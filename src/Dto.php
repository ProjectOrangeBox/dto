<?php

declare(strict_types=1);

namespace orange\dto;

use JsonSerializable;
use LogicException;
use orange\dto\DtoAttribute;
use ReflectionClass;
use ReflectionProperty;

/**
 * Dto class for handling form input validation and data management.
 *
 * This class uses PHP reflection to discover class properties with DtoAttribute
 * annotations and automatically validates and filters input data based on those rules.
 * It organizes validated data into multiple formats for flexible access patterns.
 *
 * Reflection is expensive, so it runs once per concrete Dto class: the first
 * construction compiles the class's properties, metadata and rules into a
 * static "blueprint" which every later instance replays. Constructing a Dto
 * per database row (see RecordModel::index()) therefore only pays the
 * reflection cost on the first row.
 *
 * Subclass properties must be readable publicly — compile() only discovers
 * properties whose get visibility is public. Declaring them
 * `public protected(set)` (asymmetric visibility) is recommended: the engine
 * can still assign validated values from whenValid(), while outside code can
 * no longer overwrite a property after validation, so an instance always
 * holds exactly what its rules let through.
 *
 * SOLID Principles Applied:
 * - Single Responsibility: Handles only input validation and data organization
 * - Open/Closed: Extensible through DtoAttribute annotations without modifying core logic
 * - Interface Segregation: Provides multiple access methods (asArray, asColumns, only, except) for client flexibility
 * - Dependency Inversion: Depends on DtoAttribute abstraction rather than concrete validators
 */
class Dto implements JsonSerializable
{
    /**
     * One compiled blueprint per concrete Dto class, shared by every instance.
     *
     * [class => [
     *     'primaries' => [property, ...],  // the #[IsPrimary] properties, in declaration order
     *     'tables' => [name, ...], // every table named by a #[Table], in declaration order
     *     'properties' => [property => [
     *         'fieldName' => string,   // input key (FieldName attribute or property name)
     *         'column' => string,      // db column (Column attribute or property name)
     *         'table' => ?string,      // db table (Table attribute) or null when untagged
     *         'label' => string,       // human name (Label attribute or property name)
     *         'dbCast' => ?string,     // db-shape cast target (DbCast attribute) or null
     *         'dtoArray' => ?string,   // child Dto class (IsArray with a class) or null
     *         'rules' => [[rule class, constructor args, has validate(), has filter()], ...],
     *     ]],
     * ]]
     */
    private static array $blueprints = [];

    protected array $errors = [];
    protected array $db = ['tables' => [], 'columns' => []];
    protected array $array = [];
    protected array $keys = [];

    /**
     * Initializes a Dto instance with input data and processes field attributes.
     *
     * The first construction of each concrete class compiles its blueprint;
     * every construction then processes each property through its rules.
     *
     * @param array $input The input data to be validated and processed
     */
    public function __construct(protected array $input)
    {
        $blueprint = self::$blueprints[static::class] ??= self::compile(static::class);

        foreach ($blueprint['properties'] as $property => $meta) {
            $this->process($property, $meta);
        }
    }

    /**
     * Determines if the request passed all validation rules.
     *
     * @return bool True if there are no validation errors, false otherwise
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Returns all validation errors grouped by field name.
     *
     * @return array An associative array of field names to arrays of error messages
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Returns all validation errors including nested dto-array detail,
     * dot-keyed by input field name and element key.
     *
     * errors() deliberately rolls a dto-array's child failures up into a
     * single parent message; this is the one-call export for consumers that
     * need the full picture without knowing the shape — a JSON API error
     * body, a log entry. The parent messages come first, then each invalid
     * child's own errors keyed '{field}.{key}.{childField}', recursing
     * through any deeper dto-arrays:
     *
     *     [
     *         'lines'       => ['Order lines has 1 or more errors'],
     *         'lines.1.sku' => ['Sku is required'],
     *     ]
     *
     * A dot-flat shape rather than a nested one so the result JSON-encodes
     * as a plain object of message lists — no mixed list/map arrays.
     *
     * @return array An associative array of dot-keyed field names to arrays of error messages
     */
    public function allErrors(): array
    {
        $errors = $this->errors;

        foreach (self::$blueprints[static::class]['properties'] as $property => $meta) {
            // only dto-array properties can carry nested errors — and a value
            // that never was an array leaves the property unassigned
            if ($meta['dtoArray'] === null || !isset($this->$property)) {
                continue;
            }

            foreach ($this->$property as $key => $child) {
                foreach ($child->allErrors() as $childField => $messages) {
                    $errors[$meta['fieldName'] . '.' . $key . '.' . $childField] = $messages;
                }
            }
        }

        return $errors;
    }

    /**
     * Returns the keys of the fields that passed validation.
     *
     * By default the raw property names are returned. Pass false to instead get
     * the resolved input field names (the remapped FieldName values, falling back
     * to the property name when no FieldName attribute is present).
     *
     * @param bool $raw When true (default) returns raw property names; when false returns the resolved input field names
     * @return array A list of valid keys
     */
    public function validKeys(bool $raw = true): array
    {
        $valid = [];

        foreach ($this->keys as $property => $fieldName) {
            if (!isset($this->errors[$fieldName])) {
                $valid[] = $raw ? $property : $fieldName;
            }
        }

        return $valid;
    }

    /**
     * Returns the resolved input field names of the fields that passed validation.
     *
     * Convenience wrapper for validKeys(false).
     *
     * @return array A list of valid input field names
     */
    public function validInputKeys(): array
    {
        return $this->validKeys(false);
    }

    /**
     * Returns the keys of the fields that failed validation.
     *
     * By default the raw property names are returned. Pass false to instead get
     * the resolved input field names (the remapped FieldName values, falling back
     * to the property name when no FieldName attribute is present).
     *
     * @param bool $raw When true (default) returns raw property names; when false returns the resolved input field names
     * @return array A list of invalid keys
     */
    public function invalidKeys(bool $raw = true): array
    {
        $invalid = [];

        foreach ($this->keys as $property => $fieldName) {
            if (isset($this->errors[$fieldName])) {
                $invalid[] = $raw ? $property : $fieldName;
            }
        }

        return $invalid;
    }

    /**
     * Returns the resolved input field names of the fields that failed validation.
     *
     * Convenience wrapper for invalidKeys(false).
     *
     * @return array A list of invalid input field names
     */
    public function invalidInputKeys(): array
    {
        return $this->invalidKeys(false);
    }

    /**
     * Returns the resolved input field name for a property.
     *
     * Falls back to the property name when no FieldName attribute is present.
     *
     * @param string $property The property name to resolve
     * @return string The configured field name or the property name
     */
    public function fieldName(string $property): string
    {
        return self::$blueprints[static::class]['properties'][$property]['fieldName'] ?? $property;
    }

    /**
     * Returns the resolved database column name for a property.
     *
     * Falls back to the property name when no Column attribute is present.
     *
     * @param string $property The property name to resolve
     * @return string The configured column name or the property name
     */
    public function column(string $property): string
    {
        return self::$blueprints[static::class]['properties'][$property]['column'] ?? $property;
    }

    /**
     * Returns the resolved database table name for a property.
     *
     * Null when the property carries no #[Table] - it belongs to no table, which
     * is exactly why asColumns() files it under none. Unlike column() and label(),
     * there is no property-name fallback: inventing a table name here would name
     * a table the property is not in.
     *
     * @param string $property The property name to resolve
     * @return ?string The configured table name, or null when the property declares none
     */
    public function table(string $property): ?string
    {
        return self::$blueprints[static::class]['properties'][$property]['table'] ?? null;
    }

    /**
     * Returns the primary key columns, in declaration order.
     *
     * A class may tag more than one property #[IsPrimary]: several in one table
     * make a compound key, and one per table gives a multi-table Dto a key for
     * each. Name a table to get only that table's — a model wants its own key,
     * not every key the Dto happens to carry. A class that tags no #[Table] at
     * all has only the one table it was written for, so a $tablename matches
     * all of its primaries whatever it is called.
     *
     * Column names, resolved exactly as asColumns() keys them, so
     * array_keys(primaryValues()) always equals primaries().
     *
     * @param ?string $tablename Restrict to the primaries of this table
     * @return array<int, string> The primary key column names, empty when none is tagged
     */
    public function primaries(?string $tablename = null): array
    {
        $columns = [];

        foreach ($this->primaryMetas($tablename) as $meta) {
            $columns[] = $meta['column'];
        }

        return $columns;
    }

    /**
     * Returns the primary key's column name when there is exactly one.
     *
     * The singular door, for the ordinary single-key record. A compound key has
     * no single answer, so it throws rather than naming half of one — ask
     * primaries() instead, or narrow to a table.
     *
     * @param ?string $tablename Restrict to the primaries of this table
     * @return ?string The primary key column name, or null when none is tagged
     * @throws LogicException When the class (or the named table) has more than one primary
     */
    public function primary(?string $tablename = null): ?string
    {
        $this->assertOnePrimary('primary', 'primaries', $tablename);

        $columns = $this->primaries($tablename);

        return $columns === [] ? null : $columns[0];
    }

    /**
     * Returns the resolved human-readable label for a property.
     *
     * Falls back to the property name when no Label attribute is present.
     *
     * @param string $property The property name to resolve
     * @return string The configured label or the property name
     */
    public function label(string $property): string
    {
        return self::$blueprints[static::class]['properties'][$property]['label'] ?? $property;
    }

    /**
     * Returns the tables this class names, in declaration order.
     *
     * Null when no property carries a #[Table] - which is also the answer to
     * "may I call asColumns() without naming a table?". A class that names
     * tables requires one; a class that names none takes any.
     *
     * Read off the class's declarations, not off the data, so an all-invalid
     * instance still reports the tables its class describes.
     *
     * @return ?array<int, string> The table names, or null when the class names none
     */
    public function tables(): ?array
    {
        $tables = self::$blueprints[static::class]['tables'];

        return $tables === [] ? null : $tables;
    }

    /**
     * Returns validated data organized by column name.
     *
     * A class that names no #[Table] describes one table - whichever one the
     * model holding it writes to - so asColumns() hands back every valid
     * property and any $tablename passed is simply that table's name.
     *
     * A class that does name tables describes several, and "every column" is
     * then not an answer any one of them can use: the $tablename says which
     * table is asking, and only the properties tagged for it come back.
     * Omitting it throws, because there is no sensible default - see tables()
     * for what a class will answer to.
     *
     * A table the class does not name throws too - a mistyped table name or
     * the wrong Dto for the job, either way a bug rather than a value. What it
     * names is fixed at the class's first construction, so this is the same
     * answer for every instance. A table it does name but has nothing valid
     * under comes back empty, which is a reading of the data and not a bug.
     *
     * Pass $withoutPrimary = true to drop the #[IsPrimary] column — the
     * shape for insert/update SET clauses, where the primary is
     * auto-assigned or targeted through the WHERE instead. Removal is
     * resolved through the tagged property's blueprint entry, so it always
     * targets the true column key. A compound key goes whole; under a
     * $tablename only that table's key goes, so a second table keeping its
     * own `id` column keeps it.
     *
     * @param bool $withoutPrimary When true the #[IsPrimary] properties' columns are removed
     * @param ?string $tablename The table asking - required when the class names any
     * @return array Column names to validated values, empty when nothing valid landed under $tablename
     * @throws LogicException When the class names tables and $tablename is missing or not one of them
     */
    public function asColumns(bool $withoutPrimary = false, ?string $tablename = null): array
    {
        $columns = $this->db['columns'];
        $scoped = false;

        if (($tables = $this->tables()) !== null) {
            if ($tablename === null) {
                throw new LogicException(static::class . ' names ' . count($tables) . ' table(s) (' . implode(', ', $tables) . '); asColumns() needs to know which one is asking - pass $tablename.');
            }

            if (!in_array($tablename, $tables, true)) {
                throw new LogicException(static::class . ' has no table "' . $tablename . '" - it names ' . implode(', ', $tables) . '.');
            }

            // named but empty is a reading of the data, not a bug: nothing
            // tagged for this table passed validation
            $columns = $this->db['tables'][$tablename] ?? [];
            $scoped = true;
        }

        if ($withoutPrimary) {
            // every primary in what is being returned, so a compound key goes
            // whole. Unscoped that is all of them; scoped, only this table's -
            // a second table keeping its own `id` column keeps it
            foreach ($this->primaryMetas($scoped ? $tablename : null) as $meta) {
                unset($columns[$meta['column']]);
            }
        }

        return $columns;
    }

    /**
     * Returns the #[IsPrimary] properties' compiled blueprint entries, keyed by
     * property name and in declaration order.
     *
     * The authoritative source for a primary's true table and column keys in
     * the db shapes.
     *
     * @param ?string $tablename Restrict to the primaries of this table
     * @return array<string, array> The blueprint entries, empty when none is tagged
     */
    private function primaryMetas(?string $tablename = null): array
    {
        $blueprint = self::$blueprints[static::class];
        $metas = [];

        foreach ($blueprint['primaries'] as $property) {
            $meta = $blueprint['properties'][$property];

            // a class that names no table at all has only the one table it was
            // written for, so its primaries answer to any name the caller asks by
            if ($tablename === null || $blueprint['tables'] === [] || $meta['table'] === $tablename) {
                $metas[$property] = $meta;
            }
        }

        return $metas;
    }

    /**
     * Guards the singular primary()/primaryValue() against a compound key.
     *
     * @throws LogicException When the scope holds more than one primary
     */
    private function assertOnePrimary(string $method, string $plural, ?string $tablename): void
    {
        $columns = $this->primaries($tablename);

        if (count($columns) > 1) {
            throw new LogicException(static::class . ' has ' . count($columns) . ' primary columns (' . implode(', ', $columns) . ')' . ($tablename === null ? '' : ' in table "' . $tablename . '"') . '; ' . $method . '() has no single answer - use ' . $plural . '() or name a table.');
        }
    }

    /**
     * Returns validated data as a simple associative array.
     *
     * @return array An associative array of property names to their validated values
     */
    public function asArray(): array
    {
        return $this->array;
    }

    /**
     * Returns validated data restricted to the given property names.
     *
     * Property names with no validated value are simply absent from the
     * result — like asArray(), invalid fields never appear.
     *
     * @param string ...$properties The property names to keep
     * @return array The validated values for those properties, keyed by property name
     */
    public function only(string ...$properties): array
    {
        return array_intersect_key($this->array, array_flip($properties));
    }

    /**
     * Returns validated data without the given property names.
     *
     * The complement of only() — useful for dropping fields that validate
     * but never persist, such as a password confirmation.
     *
     * @param string ...$properties The property names to drop
     * @return array The remaining validated values, keyed by property name
     */
    public function except(string ...$properties): array
    {
        return array_diff_key($this->array, array_flip($properties));
    }

    /**
     * Returns the primary key's validated values, keyed by column name.
     *
     * The shape a WHERE clause wants: keys match asColumns(), values come from
     * the tagged property itself. A compound key comes back whole, in
     * declaration order. Name a table for that table's key alone.
     *
     * A primary that failed validation has no value, so it is absent rather
     * than null — count() against primaries() to tell a partial key from a
     * whole one.
     *
     * @param ?string $tablename Restrict to the primaries of this table
     * @return array<string, mixed> Column names to their validated values
     */
    public function primaryValues(?string $tablename = null): array
    {
        $values = [];

        foreach ($this->primaryMetas($tablename) as $property => $meta) {
            if (array_key_exists($property, $this->array)) {
                $values[$meta['column']] = $this->array[$property];
            }
        }

        return $values;
    }

    /**
     * Returns the primary key's validated value when there is exactly one.
     *
     * The singular door, for the ordinary single-key record — null when no
     * property is tagged #[IsPrimary] or when the tagged one failed validation.
     * A compound key has no single value, so it throws rather than returning
     * half of one; ask primaryValues() instead, or narrow to a table.
     *
     * @param ?string $tablename Restrict to the primaries of this table
     * @return mixed The validated primary key value, or null
     * @throws LogicException When the class (or the named table) has more than one primary
     */
    public function primaryValue(?string $tablename = null): mixed
    {
        // primaries(), not primaryValues() - an invalid primary must still
        // count towards the ambiguity, or a compound key with one bad half
        // would quietly answer with the other
        $this->assertOnePrimary('primaryValue', 'primaryValues', $tablename);

        $values = $this->primaryValues($tablename);

        return $values === [] ? null : reset($values);
    }

    /**
     * Serializes the DTO as its validated data.
     *
     * json_encode() on a Dto — or a list of them — emits exactly the fields
     * that passed validation, keyed by property name. This is the explicit
     * contract for API output: invalid fields are omitted and engine
     * internals can never leak into the encoding.
     *
     * @return array The validated values, keyed by property name
     */
    public function jsonSerialize(): array
    {
        return $this->array;
    }

    /**
     * Curates var_dump() output for debugging.
     *
     * Without this a dump drowns the interesting state in the raw input and
     * internal table/column bookkeeping — what matters when inspecting a Dto
     * is whether it validated, what survived, and what failed.
     *
     * @return array The validity flag, validated values, and errors
     */
    public function __debugInfo(): array
    {
        return [
            'valid' => $this->isValid(),
            'data' => $this->array,
            'errors' => $this->errors,
        ];
    }

    /**
     * Returns raw input data or a single raw input value.
     *
     * @param null|string $key Optional input key to retrieve
     * @param mixed $default Default value to return when the key is not found
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = ''): mixed
    {
        if ($key === null) {
            return $this->input;
        }

        return $this->input[$key] ?? $default;
    }

    /**
     * Compiles a Dto class's blueprint: reflects every public property once,
     * resolves its metadata attributes (FieldName, Column, Table, Label,
     * IsPrimary) to plain strings and reduces its rule attributes to
     * [class, args] pairs that construction can replay without reflection.
     *
     * @param string $class The concrete Dto class to compile
     * @return array The compiled blueprint (see $blueprints)
     */
    private static function compile(string $class): array
    {
        $blueprint = ['primaries' => [], 'tables' => [], 'properties' => []];

        foreach (new ReflectionClass($class)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            // collect the DtoAttribute attributes keyed by short name — a
            // repeated short name overwrites the earlier one (last wins)
            $attributes = [];

            foreach ($property->getAttributes() as $attribute) {
                $attributeReflection = new ReflectionClass($attribute->getName());

                if ($attributeReflection->isSubclassOf(DtoAttribute::class)) {
                    $attributes[$attributeReflection->getShortName()] = $attribute;
                }
            }

            // a property with no attributes is ignored entirely by the engine
            if (empty($attributes)) {
                continue;
            }

            $propertyName = $property->getName();

            // index by lowercased short name for case-insensitive metadata
            // lookups (first match wins, like the old findBy())
            $byLowerName = [];

            foreach ($attributes as $name => $attribute) {
                $byLowerName[strtolower($name)] ??= $attribute;
            }

            // resolve the metadata to plain strings, defaulting to the property name
            $fieldName = isset($byLowerName['fieldname']) ? $byLowerName['fieldname']->newInstance()->getName() : $propertyName;
            $column = isset($byLowerName['column']) ? $byLowerName['column']->newInstance()->getName() : $propertyName;
            // no #[Table] means the property belongs to no table in particular, so
            // asColumns() never files it under one. An empty name (a bare #[Table])
            // says as little as no attribute at all
            $table = isset($byLowerName['table']) ? ($byLowerName['table']->newInstance()->getName() ?: null) : null;

            // the tables this class names decide whether asColumns() requires
            // a $tablename, and which ones it will answer to
            if ($table !== null && !in_array($table, $blueprint['tables'], true)) {
                $blueprint['tables'][] = $table;
            }
            $label = isset($byLowerName['label']) ? $byLowerName['label']->newInstance()->getName() : $propertyName;
            // instantiating DbCast validates its target — a typo throws here,
            // at the class's first construction, not silently at storage time
            $dbCast = isset($byLowerName['dbcast']) ? $byLowerName['dbcast']->newInstance()->getName() : null;

            // every property tagged #[IsPrimary] counts: several in one table
            // make a compound key, one per table gives a multi-table Dto a key
            // for each. The column name is read back off the blueprint entry, so
            // primaries() names exactly what asColumns() keys by
            if (isset($byLowerName['isprimary'])) {
                $blueprint['primaries'][] = $propertyName;
            }

            // keep only the attributes that actually validate or filter;
            // pure metadata attributes never need instantiating again
            $rules = [];
            $dtoArray = null;

            foreach ($attributes as $attribute) {
                $ruleClass = $attribute->getName();
                $validates = method_exists($ruleClass, 'validate');
                $filters = method_exists($ruleClass, 'filter');

                if ($validates || $filters) {
                    $rules[] = [$ruleClass, $attribute->getArguments(), $validates, $filters];
                }

                // a rule that maps elements into child DTOs (IsArray with a
                // class) flags this as a dto-array property — nested output,
                // no db shapes. Instantiating also verifies the class here,
                // at first construction, not silently at input time
                if (method_exists($ruleClass, 'getDtoClass')) {
                    $dtoArray = $attribute->newInstance()->getDtoClass();
                }
            }

            $blueprint['properties'][$propertyName] = [
                'fieldName' => $fieldName,
                'column' => $column,
                'table' => $table,
                'label' => $label,
                'dbCast' => $dbCast,
                'dtoArray' => $dtoArray,
                'rules' => $rules,
            ];
        }

        return $blueprint;
    }

    /**
     * Processes a property by applying the validation rules and filters from
     * its compiled blueprint entry.
     *
     * Records the property-to-field-name mapping, validates the input value
     * against all rules, applies filters, and stores valid data in the
     * database and array structures.
     *
     * @param string $property The property name to process
     * @param array $meta The property's blueprint entry
     * @return void
     */
    protected function process(string $property, array $meta): void
    {
        $fieldName = $meta['fieldName'];
        $label = $meta['label'];

        // remember the raw property name mapped to its resolved field name
        $this->keys[$property] = $fieldName;

        // get the value from the input
        $value = $this->input[$fieldName] ?? '';

        // optional fields only validate when provided — null, '' and [] count
        // as absent; presence rules (see validatesAbsent()) always run
        $provided = $value !== '' && $value !== [];

        // an absent dto-array normalizes to [] so the typed array property
        // and the flattened output stay well-formed either way
        if (!$provided && $meta['dtoArray'] !== null) {
            $value = [];
        }

        // assume the value is valid until a validation rule fails
        $isValid = true;

        // replay the rules in declaration order
        foreach ($meta['rules'] as [$ruleClass, $args, $validates, $filters]) {
            $rule = new $ruleClass(...$args);

            // send a copy of this request into the rule so it can access other fields if needed
            $rule->request($this);

            // do validation
            if ($validates && ($provided || $rule->validatesAbsent())) {
                if (!$rule->validate($value)) {
                    $this->errors[$fieldName][] = $rule->getMessage($label);
                    $isValid = false;
                }
            }
            // do filter
            if ($filters) {
                $value = $rule->filter($value);
            }
        }

        // if the value is valid assign it to the class and add it to the db array properties
        if ($isValid) {
            // assign the value to the class and add it to the db array properties
            $this->whenValid($property, $value, $meta['table'], $meta['column'], $meta['dbCast'], $meta['dtoArray']);
        } elseif ($meta['dtoArray'] !== null && is_array($value)) {
            // a failed dto-array still assigns the child DTOs to the property
            // so the caller can extract it and read each child's own errors()
            // — but never to the output shapes, which only carry valid data
            $this->$property = $value;
        }
    }

    /**
     * Stores validated data across multiple storage formats.
     *
     * Assigns the validated value to the class property, and stores it in the
     * array and database table/column structures for flexible data access.
     * A DbCast target applies to the db shapes only — the property and array
     * keep the domain value while asColumns() carries the storage
     * value (e.g. a bool property stored as 0/1).
     *
     * A dto-array property keeps the child Dto objects on the property while
     * asArray()/json carry each child flattened through its own asArray() —
     * and it never reaches the db shapes, because a nested structure has no
     * single-row table/column representation. Extract the children and call
     * asColumns() on each one individually to persist them.
     *
     * A property with no #[Table] is stored as a column but not under any
     * table, so asColumns($tablename) only ever reports what a class claimed.
     *
     * @param string $property The property name to assign the value to
     * @param mixed $value The validated value to store
     * @param ?string $table The database table name, or null when the property declares none
     * @param string $column The database column name
     * @param ?string $dbCast Scalar cast target for the db shapes, or null for none
     * @param ?string $dtoArray Child Dto class for dto-array properties, or null
     * @return void
     */
    protected function whenValid($property, $value, $table, $column, $dbCast = null, $dtoArray = null): void
    {
        // assign to the class
        $this->$property = $value;

        // dto-array: nested plain arrays in the output, no db shapes
        if ($dtoArray !== null) {
            $this->array[$property] = array_map(static fn (Dto $child): array => $child->asArray(), $value);

            return;
        }

        // assign to the array for easy access
        $this->array[$property] = $value;

        // the db shapes may carry a different storage type than the domain
        // property — null is never cast, so nullable columns stay null
        $dbValue = ($dbCast === null || $value === null) ? $value : match ($dbCast) {
            'int' => (int)$value,
            'float' => (float)$value,
            'string' => (string)$value,
            'bool' => (bool)$value,
            default => $value,
        };

        // if valid add it to the db array - the table shape only takes properties
        // that named a table, the column shape takes them all
        if ($table !== null) {
            $this->db['tables'][$table][$column] = $dbValue;
        }

        $this->db['columns'][$column] = $dbValue;
    }
}

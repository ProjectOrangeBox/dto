<?php

declare(strict_types=1);

namespace orange\dto;

use JsonSerializable;
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
 * - Interface Segregation: Provides multiple access methods (asArray, asTable, asColumns, only, except) for client flexibility
 * - Dependency Inversion: Depends on DtoAttribute abstraction rather than concrete validators
 */
class Dto implements JsonSerializable
{
    /**
     * One compiled blueprint per concrete Dto class, shared by every instance.
     *
     * [class => [
     *     'primary' => ?string,
     *     'primaryProperty' => ?string,
     *     'properties' => [property => [
     *         'fieldName' => string,   // input key (FieldName attribute or property name)
     *         'column' => string,      // db column (Column attribute or property name)
     *         'table' => string,       // db table (Table attribute or property name)
     *         'label' => string,       // human name (Label attribute or property name)
     *         'dbCast' => ?string,     // db-shape cast target (DbCast attribute) or null
     *         'rules' => [[rule class, constructor args, has validate(), has filter()], ...],
     *     ]],
     * ]]
     */
    private static array $blueprints = [];

    protected array $errors = [];
    protected array $db = ['tables' => [], 'columns' => []];
    protected ?string $primary = null;
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

        $this->primary = $blueprint['primary'];

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
     * Falls back to the property name when no Table attribute is present.
     *
     * @param string $property The property name to resolve
     * @return string The configured table name or the property name
     */
    public function table(string $property): string
    {
        return self::$blueprints[static::class]['properties'][$property]['table'] ?? $property;
    }

    /**
     * Returns the primary key's column name — the #[Column] name of the
     * property tagged #[IsPrimary], falling back to its resolved field name
     * when no Column attribute is present. When multiple properties are
     * tagged, the last one declared wins — there is only one primary.
     *
     * @return ?string The primary key column name, or null when no property is tagged
     */
    public function primary(): ?string
    {
        return $this->primary;
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
     * Returns validated data organized by database table structure.
     *
     * Pass $withoutPrimary = true to drop the #[IsPrimary] column from its
     * table — the shape for insert/update SET clauses, where the primary is
     * auto-assigned or targeted through the WHERE instead.
     *
     * @param false|string $tablename Optional table name to retrieve specific table data; returns all tables if false
     * @param bool $withoutPrimary When true the #[IsPrimary] property's column is removed from its table
     * @return array The table or column data structure
     * @throws \OutOfBoundsException When the requested table name is not found
     */
    public function asTable(false|string $tablename = false, bool $withoutPrimary = false): array
    {
        $db = $this->db['tables'];

        if ($withoutPrimary && ($meta = $this->primaryMeta()) !== null) {
            unset($db[$meta['table']][$meta['column']]);
        }

        if ($tablename) {
            if (!isset($db[$tablename])) {
                throw new \OutOfBoundsException('Table ' . $tablename . ' not found.');
            }

            $db = $db[$tablename];
        }

        return $db;
    }

    /**
     * Returns validated data organized by column name.
     *
     * Pass $withoutPrimary = true to drop the #[IsPrimary] column — the
     * shape for insert/update SET clauses, where the primary is
     * auto-assigned or targeted through the WHERE instead. Removal is
     * resolved through the tagged property's blueprint entry, so it is
     * immune to primary()'s field-name fallback diverging from the
     * asColumns() key.
     *
     * @param bool $withoutPrimary When true the #[IsPrimary] property's column is removed
     * @return array An associative array of column names to their validated values
     */
    public function asColumns(bool $withoutPrimary = false): array
    {
        $columns = $this->db['columns'];

        if ($withoutPrimary && ($meta = $this->primaryMeta()) !== null) {
            unset($columns[$meta['column']]);
        }

        return $columns;
    }

    /**
     * Returns the #[IsPrimary] property's compiled blueprint entry.
     *
     * The authoritative source for the primary's true table and column keys
     * in the db shapes — unlike primary(), which falls back to the field
     * name without a #[Column] attribute.
     *
     * @return ?array The blueprint entry, or null when no property is tagged
     */
    private function primaryMeta(): ?array
    {
        $property = self::$blueprints[static::class]['primaryProperty'];

        return $property === null ? null : self::$blueprints[static::class]['properties'][$property];
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
     * Returns the primary key's validated value.
     *
     * Resolved through the tagged property itself rather than primary()'s
     * column name — primary() falls back to the field name without a
     * #[Column] attribute, which need not match the asColumns() key, but
     * the tagged property's validated value is always unambiguous. Null
     * when no property is tagged #[IsPrimary] or when it failed validation.
     *
     * @return mixed The validated primary key value, or null
     */
    public function primaryValue(): mixed
    {
        $property = self::$blueprints[static::class]['primaryProperty'];

        return $property === null ? null : ($this->array[$property] ?? null);
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
        $blueprint = ['primary' => null, 'primaryProperty' => null, 'properties' => []];

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
            $table = isset($byLowerName['table']) ? $byLowerName['table']->newInstance()->getName() : $propertyName;
            $label = isset($byLowerName['label']) ? $byLowerName['label']->newInstance()->getName() : $propertyName;
            // instantiating DbCast validates its target — a typo throws here,
            // at the class's first construction, not silently at storage time
            $dbCast = isset($byLowerName['dbcast']) ? $byLowerName['dbcast']->newInstance()->getName() : null;

            // a property tagged #[IsPrimary] records its column name for primary();
            // with no #[Column] attribute the resolved field name is used instead.
            // a later tagged property always overwrites — there is only one primary
            if (isset($byLowerName['isprimary'])) {
                $blueprint['primary'] = isset($byLowerName['column']) ? $column : $fieldName;
                $blueprint['primaryProperty'] = $propertyName;
            }

            // keep only the attributes that actually validate or filter;
            // pure metadata attributes never need instantiating again
            $rules = [];

            foreach ($attributes as $attribute) {
                $ruleClass = $attribute->getName();
                $validates = method_exists($ruleClass, 'validate');
                $filters = method_exists($ruleClass, 'filter');

                if ($validates || $filters) {
                    $rules[] = [$ruleClass, $attribute->getArguments(), $validates, $filters];
                }
            }

            $blueprint['properties'][$propertyName] = [
                'fieldName' => $fieldName,
                'column' => $column,
                'table' => $table,
                'label' => $label,
                'dbCast' => $dbCast,
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
        $provided = $value !== null && $value !== '' && $value !== [];

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
            $this->whenValid($property, $value, $meta['table'], $meta['column'], $meta['dbCast']);
        }
    }

    /**
     * Stores validated data across multiple storage formats.
     *
     * Assigns the validated value to the class property, and stores it in the
     * array and database table/column structures for flexible data access.
     * A DbCast target applies to the db shapes only — the property and array
     * keep the domain value while asColumns()/asTable() carry the storage
     * value (e.g. a bool property stored as 0/1).
     *
     * @param string $property The property name to assign the value to
     * @param mixed $value The validated value to store
     * @param string $table The database table name
     * @param string $column The database column name
     * @param ?string $dbCast Scalar cast target for the db shapes, or null for none
     * @return void
     */
    protected function whenValid($property, $value, $table, $column, $dbCast = null): void
    {
        // assign to the class
        $this->$property = $value;

        // assign to the array for easy access
        $this->array[$property] = $value;

        // the db shapes may carry a different storage type than the domain
        // property — null is never cast, so nullable columns stay null
        $dbValue = ($dbCast === null || $value === null) ? $value : match ($dbCast) {
            'int' => (int)$value,
            'float' => (float)$value,
            'string' => (string)$value,
            'bool' => (bool)$value,
        };

        // if valid add it to the db array
        $this->db['tables'][$table][$column] = $dbValue;
        $this->db['columns'][$column] = $dbValue;
    }
}

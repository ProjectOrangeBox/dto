<?php

declare(strict_types=1);

namespace orange\request;

use orange\request\RequestAttribute;
use ReflectionClass;

/**
 * Request class for handling form input validation and data management.
 *
 * This class uses PHP reflection to discover class properties with RequestAttribute
 * annotations and automatically validates and filters input data based on those rules.
 * It organizes validated data into multiple formats for flexible access patterns.
 *
 * SOLID Principles Applied:
 * - Single Responsibility: Handles only input validation and data organization
 * - Open/Closed: Extensible through RequestAttribute annotations without modifying core logic
 * - Interface Segregation: Provides multiple access methods (asArray, asTable, asColumns) for client flexibility
 * - Dependency Inversion: Depends on RequestAttribute abstraction rather than concrete validators
 *
 * @property array $errors Validation errors grouped by field name
 * @property array $fieldSet Mapping of property names to their validation attributes
 * @property array $db Validated data organized by table and column structure
 * @property array $array Validated data in simple associative array format
 * @property array $keys Mapping of raw property names to their resolved field names
 */
class Request
{
  protected array $errors = [];
  protected array $fieldSet = [];
  protected array $db = ['tables' => [], 'columns' => []];
  protected array $array = [];
  protected array $keys = [];

  /**
   * Initializes a Request instance with input data and processes field attributes.
   *
   * Uses reflection to discover properties with RequestAttribute annotations,
   * then processes each property through its validation rules.
   *
   * @param array $input The input data to be validated and processed
   */
  public function __construct(protected array $input)
  {
    // use reflection to get the properties and their attributes
    $reflectionClass = new ReflectionClass(get_class($this));

    // loop through the properties and get their attributes
    foreach ($reflectionClass->getProperties() as $property) {
      $attributes = [];

      foreach ($property->getAttributes() as $attribute) {
        $attributeClassName = $attribute->getName();

        if ((new ReflectionClass($attributeClassName))->isSubclassOf(RequestAttribute::class)) {
          $attributes[$this->getClass($attributeClassName)] = $attribute->newInstance();
        }
      }

      if (!empty($attributes)) {
        $this->fieldSet[$property->getName()] = $attributes;
      }
    }

    // now we can loop through the field set and process the attributes for each property
    foreach ($this->fieldSet as $property => $attributes) {
      $this->process($property, $attributes);
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
    return $this->findBy($property, 'FieldName', $this->fieldSet[$property] ?? []);
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
    return $this->findBy($property, 'Column', $this->fieldSet[$property] ?? []);
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
    return $this->findBy($property, 'Table', $this->fieldSet[$property] ?? []);
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
    return $this->findBy($property, 'Label', $this->fieldSet[$property] ?? []);
  }

  /**
   * Returns validated data organized by database table structure.
   *
   * @param false|string $tablename Optional table name to retrieve specific table data; returns all tables if false
   * @return array The table or column data structure
   * @throws \Exception When the requested table name is not found
   */
  public function asTable(false|string $tablename = false): array
  {
    $db = $this->db['tables'];

    if ($tablename) {
      if (!isset($this->db['tables'][$tablename])) {
        throw new \Exception('Table ' . $tablename . ' not found.');
      }

      $db = $this->db['tables'][$tablename];
    }

    return $db;
  }

  /**
   * Returns validated data organized by column name.
   *
   * @return array An associative array of column names to their validated values
   */
  public function asColumns(): array
  {
    return $this->db['columns'];
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
   * Extracts the short class name from a fully qualified class name.
   *
   * @param string $className The fully qualified class name
   * @return string The short class name without namespace
   */
  protected function getClass($className): string
  {
    // Find the position of the last backslash
    $lastSlashPos = strrpos($className, '\\');

    if ($lastSlashPos === false) {
      // No namespace separator found, so the whole string is the class name
      $shortName = $className;
    } else {
      // Extract the substring after the last backslash
      $shortName = substr($className, $lastSlashPos + 1);
    }
    return $shortName;
  }

  /**
   * Processes a property by applying validation rules and filters from its attributes.
   *
   * Resolves the field/column/table/label metadata, records the property-to-field-name
   * mapping, validates the input value against all rules, applies filters, and stores
   * valid data in the database and array structures.
   *
   * @param string $property The property name to process
   * @param array $attributes The validation attributes for the property
   * @return void
   */
  protected function process(string $property, array $attributes): void
  {
    // get the field name, column name, table name and human name from the attributes

    // form field name
    $fieldName = $this->findBy($property, 'FieldName', $attributes);

    // remember the raw property name mapped to its resolved field name
    $this->keys[$property] = $fieldName;

    // table column name
    $column = $this->findBy($property, 'Column', $attributes);

    // table name
    $table = $this->findBy($property, 'Table', $attributes);

    // human readable name for error messages
    $label = $this->findBy($property, 'Label', $attributes);

    // get the value from the input
    $value = $this->input[$fieldName] ?? '';

    // assume the value is valid until a validation rule fails
    $isValid = true;

    // loop through the attributes and apply validation and filtering
    foreach ($attributes as $rule) {
      // send a copy of this request into the rule so it can access other fields if needed
      $rule->request($this);

      // do validation
      if (method_exists($rule, 'validate')) {
        if (!$rule->validate($value)) {
          $this->errors[$fieldName][] = $rule->getMessage($label);
          $isValid = false;
        }
      }
      // do filter
      if (method_exists($rule, 'filter')) {
        $value = $rule->filter($value);
      }
    }

    // if the value is valid assign it to the class and add it to the db array properties
    if ($isValid) {
      // assign the value to the class and add it to the db array properties
      $this->whenValid($property, $value, $table, $column);
    }
  }

  /**
   * Stores validated data across multiple storage formats.
   *
   * Assigns the validated value to the class property, and stores it in the
   * array and database table/column structures for flexible data access.
   *
   * @param string $property The property name to assign the value to
   * @param mixed $value The validated value to store
   * @param string $table The database table name
   * @param string $column The database column name
   * @return void
   */
  protected function whenValid($property, $value, $table, $column): void
  {
    // assign to the class
    $this->$property = $value;

    // assign to the array for easy access
    $this->array[$property] = $value;

    // if valid add it to the db array
    $this->db['tables'][$table][$column] = $value;
    $this->db['columns'][$column] = $value;
  }

  /**
   * Finds an attribute value by key name using case-insensitive matching.
   *
   * @param string $property The property name (used as default if key not found)
   * @param string $key The attribute key to search for
   * @param array $attributes The attributes to search through
   * @return string The value from the matching attribute or the property name if not found
   */
  protected function findBy(string $property, string $key, array $attributes): string
  {
    $fieldName = $property;

    foreach ($attributes as $attrName => $attribute) {
      if (strtolower($attrName) == strtolower($key)) {
        $fieldName = $attribute->getName();

        break;
      }
    }

    return $fieldName;
  }
}

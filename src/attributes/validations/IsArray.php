<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use InvalidArgumentException;
use orange\dto\Dto;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is an array — multi-select and checkbox-group inputs.
 *
 * Optionally maps each element into a child Dto: #[IsArray(Line::class)]
 * expects every element to itself be an array, constructs a Line from each
 * one (preserving the input keys), and validates them all. The children
 * replace the raw elements through filter(), so the property ends up holding
 * an array of Dto objects. Child failures surface as a single error on the
 * parent — extract the property and read each child's own errors() for the
 * detail.
 */
class IsArray extends DtoAttribute
{
    protected string $errorMsg = '%s must be an array';

    /**
     * The child DTOs built by validate(), or null when no class is configured
     * or the input never was an array.
     */
    private ?array $children = null;

    /**
     * Stores the optional child Dto class and custom message.
     *
     * The class is verified here — a typo throws at the owning class's first
     * construction (compile time), not silently at input time.
     */
    public function __construct(private ?string $dtoClass = null, string $message = '')
    {
        if ($dtoClass !== null && !is_subclass_of($dtoClass, Dto::class)) {
            throw new InvalidArgumentException($dtoClass . ' is not a ' . Dto::class . ' subclass.');
        }

        parent::__construct($message);
    }

    /**
     * Returns the configured child Dto class, or null when this is a plain
     * is-an-array check. compile() uses this to flag dto-array properties.
     */
    public function getDtoClass(): ?string
    {
        return $this->dtoClass;
    }

    /**
     * Checks whether the input is an array and — when a child class is
     * configured — builds and validates a child Dto per element.
     *
     * Only one message is ever reported, most fundamental first: not an
     * array at all, then a non-array element, then child validation errors.
     * A non-array element still gets a child built from [] so the extracted
     * property's keys always line up with the input.
     */
    public function validate(mixed $input): bool
    {
        if (!is_array($input)) {
            $this->errorMsg = '%s must be an array';

            return false;
        }

        if ($this->dtoClass === null) {
            return true;
        }

        $this->children = [];
        $malformed = false;
        $invalid = false;

        foreach ($input as $key => $element) {
            if (!is_array($element)) {
                $malformed = true;
                $element = [];
            }

            $child = new $this->dtoClass($element);

            $invalid = $invalid || !$child->isValid();

            $this->children[$key] = $child;
        }

        if ($malformed) {
            $this->errorMsg = '%s contains a non-object entry';

            return false;
        }

        if ($invalid) {
            $this->errorMsg = '%s has 1 or more errors';

            return false;
        }

        return true;
    }

    /**
     * Swaps the raw elements for the child DTOs built by validate().
     *
     * Without a configured class — or when the input never was an array —
     * the value passes through untouched.
     */
    public function filter(mixed $value): mixed
    {
        return $this->children ?? $value;
    }
}

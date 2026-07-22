<?php

declare(strict_types=1);

namespace orange\dto;

/**
 * Provides shared behavior for dto metadata, filters, and validators.
 */
class DtoAttribute
{
    protected Dto $dto;
    protected string $human = 'This field';
    protected string $errorMsg = '';

    /**
     * When true this rule's validate() runs even when the field is absent
     * from the input. Presence rules (IsRequired, RequiredIf, RequiredWith)
     * enable this; ordinary rules only validate a provided value.
     */
    protected bool $validateWhenAbsent = false;

    /**
     * Stores an optional custom error message for the attribute.
     */
    public function __construct(protected string $message = '')
    {
        $this->message = $message;
    }

    /**
     * Shares the current request instance with the attribute.
     */
    public function request(Dto $request): void
    {
        $this->dto = $request;
    }

    /**
     * Whether validate() should run when the field is absent from the input.
     */
    public function validatesAbsent(): bool
    {
        return $this->validateWhenAbsent;
    }

    /**
     * Returns the formatted error message for the attribute.
     */
    public function getMessage(?string $human = null): string
    {
        $human = $human ?: $this->human;
        $errorMsg = $this->message ?: $this->errorMsg;

        return sprintf($errorMsg, ...array_merge([$human], $this->getMessageValues()));
    }

    /**
     * Supplies additional values used when formatting error messages.
     */
    protected function getMessageValues(): array
    {
        return [];
    }

    /**
     * Determines whether a value counts as "provided" for presence checks.
     *
     * Unlike empty(), this treats '0' and 0 as filled — only null, '', and []
     * are considered absent.
     */
    protected function isFilled(mixed $input): bool
    {
        return $input !== null && $input !== '' && $input !== [];
    }
}

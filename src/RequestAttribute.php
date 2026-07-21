<?php

declare(strict_types=1);

namespace orange\request;

/**
 * Provides shared behavior for request metadata, filters, and validators.
 */
class RequestAttribute
{
    protected Request $request;
    protected string $human = 'This field';
    protected string $errorMsg = '';

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
    public function request(Request $request): void
    {
        $this->request = $request;
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

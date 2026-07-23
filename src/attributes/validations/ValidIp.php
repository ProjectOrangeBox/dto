<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a valid IP address.
 */
class ValidIp extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid IP address';

    /**
     * Stores the IP version filter and optional custom message.
     */
    public function __construct(private readonly string $version = '', string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input is a valid IP address for the configured version.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $flags = 0;

            if (strtolower($this->version) === 'ipv4') {
                $flags = FILTER_FLAG_IPV4;
            } elseif (strtolower($this->version) === 'ipv6') {
                $flags = FILTER_FLAG_IPV6;
            }

            $bool = filter_var($input, FILTER_VALIDATE_IP, $flags) !== false;
        }

        return $bool;
    }

    /**
     * Returns the configured IP version filter.
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}

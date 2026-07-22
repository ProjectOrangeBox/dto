<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Removes invisible control and zero-width characters from string input —
 * the classic copy-paste garbage. Tabs, newlines and carriage returns are
 * kept; C0/C1 controls, DEL, zero-width spaces/joiners and the BOM are not.
 */
class StripControlChars extends DtoAttribute
{
    /**
     * Returns the cleaned string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only clean when the input is a string.
        if (is_string($input)) {
            $output = preg_replace(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{0080}-\x{009F}\x{200B}-\x{200D}\x{FEFF}]/u',
                '',
                $input
            ) ?? $input;
        }

        return $output;
    }
}

<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Folds accented characters to their closest ASCII equivalents (é -> e,
 * ü -> u). Useful ahead of Slugify or systems that only accept ASCII.
 *
 * Uses the intl extension's Transliterator when available (accurate, and
 * romanizes non-Latin scripts too); otherwise falls back to iconv, whose
 * results vary by platform (accents may become quote artifacts).
 */
class Transliterate extends DtoAttribute
{
    /**
     * Returns the ASCII-folded string, or the original value when not a
     * string or when the conversion fails.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only transliterate when the input is a string.
        if (is_string($input)) {
            $converted = function_exists('transliterator_transliterate')
                ? transliterator_transliterate('Any-Latin; Latin-ASCII', $input)
                : iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);

            if (is_string($converted)) {
                $output = $converted;
            }
        }

        return $output;
    }
}

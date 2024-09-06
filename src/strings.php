<?php

declare(strict_types=1);

namespace Crell\MiDy;

/**
 * Extracts and returns a multi-lin value from a string, between the provided delimiters.
 *
 * Only the first instance of the start/end delimiters is checked.
 *
 * @param string $string
 *   The string from which to extract.
 * @param string $startDelim
 *   The opening delimiter.
 * @param string $endDelim
 *   The ending delimiter.
 * @return string|null
 *   The extracted string, or null if it wasn't found.
 */
function str_extract_between(string $string, string $startDelim, string $endDelim): ?string
{
    $patternDelimiter = '#';

    $startDelim = preg_quote($startDelim, $patternDelimiter);
    $endDelim = preg_quote($endDelim, $patternDelimiter);

    $pattern = "{$patternDelimiter}{$startDelim}(.*){$endDelim}{$patternDelimiter}ms";

    preg_match($pattern, $string, $matches);

    return $matches[1] ?? null;
}

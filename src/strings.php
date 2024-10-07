<?php

declare(strict_types=1);

namespace Crell\MiDy;

/**
 * Extracts and returns a multi-line value from a string, between the provided delimiters.
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

/**
 * Creates a directory if it doesn't exist yet.
 *
 * @param string $path
 *   The path to the directory to create.  If not a stream path, a relative
 *   path will be evaluated relative to the current working directory. For that
 *   reason, using an absolute path is recommended.
 * @return string
 *   The path to the just-validated directory.  If it is not a stream path,
 *   the result will be run through realpath() to minimize confusion.
 */
function ensure_dir(string $path): string
{
    if (!is_dir($path)) {
        if (!mkdir($path, recursive: true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    return str_contains($path, '://')
        ? $path
        : \realpath($path);
}
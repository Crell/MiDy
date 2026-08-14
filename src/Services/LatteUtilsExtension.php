<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Latte\Extension;

class LatteUtilsExtension extends Extension
{
    public function getFunctions(): array
    {
        return [
            'readingTime' => $this->readingTime(...),
        ];
    }

    /**
     * Returns the estimated reading time of a block of text, in minutes.
     *
     * If the difference between the max and min times is less than 5 minutes,
     * only the max time will be returned.  Otherwise, it will be given as
     * range string.  No labeling (like "minutes") is included, to allow templates
     * to control the display completely.
     *
     * There is no error handling for the minWpm being less than maxWpm, as throwing
     * an exception makes little sense here and the worst case is the numbers
     * appear a bit odd.
     */
    public function readingTime(string $text, int $minWpm = 200, int $maxWpm=250): string
    {
        $words = str_word_count($text);
        $slowTime = floor($words / $minWpm);
        $fastTime = ceil($words / $maxWpm);

        if (abs($fastTime - $slowTime) < 5) {
            return (string)$fastTime;
        }

        return "$fastTime-$slowTime";
    }
}

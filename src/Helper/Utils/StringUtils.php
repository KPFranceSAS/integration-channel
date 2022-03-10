<?php

namespace App\Helper\Utils;

use function Symfony\Component\String\u;

class StringUtils
{

    public static function humanizeString(string $string): string
    {
        $uString = u($string);
        $upperString = $uString->upper()->toString();
        if ($uString->toString() === $upperString) {
            return $upperString;
        }

        return $uString
            ->replaceMatches('/([A-Z])/', '_$1')
            ->replaceMatches('/[_\s]+/', ' ')
            ->trim()
            ->lower()
            ->title(true)
            ->toString();
    }
}

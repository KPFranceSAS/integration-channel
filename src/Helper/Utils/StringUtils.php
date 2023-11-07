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



    public static function compareString(string $string1, string $string2): bool
    {
        return self::transformStringToCompare($string1) == self::transformStringToCompare($string2);
    }

    public static function transformStringToCompare(string $string): string
    {
        $conversion=[
            " " => "",
            "." => "",
            "," => "",
            "/" => ""
        ];
        return str_replace(array_keys($conversion), array_values($conversion), strtoupper($string));
    }
}

<?php

namespace XRPL_PHP\Utils;

use XRPL_PHP\Core\Buffer;

class Utilities
{
    public const HEX_REGEX = '/^[A-F0-9a-f]+$/';
    public const UPPERCASE_HEX_REGEX = '/^[A-F0-9]+$/';
    public const ISSUED_CURRENCY_SIZE = 3;

    public static function isHex(string $str, bool $checkUppercase = false): bool
    {
        if ($checkUppercase) {
            return (bool) preg_match(self::UPPERCASE_HEX_REGEX, $str);
        }

        return (bool)  preg_match(self::HEX_REGEX, $str);
    }

    public static function isoToHex(string $iso): string
    {
        $bytes = Buffer::alloc(20);
        if ($iso !== 'XRP') {
            $isoBytes = array_map(function ($c) {
                return ord($c);
            }, str_split($iso));
            $bytes->set(12, $isoBytes);
        }

        return $bytes->toString();
    }

    public static function isIssuedCurrency(mixed $input): bool
    {
        return (
          is_array($input) &&
          count($input) === self::ISSUED_CURRENCY_SIZE &&
          isset($input['currency']) && is_string($input['currency']) &&
          isset($input['issuer']) && is_string($input['issuer']) &&
          isset($input['value']) && is_string($input['value'])
        );
    }

    /**
     * Converts a string to its hex equivalent. Useful for Memos.
     *
     * @param string $string
     * @return string
     */
    public static function convertStringToHex(string $string): string
    {
        return Buffer::from($string, 'utf-8')->toString();
    }

    /**
     * Converts hex to its string equivalent. Useful to read the Domain field and some Memos.
     *
     * @param string $hex
     * @return string
     * @throws \Exception
     */
    public static function convertHexToString(string $hex): string
    {
        return Buffer::from($hex, 'hex')->toUtf8();
    }
}
<?php

namespace Hardcastle\XRPL_PHP\Utils;

use Hardcastle\Buffer\Buffer;

/**
 * Assorted helpers that have no better home yet.
 */
class Utilities
{
    public const HEX_REGEX = '/^[A-F0-9a-f]+$/';
    public const UPPERCASE_HEX_REGEX = '/^[A-F0-9]+$/';
    public const ISSUED_CURRENCY_SIZE = 3;

    /**
     * Whether the string consists only of hex digits.
     */
    public static function isHex(string $str, bool $checkUppercase = false): bool
    {
        if ($checkUppercase) {
            return (bool) preg_match(self::UPPERCASE_HEX_REGEX, $str);
        }

        return (bool)  preg_match(self::HEX_REGEX, $str);
    }

    /**
     * Pad a three character currency code to the 40 character hex form the ledger
     * stores it in.
     */
    public static function isoToHex(string $iso): string
    {
        $bytes = Buffer::alloc(20);
        if ($iso !== 'XRP') {
            $isoBytes = array_map(ord(...), str_split($iso));
            $bytes->set(12, $isoBytes);
        }

        return $bytes->toString();
    }

    /**
     * Whether an amount describes an issued token rather than XRP.
     */
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
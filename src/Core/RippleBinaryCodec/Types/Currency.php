<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class Currency extends Hash160
{
    private const XRP_HEX_REGEX = '/^0{40}$/';
    private const ISO_REGEX = '/^[A-Z0-9a-z?!@#$%^&*(){}[\]|]{3}$/';
    private const HEX_REGEX = '/^[A-F0-9]{40}$/';
    private const STANDARD_FORMAT_HEX_REGEX = '/^0{24}[\x00-\x7F]{6}0{10}$/';

    private ?string $iso = null;

    public function __construct(?Buffer $bytes = null)
    {
        if (is_null($bytes)) {
            $bytes = Buffer::alloc(static::$width); // 20 Zeros = XRP
        }

        parent::__construct($bytes);

        $hex = $this->toBytes()->toString(); //this.bytes.toString('hex')

        if (preg_match(self::XRP_HEX_REGEX, $hex) === 1) {
            $this->iso = 'XRP';
        } else if (preg_match(self::STANDARD_FORMAT_HEX_REGEX , $hex) === 1) {
            $slice = $this->toBytes()->slice(12, 15);
            $this->iso = $this->isoCodeFromHex($slice);
        }
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        return new Currency($parser->read(static::$width));
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        if (!static::isValidRepresentation($serializedJson)) {
            throw new \Exception('Unsupported Currency representation: ' . $serializedJson);
        }

        $bytes = strlen($serializedJson) === 3 ? static::isoToBytes($serializedJson) : Buffer::from($serializedJson);

        return new Currency($bytes);
    }

    public function toJson(): array|string|int
    {
        if (!is_null($this->iso)) {
            return $this->iso;
        }

        return $this->toBytes()->toString();
    }

    private static function isValidRepresentation(string $value): bool
    {
        return static::isStringRepresentation($value);
    }

    private static function isStringRepresentation(string $input): bool
    {
        return static::isIsoCode($input) || static::isHex($input);
    }

    private static function isIsoCode(string $iso): bool
    {
        return preg_match(self::ISO_REGEX, $iso) === 1;
    }

    private static function isHex(string $hex): bool
    {
        return preg_match(self::HEX_REGEX, $hex) === 1;
    }

    private static function isoToBytes(string $iso): Buffer
    {
        $bytes = Buffer::alloc(20);
        if ($iso !== 'XRP') {
            $isoBytes = array_map(function ($c) {
                return ord($c);
            }, str_split($iso));
            $bytes->set(12, $isoBytes);
        }

        return $bytes;
    }

    private function isoCodeFromHex(Buffer $code): ?string
    {
        $iso = '';

        foreach ($code->toArray() as $byteVal) {
            $iso .= chr($byteVal);
        }

        if ($iso === 'XRP') {
            return null;
        }

        if ($this->isIsoCode($iso)) {
            return $iso;
        }

        return null;
    }
}
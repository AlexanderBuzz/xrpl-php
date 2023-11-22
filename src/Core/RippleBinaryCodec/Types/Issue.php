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

use Exception;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class Issue extends SerializedType
{
    protected static int $bytesLength = 20;

    /**
     *  Class for serializing/Deserializing Issues
     *
     * @param Buffer|null $bytes
     * @throws Exception
     */
    public function __construct(?Buffer $bytes = null)
    {
        if (!$bytes) {
            $bytes = Buffer::alloc(self::$bytesLength); // 8 bytes for amount, 12 bytes for currency and issuer
        }

        parent::__construct($bytes);
    }

    /**
     *  Read an issue from a BinaryParser
     *
     * @param BinaryParser $parser
     * @param int|null $lengthHint
     * @return SerializedType
     * @throws Exception
     */
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $currencyBuffer = $parser->read(self::$bytesLength);

        if ((new Currency($currencyBuffer))->toJson() === 'XRP') {
            return new Issue($currencyBuffer);
        }

        $currencyAndIssuer = [$currencyBuffer, $parser->read(20)];

        return new Issue(Buffer::concat($currencyAndIssuer));
    }

    /**
     *  Read an issue from a JSON string
     *
     * @param string $serializedJson
     * @return SerializedType
     * @throws Exception
     */
    public static function fromJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson, true);
        if (self::isIssueObject($json)) {
            $currencyBuffer = Currency::fromJson($json['currency'])->toBytes();
            if (empty($json['issuer'])) {
                return new Issue($currencyBuffer);
            }

            $issuerBuffer = AccountId::fromJson($json['issuer'])->toBytes();

            return new Issue(Buffer::concat([$currencyBuffer, $issuerBuffer]));
        }

        throw new Exception('Invalid type to construct an Issue');
    }

    /**
     * Returns the JSON representation of the Issue as a string or array
     *
     * @return string|array
     * @throws Exception
     */
    public function toJson(): string|array
    {
        $binaryParser = new BinaryParser($this->toHex());
        $currency = Currency::fromParser($binaryParser);

        if ($currency->toJson() === 'XRP') {
            return [
                'currency' => $currency->toJson()
            ];
        }

        $issuer = AccountId::fromParser($binaryParser);

        return [
            'currency' => $currency->toJson(),
            'issuer' => $issuer->toJson()
        ];
    }

    /**
     *  Type guard for Issue object
     *
     * @param array $json
     * @return bool
     */
    private static function isIssueObject(array $json): bool
    {
        $keys = array_keys($json);
        sort($keys);

        if (count($keys) === 1) {
            return $keys[0] === 'currency';
        }

        return count($keys) === 2 && $keys[0] === 'currency' && $keys[1] === 'issuer';
    }
}
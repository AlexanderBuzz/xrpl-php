<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types;

use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class Issue extends SerializedType
{
    protected static int $bytesLength = 20;

    /**
     * An MPT issue is serialized as issuer account, the reserved "no account"
     * placeholder and the little endian issuance sequence.
     */
    protected static int $mptBytesLength = 44;

    protected const NO_ACCOUNT = '0000000000000000000000000000000000000001';

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

        $issuerBuffer = $parser->read(20);

        // The "no account" placeholder in the issuer slot marks an MPT issue,
        // whose issuance sequence follows in the next four bytes.
        if (strtoupper($issuerBuffer->toString()) === self::NO_ACCOUNT) {
            return new Issue(Buffer::concat([$currencyBuffer, $issuerBuffer, $parser->read(4)]));
        }

        return new Issue(Buffer::concat([$currencyBuffer, $issuerBuffer]));
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
        if (is_array($json) && self::isIssueObject($json)) {
            if (isset($json['mpt_issuance_id'])) {
                $mptIssuanceId = Hash192::fromJson($json['mpt_issuance_id'])->toBytes();

                // The issuance sequence is big endian inside mpt_issuance_id
                // but little endian on the wire.
                $sequence = $mptIssuanceId->slice(0, 4)->toArray();
                $issuerAccount = $mptIssuanceId->slice(4);

                return new Issue(Buffer::concat([
                    $issuerAccount,
                    Buffer::from(self::NO_ACCOUNT, 'hex'),
                    Buffer::from(array_reverse($sequence))
                ]));
            }

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
        if ($this->bytes->getLength() === self::$mptBytesLength) {
            $issuerAccount = $this->bytes->slice(0, 20);
            $sequence = array_reverse($this->bytes->slice(40, 44)->toArray());

            return [
                'mpt_issuance_id' => strtoupper(
                    Buffer::concat([Buffer::from($sequence), $issuerAccount])->toString()
                )
            ];
        }

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
            return $keys[0] === 'currency' || $keys[0] === 'mpt_issuance_id';
        }

        return count($keys) === 2 && $keys[0] === 'currency' && $keys[1] === 'issuer';
    }
}
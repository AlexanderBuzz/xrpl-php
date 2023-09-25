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

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;

class PathStep extends SerializedType
{
    const TYPE_ACCOUNT = 0x01;
    const TYPE_CURRENCY = 0x10;
    const TYPE_ISSUER = 0x20;

    public function __construct(?Buffer $bytes = null)
    {
        if (is_null($bytes)) {
            $bytes = Buffer::alloc();
        }

        parent::__construct($bytes);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $type = $parser->readUInt8()->toInt();

        $bytesList = new BytesList();
        $bytesList->push(Buffer::from([$type]));

        if ($type & self::TYPE_ACCOUNT) {
            $bytesList->push($parser->read(AccountId::$width));
        }

        if ($type & self::TYPE_CURRENCY) {
            $bytesList->push($parser->read(Currency::$width));
        }

        if ($type & self::TYPE_ISSUER) {
            $bytesList->push($parser->read(AccountId::$width));
        }

        return new PathStep($bytesList->toBytes());
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson, true);

        $bytesList = new BytesList();
        $type = [0];

        if (isset($json["account"])) {
            $bytesList->push(AccountId::fromJson($json["account"])->toBytes());
            $type[0] |= self::TYPE_ACCOUNT;
        }

        if (isset($json["currency"])) {
            $bytesList->push(Currency::fromJson($json["currency"])->toBytes());
            $type[0] |= self::TYPE_CURRENCY;
        }

        if (isset($json["issuer"])) {
            $bytesList->push(AccountId::fromJson($json["issuer"])->toBytes());
            $type[0] |= self::TYPE_ISSUER;
        }

        $bytesList->prepend(Buffer::from($type));

        return new PathStep($bytesList->toBytes());
    }

    public function toJson(): array|string|int
    {
        $result = [];
        $parser = new BinaryParser($this->bytes->toString());
        $type = $parser->readUInt8()->toInt();

        if (($type & self::TYPE_ACCOUNT) > 0) {
            $result['account'] = AccountId::fromParser($parser)->toJson();
        }

        if (($type & self::TYPE_CURRENCY) > 0) {
            $result['currency'] = Currency::fromParser($parser)->toJson();
        }

        if (($type & self::TYPE_ISSUER) > 0) {
            $result['issuer'] = AccountId::fromParser($parser)->toJson();
        }

        return $result;
    }

    public static function isPathStep(array $testSubject):  bool
    {
        return (
            isset($testSubject['account']) ||
            isset($testSubject['currency']) ||
            isset($testSubject['issuer'])
        );
    }
}
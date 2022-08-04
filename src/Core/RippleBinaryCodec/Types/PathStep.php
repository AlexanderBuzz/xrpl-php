<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;
use function MongoDB\BSON\fromJSON;

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
            $bytesList->push($parser->read(20)); //TODO: AccountID::width
        }

        if ($type & self::TYPE_CURRENCY) {
            $bytesList->push($parser->read(Currency::$width));
        }

        if ($type & self::TYPE_ISSUER) {
            $bytesList->push($parser->read(20)); //TODO: AccountID::width
        }

        return new PathStep($bytesList->toBytes());
    }

    public static function fromSerializedJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson, true);

        $bytesList = new BytesList();
        $bytesList->push(Buffer::from([0]));

        if (isset($json["account"])) {
            $bytesList[] = AccountId::fromSerializedJson($serializedJson);
        }

        if (isset($json["currency"])) {

        }

        if (isset($json["issuer"])) {

        }

        return new PathStep($bytesList->toBytes());
    }

    public function toJson(): array|string|int
    {
        $parser = new BinaryParser($this->bytes->toString());
        $type = $parser->readUInt8()->toInt();

        return '';
    }
}
<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;
use function MongoDB\BSON\fromJSON;

class Path extends SerializedType
{
    public function __construct(?Buffer $bytes = null)
    {
        if (is_null($bytes)) {
            $bytes = Buffer::alloc();
        }

        parent::__construct($bytes);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bytesList = new BytesList();

        while (!$parser->end()) {
            $bytesList->push(PathStep::fromParser($parser)->toBytes());

            if (
                $parser->end() || $parser->peek() === PathSet::PATHSET_END_BYTE || $parser->peek() === PathSet::PATH_SEPARATOR_BYTE
            ) {
                break;
            }
        }
        return new Path($bytesList->toBytes());
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson, true);

        $bytesList = new BytesList();

        foreach ($json as $step) {
            $serializedJson = json_encode($step);
            $bytesList->push(PathStep::fromJson($serializedJson)->toBytes());
        }

        return new Path($bytesList->toBytes());
    }

    public function toJson(): array|string|int
    {
        $result = [];
        $parser = new BinaryParser($this->bytes->toString());

        while (!$parser->end()) {
            $result[] = PathStep::fromParser($parser)->toJson();
        }

        return $result;
    }
}
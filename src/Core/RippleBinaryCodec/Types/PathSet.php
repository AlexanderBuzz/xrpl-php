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

class PathSet extends SerializedType
{
    public const PATHSET_END_BYTE = 0x00;
    public const PATH_SEPARATOR_BYTE = 0xff;

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
            $bytesList->push(Path::fromParser($parser)->toBytes());
            $bytesList->push($parser->read(1));

            if ($bytesList->deepGrab($bytesList->getLength()-1, 0) === self::PATHSET_END_BYTE) {
                break;
            }
        }

        return new PathSet($bytesList->toBytes());
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson, true);

        $bytesList = new BytesList();

        if (self::isPathSet($json)) {
            foreach ($json as $path) {
                $serializedJson = json_encode($path);
                $bytesList->push(Path::fromJson($serializedJson)->toBytes());
                $bytesList->push(Buffer::from([self::PATH_SEPARATOR_BYTE]));
            }
        }

        $bytesList->replace($bytesList->getLength()-1, Buffer::from([self::PATHSET_END_BYTE]));

        return new PathSet($bytesList->toBytes());
    }

    public function toJson(): array|string|int
    {
        $result = [];
        $parser = new BinaryParser($this->bytes->toString());

        while (!$parser->end()) {
            $result[] = Path::fromParser($parser)->toJson();
            $parser->skip(1);
        }

        return $result;
    }

    public static function isPathSet(mixed $testSubject): bool
    {
        return (
            (is_array($testSubject) && count($testSubject) === 0) ||
            (is_array($testSubject) && is_array($testSubject[0]) && count($testSubject[0]) === 0) ||
            (is_array($testSubject) && is_array($testSubject[0]) && PathStep::isPathStep($testSubject[0][0]))
        );
    }
}
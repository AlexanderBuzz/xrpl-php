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
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;

class Path extends SerializedType
{
    public function __construct(?Buffer $bytes = null)
    {
        if (is_null($bytes)) {
            $bytes = Buffer::alloc();
        }

        parent::__construct($bytes);
    }

    /**
     *
     *
     * @param BinaryParser $parser
     * @param int|null $lengthHint
     * @return SerializedType
     * @throws Exception
     */
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

    /**
     *
     *
     * @param string $serializedJson
     * @return SerializedType
     */
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

    /**
     *
     *
     * @return array|string|int
     * @throws Exception
     */
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
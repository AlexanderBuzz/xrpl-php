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

class XchainBridge extends SerializedType
{
    public const TYPE_ORDER = [
        [ 'name' => 'LockingChainDoor', 'type' =>  AccountId::class ],
        [ 'name' => 'LockingChainIssue', 'type' => Issue::class ],
        [ 'name' => 'IssuingChainDoor', 'type' => AccountId::class ],
        [ 'name' => 'IssuingChainIssue', 'type' => Issue::class ]
    ];
    /**
     *  Class for serializing/Deserializing a cross-chain bridge
     *
     * @param Buffer|null $bytes
     * @throws Exception
     */
    public function __construct(?Buffer $bytes = null)
    {
        if (!$bytes) {
            $bytes = Buffer::concat([
                Buffer::from([0x14]),
                Buffer::alloc(40),
                Buffer::from([0x14]),
                Buffer::from([0x14])
            ]);
        }

        parent::__construct($bytes);
    }

    /**
     *  Construct a cross-chain bridge from a BinaryParser
     *
     * @param BinaryParser $parser
     * @param int|null $lengthHint
     * @return SerializedType
     * @throws Exception
     */
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bufferArray = [];
        foreach (self::TYPE_ORDER as $item) {
            [$type] = [$item['type']];
            if ($type === AccountId::class) {
                $parser->skip(1);
                $bufferArray[] = Buffer::from([0x14]);
            }
            $object = call_user_func($type .'::fromParser', $parser);
            $bufferArray[] = $object->toBytes();
        }

        return new XchainBridge(Buffer::concat($bufferArray));
    }

    /**
     *  Construct a cross-chain bridge from a JSON string
     *
     * @param string $serializedJson
     * @return SerializedType
     * @throws Exception
     */
    public static function fromJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson, true);

        if (!self::isXchainBridgeObject($json)) {
            throw new Exception('Invalid type to construct an XChainBridge');
        }

        $bufferArray = [];
        foreach (self::TYPE_ORDER as $item) {
            [$name, $type] = [$item['name'], $item['type']];
            if ($type === AccountId::class) {
                $bufferArray[] = Buffer::from([0x14]);
            }
            $object = call_user_func($type . '::fromJson', is_string($json[$name]) ? $json[$name] : json_encode($json[$name]));
            $bufferArray[] = $object->toBytes();
        }

        return new XchainBridge(Buffer::concat($bufferArray));
    }

    /**
     *  Returns the JSON representation of this XChainBridge as an array
     *
     * @return string|array
     * @throws Exception
     */
    public function toJson(): string|array
    {
        $parser = new BinaryParser($this->toHex());
        $json = [];
        foreach (self::TYPE_ORDER as $item) {
            [$name, $type] = [$item['name'], $item['type']];
            if ($type === AccountId::class) {
                $parser->skip(1);
            }
            $object = call_user_func($type .'::fromParser', $parser)->toJson();
            $json[$name] = $object;
        }

        return $json;
    }

    /**
     *  Type guard for XchainBridge object
     *
     * @param array $json
     * @return bool
     */
    private static function isXchainBridgeObject(array $json): bool
    {
        $keys = array_keys($json);
        sort($keys);

        return (
            count($keys) === 4 &&
            $keys[0] === 'IssuingChainDoor' &&
            $keys[1] === 'IssuingChainIssue' &&
            $keys[2] === 'LockingChainDoor' &&
            $keys[3] === 'LockingChainIssue'
        );
    }
}
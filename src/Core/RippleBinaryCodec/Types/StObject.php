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
use Hardcastle\XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinarySerializer;
use Hardcastle\XRPL_PHP\Core\CoreUtilities;

class StObject extends SerializedType
{
    public const OBJECT_END_MARKER_HEX = "E1";

    public const OBJECT_END_MARKER = "ObjectEndMarker";

    public const ST_OBJECT = "STObject";

    public const DESTINATION = 'Destination';

    public const ACCOUNT = 'ACCOUNT';

    public const SOURCE_TAG = 'SourceTag';

    public const DESTINATION_TAG = 'DestinationTag';

    /**
     * Set when the object was parsed, so that toJson() resolves field names
     * against the same definitions the parser used.
     */
    protected ?Definitions $definitions = null;


    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $binarySerializer = new BinarySerializer(Buffer::alloc(0));

        while (!$parser->end()) {
            $field = $parser->readField();
            if ($field->getName() === self::OBJECT_END_MARKER) {
                break;
            }

            $associatedValue = $parser->readFieldValue($field);
            $binarySerializer->writeFieldAndValue($field, $associatedValue);

            if ($field->getType() === self::ST_OBJECT) {
                $binarySerializer->put(self::OBJECT_END_MARKER_HEX);
            }
        }

        $object = new StObject($binarySerializer->getBytes());
        $object->definitions = $parser->getDefinitions();

        return $object;
    }

    public static function fromJson(string $serializedJson, ?Definitions $definitions = null): SerializedType
    {
        $json = json_decode($serializedJson, true);
        $binarySerializer = new BinarySerializer(Buffer::alloc(0));
        $definitions ??= Definitions::getInstance();

        $isUnlModify = false;

        //xAddressDecoded ->
        $xAddressDecoded = [];
        foreach ($json as $key => $value) {
            //if ($value && CoreUtilities::isValidXAddress($value)) {
            //    $handled = self::handleXAddress($key, $value);
            //    //checkForDuplicateTags
            //}
        }

        //sort
        uksort($json, function ($a, $b) use ($definitions) {
            $fieldInstanceA = $definitions->getFieldInstance($a);
            $fieldInstanceB = $definitions->getFieldInstance($b);

            return $fieldInstanceA->getOrdinal() - $fieldInstanceB->getOrdinal();
        });

        //TODO: xrpl.js uses filters here, do we need them?

        foreach ($json as $key => $value) {
            $fieldInstance = $definitions->getFieldInstance($key);

            if (is_array($value)) {
                $value = json_encode($value);
            } else if (is_string($value)) {
                $value = $definitions->mapSpecificFieldFromValue($key, $value);
            }

            $serializedTypeInstance = SerializedType::getTypeByName($fieldInstance->getType());

            if ($serializedTypeInstance instanceof UnsignedInt64 && UnsignedInt64::isBase10Field($key)) {
                $fieldValue = UnsignedInt64::fromBase10((string)$value);
            } else if ($serializedTypeInstance instanceof StObject || $serializedTypeInstance instanceof StArray) {
                // Only these two resolve field names, so only they need to know
                // which network's definitions are in play.
                $fieldValue = $serializedTypeInstance::fromJson($value, $definitions);
            } else {
                $fieldValue = $serializedTypeInstance::fromJson($value);
            }

            $binarySerializer->writeFieldAndValue($fieldInstance, $fieldValue);
        }

        if (isset($fieldInstance) && $fieldInstance->getType() === self::ST_OBJECT) {
            $binarySerializer->put(self::OBJECT_END_MARKER_HEX);
        }

        return new StObject($binarySerializer->getBytes());
    }

    public function toJson(): array|string
    {
        $definitions = $this->definitions ?? Definitions::getInstance();
        $binaryParser = new BinaryParser($this->bytes->toString(), $definitions);
        $accumulator = [];

        while (!$binaryParser->end()) {
            $fieldInstance = $binaryParser->readField();
            if ($fieldInstance->getName() === self::OBJECT_END_MARKER) {
                break;
            }

            $fieldValue = $binaryParser->readFieldValue($fieldInstance);

            if ($fieldValue instanceof UnsignedInt64 && UnsignedInt64::isBase10Field($fieldInstance->getName())) {
                $accumulator[$fieldInstance->getName()] = $fieldValue->toBase10();
                continue;
            }

            $node = $fieldValue->toJson();
            if(is_array($node)) {
                $accumulator[$fieldInstance->getName()] = $node;
            } else {
                $mappedNode = $definitions->mapValueToSpecificField($fieldInstance->getName(), $node);
                $accumulator[$fieldInstance->getName()] = (!empty($mappedNode)) ? $mappedNode : $node;
            }
        }

        return $accumulator;
    }

    /**
     * Break down an X-Address into an account and a tag
     *
     * @param string $field
     * @param string $xAddress
     * @return array
     * @throws Exception
     */
    private static function handleXAddress(string $field, string $xAddress): array
    {
        $decoded = CoreUtilities::xAddressToClassicAddress($xAddress);

        if ($field === self::DESTINATION) {
            $tagName = self::DESTINATION_TAG;
        } else if ($field === self::ACCOUNT) {
            $tagName = self::SOURCE_TAG;
        } else if (isset($decoded['tag'])) {
            throw new Exception($field . ' cannot have an associated tag');
        }

        return (isset($decoded['tag'])) ? [
            $field => $decoded['classicAddress'],
            $tagName => $decoded['tag']
        ] : [
            $field => $decoded['classicAddress']
        ];
    }

}
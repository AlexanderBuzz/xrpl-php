<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/**
 * A collection of serialization information about a specific field type.
 */
class FieldInstance
{
    private int $nth;

    private bool $isVariableLengthEncoded;

    private bool $isSerialized;

    private bool $isSigningField;

    private string $type;

    private string $name;

    private FieldHeader $fieldHeader;

    private int $ordinal;

    private string $associatedType;

    public function __construct(FieldInfo $fieldInfo, string $fieldName, FieldHeader $fieldHeader)
    {
        $this->nth = $fieldInfo->getNth();
        $this->isVariableLengthEncoded = $fieldInfo->isVariableLengthEncoded();
        $this->isSerialized = $fieldInfo->isSerialized();
        $this->isSigningField = $fieldInfo->isSigningField();
        $this->type = $fieldInfo->getType();
        $this->name = $fieldName;
        $this->fieldHeader = $fieldHeader;
        $this->ordinal = $this->fieldHeader->getTypeCode() << 16 | $this->nth;
        $this->associatedType = $this->getTypeByName($this->type);
    }

    private function getTypeByName(string $name): string
    {
        $typeMap = [
            //"AccountID" => AccountID,
            //"Amount" => Amount,
            //"Blob" => Blob,
            //"Currency" => Currency,
            //"Hash128" => Hash128,
            //"Hash160" => Hash160,
            //"Hash256" => Hash256,
            //"PathSet" => PathSet,
            "STArray" => StArray::class,
            //"STObject" => SerializedDict,
            //"UInt8" => UInt8,
            //"UInt16" => UInt16,
            //"UInt32" => UInt32,
            //"UInt64" => UInt64,
            //"Vector256" => Vector256,
        ];

        return $typeMap[$name];
    }


}
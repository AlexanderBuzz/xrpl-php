<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;

/**
 * A collection of serialization information about a specific field type.
 */
class FieldInstance
{
    private readonly int $nth;

    private readonly bool $isVariableLengthEncoded;

    private readonly bool $isSerialized;

    private readonly bool $isSigningField;

    private readonly string $type;

    private readonly int $ordinal;

    private readonly string $associatedType;

    /**
     *
     *
     * @param FieldInfo $fieldInfo
     * @param string $name
     * @param FieldHeader $fieldHeader
     * @throws \Exception
     */
    public function __construct(FieldInfo $fieldInfo, private readonly string $name, private readonly FieldHeader $fieldHeader)
    {
        $this->nth = $fieldInfo->getNth();
        $this->isVariableLengthEncoded = $fieldInfo->isVariableLengthEncoded();
        $this->isSerialized = $fieldInfo->isSerialized();
        $this->isSigningField = $fieldInfo->isSigningField();
        $this->type = $fieldInfo->getType();
        $this->ordinal = $this->fieldHeader->getTypeCode() << 16 | $this->nth;
        $this->associatedType = SerializedType::getTypeByName($this->type)::class;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHeader(): FieldHeader
    {
        return $this->fieldHeader;
    }

    public function isVariableLengthEncoded(): bool
    {
        return $this->isVariableLengthEncoded;
    }

    public function isSigningField(): bool
    {
        return $this->isSigningField;
    }

    public function getOrdinal(): int
    {
        return $this->ordinal;
    }

    /**
     * Build the ordinal the codec sorts fields by, which is the type code followed
     * by the field code.
     */
    public function buildField(string $name, FieldInfo $fieldInfo, int $typeOrdinal): FieldInstance
    {

    }
}
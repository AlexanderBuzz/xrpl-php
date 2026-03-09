<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions;

/**
 * Model object for field info from the "fields" section of definitions.json.
 */
class FieldInfo
{
    public function __construct(private int    $nth, private bool   $isVariableLengthEncoded, private bool   $isSerialized, private bool   $isSigningField, private string $type)
    {
    }

    /**
     * @return int
     */
    public function getNth(): int
    {
        return $this->nth;
    }

    /**
     * @param int $nth
     */
    public function setNth(int $nth): void
    {
        $this->nth = $nth;
    }

    /**
     * @return bool
     */
    public function isVariableLengthEncoded(): bool
    {
        return $this->isVariableLengthEncoded;
    }

    /**
     * @param bool $isVariableLengthEncoded
     */
    public function setIsVariableLengthEncoded(bool $isVariableLengthEncoded): void
    {
        $this->isVariableLengthEncoded = $isVariableLengthEncoded;
    }

    /**
     * @return bool
     */
    public function isSerialized(): bool
    {
        return $this->isSerialized;
    }

    /**
     * @param bool $isSerialized
     */
    public function setIsSerialized(bool $isSerialized): void
    {
        $this->isSerialized = $isSerialized;
    }

    /**
     * @return bool
     */
    public function isSigningField(): bool
    {
        return $this->isSigningField;
    }

    /**
     * @param bool $isSigningField
     */
    public function setIsSigningField(bool $isSigningField): void
    {
        $this->isSigningField = $isSigningField;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
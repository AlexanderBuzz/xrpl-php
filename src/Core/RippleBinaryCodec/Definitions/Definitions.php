<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

class Definitions
{
    public static ?Definitions $instance = null;

    public array $definitionsMap = [];

    public function __construct()
    {

    }

    public static function getInstance(): Definitions
    {
        if(static::$instance === null) {
            static::$instance = new Definitions();
        }

        return static::$instance;
    }

    public function getFieldHeaderFromName(string $fieldName): FieldHeader
    {
        //return fieldIdNameMap.get(fieldHeader);
    }

    public function getFieldNameFromHeader(FieldHeader $fieldHeader): string
    {
        //return fieldIdNameMap.get(fieldHeader);
    }

    public function getFieldHeader(string $fieldName): FieldHeader
    {

    }

    public function getFieldInstance(string $fieldName): FieldInstance
    {
        $info = $this->definitionsMap[$fieldName];
        $fieldHeader = $this->getFieldHeaderFromName($fieldName);

        return new FieldInstance($info, $fieldName, $fieldHeader);
    }
}
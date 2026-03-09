<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Exception;

class Definitions
{
    public static ?Definitions $instance = null;

    private array $definitions = [];

    private array $typeOrdinals = [];

    private array $fieldHeaderMap = [];

    private array $ledgerEntryTypes = [];

    private array $transactionResults = [];

    private array $transactionTypes = [];

    private array $fieldInfoMap = [];

    private array $fieldIdNameMap = [];

    /**
     * Definitions constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $path = getenv('XRPL_PHP_DEFINITIONS_FILE_PATH') ?: dirname(__FILE__) . "/definitions.json";
        if (file_exists($path)) {
            $fileContents = file_get_contents($path);
        } else {
            throw new Exception("Definitions file not found.");
        }

        $this->definitions = json_decode($fileContents, true);

        $hooksPath = dirname(__FILE__) . "/../../../Hooks/hooksDefinitions.json";
        if (file_exists($hooksPath)) {
            $hooksDefinitions = json_decode(file_get_contents($hooksPath), true);
            $this->definitions['TYPES'] = array_merge($this->definitions['TYPES'], $hooksDefinitions['TYPES']);
            $this->definitions['LEDGER_ENTRY_TYPES'] = array_merge($this->definitions['LEDGER_ENTRY_TYPES'], $hooksDefinitions['LEDGER_ENTRY_TYPES']);
            $this->definitions['TRANSACTION_RESULTS'] = array_merge($this->definitions['TRANSACTION_RESULTS'], $hooksDefinitions['TRANSACTION_RESULTS'] ?? []);
            $this->definitions['TRANSACTION_TYPES'] = array_merge($this->definitions['TRANSACTION_TYPES'], $hooksDefinitions['TRANSACTION_TYPES'] ?? []);
            $this->definitions['FIELDS'] = array_merge($this->definitions['FIELDS'], $hooksDefinitions['FIELDS']);
        }

        $this->typeOrdinals = $this->definitions['TYPES'];
        $this->ledgerEntryTypes = $this->definitions['LEDGER_ENTRY_TYPES'];
        $this->transactionResults = $this->definitions['TRANSACTION_RESULTS'];
        $this->transactionTypes = $this->definitions['TRANSACTION_TYPES'];

        foreach ($this->definitions['FIELDS'] as $field) {
            $fieldName = $field[0];
            $fieldInfo = new FieldInfo(
                $field[1]["nth"],
                $field[1]["isVLEncoded"],
                $field[1]["isSerialized"],
                $field[1]["isSigningField"],
                $field[1]["type"],
            );
            $fieldHeader = new FieldHeader($this->typeOrdinals[$fieldInfo->getType()], $fieldInfo->getNth());

            $this->fieldInfoMap[$fieldName] = $fieldInfo;
            $this->fieldIdNameMap[$fieldHeader->getTypeCode() . ":" . $fieldHeader->getFieldCode()] = $fieldName;
            $this->fieldHeaderMap[$fieldName] = $fieldHeader;
        }
    }

    public static function getInstance(): Definitions
    {
        if (static::$instance === null) {
            static::$instance = new Definitions();
        }

        return static::$instance;
    }

    public function getFieldHeaderFromName(string $fieldName): FieldHeader
    {
        if (!isset($this->fieldHeaderMap[$fieldName])) {
            throw new Exception("Field $fieldName not found in definitions.");
        }

        return $this->fieldHeaderMap[$fieldName];
    }

    public function getFieldNameFromHeader(FieldHeader $fieldHeader): string
    {
        $key = $fieldHeader->getTypeCode() . ":" . $fieldHeader->getFieldCode();

        if (!isset($this->fieldIdNameMap[$key])) {
            throw new Exception("Field with header $key not found in definitions.");
        }

        return $this->fieldIdNameMap[$key];
    }

    public function getFieldInstance(string $fieldName): FieldInstance
    {
        $fieldInfo = $this->fieldInfoMap[$fieldName];
        $fieldHeader = $this->getFieldHeaderFromName($fieldName);

        return new FieldInstance($fieldInfo, $fieldName, $fieldHeader);
    }

    public function mapSpecificFieldFromValue(string $fieldName, string $value): int|string
    {
        switch ($fieldName) {
            case "LedgerEntryType":
                $lookup = $this->ledgerEntryTypes;
                break;
            case "TransactionResult":
                $lookup = $this->transactionResults;
                break;
            case "TransactionType":
                $lookup = $this->transactionTypes;
                break;
            default:
                return $value;
        }

        //TODO: In case the value is not found, should an exception be thrown?
        return (isset($lookup[$value])) ? $lookup[$value] : $value;
    }

    public function mapValueToSpecificField(string $fieldName, string|int $value): string
    {
        switch ($fieldName) {
            case "LedgerEntryType":
                $lookup = array_flip($this->ledgerEntryTypes);
                break;
            case "TransactionResult":
                $lookup = array_flip($this->transactionResults);
                break;
            case "TransactionType":
                $lookup = array_flip($this->transactionTypes);
                break;
            default:
                return "";
        }

        return (isset($lookup[(int)$value])) ? $lookup[(int)$value] : "";
    }
}
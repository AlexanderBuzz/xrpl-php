<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

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

    public function __construct()
    {
        $path = dirname(__FILE__) . "/definitions.json";
        $this->definitions = json_decode(file_get_contents($path), true);

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
            $this->fieldIdNameMap[md5(serialize($fieldHeader))] = $fieldName;
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
        return $this->fieldHeaderMap[$fieldName];
    }

    public function getFieldNameFromHeader(FieldHeader $fieldHeader): string
    {
        return $this->fieldIdNameMap[md5(serialize($fieldHeader))];
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
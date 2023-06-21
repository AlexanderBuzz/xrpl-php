<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Ledger;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/ledger_entry.html
 */
class LedgerEntryRequest extends BaseRequest
{
    protected string $command = "ledger_entry";

    //Todo: this is a rather complex method, constructor won't be enough, see Ledger Object Types
    public function __construct(
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null, //is an Object in https://github.com/XRPLF/xrpl.js/blob/develop/packages/xrpl/src/models/methods/ledgerData.ts
        protected ?bool $binary = null,
        protected ?int $limit = null,
        protected mixed $marker = null
    ) {}
}
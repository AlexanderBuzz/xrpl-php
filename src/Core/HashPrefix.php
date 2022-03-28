<?php

namespace XRPL_PHP\Core;

class HashPrefix {

    // transaction plus signature to give transaction ID 'TXN'
    const TRANSACTION_ID = "0x54584e00";

    // transaction plus metadata 'TND'
    const TRANSACTION_NODE = "0x534e4400";

    // inner node in tree 'MIN'
    const INNER_NODE = "0x4d494e00";

    // leaf node in tree 'MLN'
    const LEAF_NODE = "0x4d4c4e00";

    // inner transaction to sign 'STX'
    const TRANSACTION_SIGN = "0x53545800";

    // inner transaction to sign (TESTNET) 'stx'
    const TRANSACTION_SIGN_TESTNET = "0x73747800";

    // inner transaction to multisign 'SMT'
    const TRANSACTION_MULTISIGN = "0x534d5400";

    // ledger 'LWR'
    const LEDGER = "0x4c575200";
}
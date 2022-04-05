<?php declare(strict_types = 1);

namespace XRPL_PHP\Core\RippleAddressCodec;

class Utils
{
    const XRPL_ALPHABET = "rpshnaf39wBUDNEGHJKLM4PQRST7VWXYZ2bcdeCg65jkm8oFqi1tuvAxyz";

    public function seqEqual(array $a1, array $a2): bool
    {
        return empty(array_diff_assoc($a1, $a2));
    }

    public function concatArgs()
    {

    }

    private function isSequence()
    {

    }
}
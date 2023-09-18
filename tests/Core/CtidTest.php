<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\Ctid;

final class CtidTest extends TestCase
{
    public function testDecode(): void
    {
        $ctid = Ctid::fromCtid('C3B3567200190001');

        $this->assertEquals( 1, $ctid->getNetworkId());
        $this->assertEquals(62084722, $ctid->getLedgerIndex());
        $this->assertEquals(25, $ctid->getTransactionIndex());

        $ctid = Ctid::fromCtid('C0CA2AA7326FC045');

        $this->assertEquals( 49221, $ctid->getNetworkId());
        $this->assertEquals(13249191, $ctid->getLedgerIndex());
        $this->assertEquals(12911, $ctid->getTransactionIndex());

        $ctid = Ctid::fromCtid('C000000000000000');

        $this->assertEquals( 0, $ctid->getNetworkId());
        $this->assertEquals(0, $ctid->getLedgerIndex());
        $this->assertEquals(0, $ctid->getTransactionIndex());

        $ctid = Ctid::fromCtid('C000000100020003');

        $this->assertEquals( 3, $ctid->getNetworkId());
        $this->assertEquals(1, $ctid->getLedgerIndex());
        $this->assertEquals(2, $ctid->getTransactionIndex());

        $ctid = Ctid::fromCtid('CFFFFFFFFFFFFFFF');

        $this->assertEquals( 0xFFFF, $ctid->getNetworkId());
        $this->assertEquals(0xFFFFFFF, $ctid->getLedgerIndex());
        $this->assertEquals(0xFFFF, $ctid->getTransactionIndex());

    }

    public function testEncode(): void
    {
        $raw = [
            'networkId' => 1,
            'ledgerIndex' => 62084722,
            'transactionIndex' => 25,
        ];
        $ctid = Ctid::fromRawValues($raw['ledgerIndex'], $raw['transactionIndex'], $raw['networkId']);
        $this->assertEquals('C3B3567200190001', $ctid->getHex());

        $raw = [
            'networkId' => 49221,
            'ledgerIndex' => 13249191,
            'transactionIndex' => 12911,
        ];
        $ctid = Ctid::fromRawValues($raw['ledgerIndex'], $raw['transactionIndex'], $raw['networkId']);
        $this->assertEquals('C0CA2AA7326FC045', $ctid->getHex());

        $raw = [
            'networkId' => 0,
            'ledgerIndex' => 0,
            'transactionIndex' => 0,
        ];
        $ctid = Ctid::fromRawValues($raw['ledgerIndex'], $raw['transactionIndex'], $raw['networkId']);
        $this->assertEquals('C000000000000000', $ctid->getHex());

        $raw = [
            'networkId' => 3,
            'ledgerIndex' => 1,
            'transactionIndex' => 2,
        ];
        $ctid = Ctid::fromRawValues($raw['ledgerIndex'], $raw['transactionIndex'], $raw['networkId']);
        $this->assertEquals('C000000100020003', $ctid->getHex());

        $raw = [
            'networkId' => 0xFFFF,
            'ledgerIndex' => 0xFFFFFFF,
            'transactionIndex' => 0xFFFF,
        ];
        $ctid = Ctid::fromRawValues($raw['ledgerIndex'], $raw['transactionIndex'], $raw['networkId']);
        $this->assertEquals('CFFFFFFFFFFFFFFF', $ctid->getHex());
    }
}
<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Models\Transaction;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction;

final class BaseTransactionTest extends TestCase
{
    public function testConstructor()
    {
        $mock = $this->getMockForAbstractClass(
            BaseTransaction::class,
            ['fields' => [
                'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
                'TransactionType' => 'Payment',
                'TxnSignature' => 'some-filler-string'
            ]]
        );

        $this->assertTrue($mock->offsetExists('baseProperties'));
        $this->assertTrue($mock->offsetExists('transactionTypeProperties'));

        $this->assertTrue($mock->offsetExists('Account'));
        $this->assertTrue($mock->offsetExists('TransactionType'));
        $this->assertTrue($mock->offsetExists('TxnSignature'));
    }

    public function testFromArray()
    {
        $mock = $this->getMockForAbstractClass(
            BaseTransaction::class,
            ['fields' => []]
        );

        $tx = [
            'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
            'TransactionType' => 'Payment',
            'TxnSignature' => 'some-filler-string'
        ];

        $mock->fromArray($tx);

        $this->assertEquals('rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe', $mock['Account']);
        $this->assertEquals('Payment', $mock['TransactionType']);
        $this->assertEquals('some-filler-string', $mock['TxnSignature']);
    }

    public function testToArray()
    {
        $tx = [
            'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
            'TransactionType' => 'Payment',
            'TxnSignature' => 'some-filler-string'
        ];

        $mock = $this->getMockForAbstractClass(
            BaseTransaction::class,
            ['fields' => $tx]
        );

        $this->assertEquals($tx, $mock->toArray());

    }
}
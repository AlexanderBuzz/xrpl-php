<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Models\Transaction;

use Exception;
use PHPUnit\Framework\TestCase;
use XRPL_PHP\Models\Transaction\TransactionTypes\Payment;

final class BaseTransactionTest extends TestCase
{
    public function testConstructor(): void
    {
        $properties = [
            'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
            'TransactionType' => 'Payment',
            'TxnSignature' => 'some-filler-string'
        ];

        $paymentTransaction = new Payment($properties);

        $this->assertTrue($paymentTransaction->offsetExists('baseProperties'));
        $this->assertTrue($paymentTransaction->offsetExists('transactionTypeProperties'));

        $this->assertTrue($paymentTransaction->offsetExists('Account'));
        $this->assertTrue($paymentTransaction->offsetExists('TransactionType'));
        $this->assertTrue($paymentTransaction->offsetExists('TxnSignature'));
    }

    public function testToArray(): void
    {
        $tx = [
            'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
            'TransactionType' => 'Payment',
            'TxnSignature' => 'some-filler-string'
        ];
        $paymentTransaction = new Payment($tx);

        $this->assertEquals($tx, $paymentTransaction->toArray());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Wrong TransactionType for class Payment: AccountSet");

        $txWrongType = [
            'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
            'TransactionType' => 'AccountSet',
            'TxnSignature' => 'some-filler-string',
            'Foo' => 'Bar'
        ];
        $paymentTransaction = new Payment($txWrongType);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Property Foo does not exist in Payment");

        $txWrongProperty = [
            'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
            'TransactionType' => 'Payment',
            'TxnSignature' => 'some-filler-string'
        ];
        $paymentTransaction = new Payment($txWrongProperty);
    }
}
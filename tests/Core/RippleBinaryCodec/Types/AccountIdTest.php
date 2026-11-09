<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use Exception;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/AccountIdTypeTest.java
 *
 * XRPL.JS
 *
 */
final class AccountIdTest extends TestCase
{
    private string $json = "r9cZA1mLK5R5Am25ArfXFmqgNwjZgnfk59";

    private string $hex = "5E7B112523F68D2F5E879DB4EAC51C6698A69304";

   public function testDecode(): void
   {
       $this->assertEquals(
           $this->json,
           AccountId::fromHex($this->hex)->toJson()
       );
   }

    public function testEncode(): void
    {
        $this->assertEquals(
            $this->hex,
            AccountId::fromJson($this->json)->toHex()
        );
    }

    /** rippled's STAccount::add writes an unset account as an empty field. */
    public function testAnUnsetFieldIsEmptyOnTheWireAndInJson(): void
    {
        $tx = ['TransactionType' => 'AccountSet', 'Account' => '', 'Fee' => '0', 'Sequence' => 0, 'SigningPubKey' => ''];
        $codec = new BinaryCodec();

        $hex = $codec->encode($tx);

        $this->assertStringEndsWith('8100', $hex);
        $this->assertEquals($tx, $codec->decode($hex));
        $this->assertSame($hex, $codec->encode($codec->decode($hex)));
    }

    public function testAnUnsetFieldInsideAnArrayStaysEmpty(): void
    {
        $tx = [
            'TransactionType' => 'AccountSet',
            'Account' => $this->json,
            'Signers' => [['Signer' => ['Account' => '', 'SigningPubKey' => 'AB', 'TxnSignature' => 'CD']]],
        ];
        $codec = new BinaryCodec();

        $this->assertEquals($tx, $codec->decode($codec->encode($tx)));
    }

    /** The zero account is an account: pseudo transactions on the XRP Ledger use it. */
    public function testTheZeroAccountKeepsItsTwentyBytes(): void
    {
        $tx = ['TransactionType' => 'AccountSet', 'Account' => 'rrrrrrrrrrrrrrrrrrrrrhoLvTp', 'Fee' => '0', 'Sequence' => 0, 'SigningPubKey' => ''];
        $codec = new BinaryCodec();

        $hex = $codec->encode($tx);

        $this->assertStringEndsWith('8114' . str_repeat('00', 20), $hex);
        $this->assertEquals($tx, $codec->decode($hex));
    }

    public function testANewAccountIdIsTheZeroAccountNotAnUnsetOne(): void
    {
        $account = new AccountId();

        $this->assertFalse($account->isDefault());
        $this->assertSame('rrrrrrrrrrrrrrrrrrrrrhoLvTp', $account->toJson());
    }

    /** An issuer or a path step has no length prefix to carry "unset". */
    public function testAnUnsetAccountStaysTwentyBytesWhereTheWidthIsFixed(): void
    {
        $this->assertSame(20, AccountId::fromJson('')->toBytes()->getLength());
    }

    public function testAFieldLengthOtherThanZeroOrTwentyIsRefused(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid AccountID length 5');

        AccountId::fromParser(new BinaryParser('0102030405'), 5);
    }

    public function testAnAccountWithoutALengthPrefixStillReadsTwentyBytes(): void
    {
        $this->assertSame($this->json, AccountId::fromParser(new BinaryParser($this->hex))->toJson());
    }
}
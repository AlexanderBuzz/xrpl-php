<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Client;

use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\Autofiller;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Core\CoreUtilities;

/**
 * setValidAddresses() turns X-addresses into classic ones, without a node.
 */
final class AutofillerAddressTest extends TestCase
{
    private const ISAAC = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    private const ALICE = 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf';

    public function testADelegateGivenAsAnXAddressBecomesClassic(): void
    {
        $tx = [
            'TransactionType' => 'Payment',
            'Account' => self::ISAAC,
            'Delegate' => CoreUtilities::classicAddressToXAddress(self::ALICE, 7, false),
            'Destination' => self::ISAAC,
            'Amount' => '1000',
        ];

        (new Autofiller(new JsonRpcClient('http://localhost:1')))->setValidAddresses($tx);

        $this->assertSame(self::ALICE, $tx['Delegate']);
    }
}

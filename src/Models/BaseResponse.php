<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models;

abstract class BaseResponse
{
    protected int|string|null $id = null;

    protected array $result = [];

    protected ?array $warnings = null;

    protected string $status = 'success';

    protected string $type = 'response';

    //protected bool $forwarded;

    //protected float $number;

    public function __construct(
        //int|string|null $id = null,
        array $responsePayload
    ) {
        $this->result = $responsePayload['result'];

        if (isset($responsePayload['warnings'])) {
            $this->warnings = $responsePayload['warnings'];
        }
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function getStatus(): array
    {
        return $this->result['status'];
    }

    public function getWarnings(): array|null
    {
        return $this->warnings;
    }
}
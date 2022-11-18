<?php

namespace XRPL_PHP\Models;

abstract class BaseResponse
{
    protected int|string|null $id = null;

    //protected string $status = 'success';

    //protected string $type = 'response';

    protected array $result = [];

    //protected string $warning = 'load';

    //protected bool $forwarded;

    //protected float $number;

    public function __construct(
        //int|string|null $id = null,
        array $responsePayload
    ) {
        $this->result = $responsePayload['result'];
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function getStatus(): array
    {
        return $this->result['status'];
    }
}
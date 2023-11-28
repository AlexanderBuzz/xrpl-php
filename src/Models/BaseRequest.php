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

abstract class BaseRequest
{
    protected string $command;

    public function getBody(): array
    {
        $params = [];
        foreach (get_object_vars($this) as $propertyName => $propertyValue) {
            //use only parameters that are set; 'command' is not part of params in JSON_RPC
            //TODO: check psalm handling of variable type
            if (!is_null($propertyValue) && $propertyName !== 'command') {
                $propertyName = $this->convertToSnakeCase($propertyName);
                $params[$propertyName] = (!is_object($propertyValue)) ? $propertyValue : $propertyValue->toValue();
            }
        }

        //TODO: param id seems not to be used in JSON-RPC

        if (count($params) === 0) {
            return [
                'method' => $this->command
            ];
        }

        return [
            'method' => $this->command,
            'params' => [$params]
        ];
    }

    public function getJson(): string
    {
        return json_encode($this->getBody());
    }

    public function fromArray(array $body): void
    {
        //Perhaps we want to create this thing from an array
    }

    public function fromJson(array $body): void
    {
        //perhaps we want to create this thing from a serialized Json string
    }

    public function  convertToSnakeCase(string $input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public function convertToCamelCase(string $input): string
    {
        //TODO: implement function
    }
}
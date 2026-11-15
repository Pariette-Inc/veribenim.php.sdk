<?php

declare(strict_types=1);

namespace Veribenim\Tests\Support;

use Veribenim\Proxy\ProxyTransport;

/**
 * Ağa çıkmadan upstream'i taklit eder; giden isteği kaydeder.
 */
class FakeProxyTransport implements ProxyTransport
{
    /** @var array<int, array{method:string,url:string,headers:array<string,string>,body:string,timeout:int}> */
    public array $sent = [];

    /**
     * @param array{status:int, headers: array<int, array{0:string,1:string}>, body:string}|\Throwable $response
     */
    public function __construct(public array|\Throwable $response = ['status' => 200, 'headers' => [], 'body' => '{"status":true}'])
    {
    }

    public function send(string $method, string $url, array $headers, string $body, int $timeout): array
    {
        $this->sent[] = compact('method', 'url', 'headers', 'body', 'timeout');

        if ($this->response instanceof \Throwable) {
            throw $this->response;
        }

        return $this->response;
    }

    public function last(): array
    {
        return end($this->sent) ?: [];
    }
}

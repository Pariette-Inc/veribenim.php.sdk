<?php

declare(strict_types=1);

namespace Veribenim\Tests\Support;

use Veribenim\VeribenimClient;

/**
 * HTTP katmanını (request/postBeacon) gerçek ağ çağrısı yapmadan kaydeden
 * test double'ı. Public API'nin doğru path/payload ürettiğini doğrulamak
 * için kullanılır.
 */
class RecordingVeribenimClient extends VeribenimClient
{
    /** @var array<int, array{method:string, path:string, body:?array}> */
    public array $requests = [];

    /** Sıradaki request() çağrılarının döneceği yanıtlar (FIFO). */
    public array $queuedResponses = [];

    /** postBeacon() çağrılarının döneceği sonuç. */
    public bool $beaconResult = true;

    protected function request(string $method, string $path, ?array $body = null): ?array
    {
        $this->requests[] = ['method' => $method, 'path' => $path, 'body' => $body];

        if ($this->queuedResponses !== []) {
            return array_shift($this->queuedResponses);
        }

        return null;
    }

    protected function postBeacon(string $path, array $body): bool
    {
        $this->requests[] = ['method' => 'POST(beacon)', 'path' => $path, 'body' => $body];

        return $this->beaconResult;
    }

    public function lastRequest(): array
    {
        return end($this->requests) ?: [];
    }
}

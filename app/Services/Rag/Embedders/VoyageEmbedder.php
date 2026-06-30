<?php

declare(strict_types=1);

namespace App\Services\Rag\Embedders;

use App\Services\Rag\Contracts\Embedder;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Embeddings via Voyage AI — the provider Anthropic recommends for embeddings
 * (Anthropic has no first-party embeddings endpoint). `voyage-3.5` returns 1024 dims.
 */
final class VoyageEmbedder implements Embedder
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly int $dimensions,
    ) {}

    public function embed(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = $this->http
            ->withToken($this->apiKey)
            ->acceptJson()
            ->post("{$this->baseUrl}/v1/embeddings", [
                'model' => $this->model,
                'input' => array_values($texts),
                'input_type' => 'document',
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Voyage embeddings request failed: {$response->status()} {$response->body()}");
        }

        /** @var list<array{embedding: list<float>}> $data */
        $data = $response->json('data') ?? [];

        return array_map(static fn (array $row): array => $row['embedding'], $data);
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }
}

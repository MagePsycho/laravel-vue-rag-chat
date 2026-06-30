<?php

declare(strict_types=1);

namespace App\Services\Rag\Embedders;

use App\Services\Rag\Contracts\Embedder;

/**
 * Deterministic, dependency-free embedder for local dev and tests.
 *
 * It builds a normalised "bag of token-hashes" vector: each word is hashed into a
 * bucket and accumulated, then the vector is L2-normalised. It's not semantically
 * smart, but it's stable and gives genuine cosine ranking (documents that share
 * words score higher), so the whole retrieval pipeline is exercisable with no API key.
 */
final class FakeEmbedder implements Embedder
{
    public function __construct(private readonly int $dimensions = 1024) {}

    public function embed(array $texts): array
    {
        return array_map(fn (string $text): array => $this->vectorFor($text), array_values($texts));
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * @return list<float>
     */
    private function vectorFor(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);

        foreach ($this->tokenize($text) as $token) {
            $bucket = (int) (hexdec(substr(md5($token), 0, 8)) % $this->dimensions);
            $vector[$bucket] += 1.0;
        }

        return $this->normalize($vector);
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        preg_match_all('/[a-z0-9]+/', strtolower($text), $matches);

        return $matches[0];
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    private function normalize(array $vector): array
    {
        $magnitude = sqrt(array_sum(array_map(static fn (float $v): float => $v * $v, $vector)));

        if ($magnitude === 0.0) {
            return $vector;
        }

        return array_map(static fn (float $v): float => $v / $magnitude, $vector);
    }
}

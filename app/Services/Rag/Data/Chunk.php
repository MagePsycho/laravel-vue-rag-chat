<?php

declare(strict_types=1);

namespace App\Services\Rag\Data;

/**
 * A single slice of a document, produced by the DocumentChunker before embedding.
 */
final readonly class Chunk
{
    public function __construct(
        public int $position,
        public string $content,
    ) {}
}

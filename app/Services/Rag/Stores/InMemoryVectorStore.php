<?php

declare(strict_types=1);

namespace App\Services\Rag\Stores;

use App\Models\Document;
use App\Services\Rag\Contracts\VectorStore;
use App\Services\Rag\Data\RetrievedChunk;

/**
 * In-process vector store used by tests and the "memory" driver. Computes cosine
 * similarity in PHP — no database required — so the retrieval logic can be
 * exercised in isolation.
 */
final class InMemoryVectorStore implements VectorStore
{
    /** @var list<array{meta: RetrievedChunk, vector: list<float>}> */
    private array $items = [];

    private int $nextId = 1;

    /**
     * Convenience for tests: add a single chunk with a known vector.
     *
     * @param  list<float>  $vector
     */
    public function add(RetrievedChunk $meta, array $vector): void
    {
        $this->items[] = ['meta' => $meta, 'vector' => $vector];
    }

    public function storeDocument(Document $document, array $chunks, array $embeddings): void
    {
        foreach ($chunks as $i => $chunk) {
            $this->add(new RetrievedChunk(
                chunkId: $this->nextId++,
                documentId: (int) ($document->id ?? 0),
                documentTitle: (string) ($document->title ?? 'Untitled'),
                position: $chunk->position,
                content: $chunk->content,
                score: 0.0,
            ), $embeddings[$i]);
        }
    }

    public function search(array $queryVector, int $k): array
    {
        $scored = array_map(function (array $item) use ($queryVector): RetrievedChunk {
            $m = $item['meta'];

            return new RetrievedChunk(
                chunkId: $m->chunkId,
                documentId: $m->documentId,
                documentTitle: $m->documentTitle,
                position: $m->position,
                content: $m->content,
                score: $this->cosine($queryVector, $item['vector']),
            );
        }, $this->items);

        usort($scored, static fn (RetrievedChunk $a, RetrievedChunk $b): int => $b->score <=> $a->score);

        return array_slice($scored, 0, $k);
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        foreach ($a as $i => $av) {
            $bv = $b[$i] ?? 0.0;
            $dot += $av * $bv;
            $magA += $av * $av;
            $magB += $bv * $bv;
        }

        if ($magA === 0.0 || $magB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($magA) * sqrt($magB));
    }
}

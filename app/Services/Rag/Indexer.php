<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\Document;
use App\Services\Rag\Contracts\Embedder;
use App\Services\Rag\Contracts\VectorStore;

/**
 * Turns raw text into a stored, searchable document: chunk -> embed -> persist.
 */
final class Indexer
{
    public function __construct(
        private readonly DocumentChunker $chunker,
        private readonly Embedder $embedder,
        private readonly VectorStore $store,
    ) {}

    /**
     * Index a piece of text under a title. Returns the created Document.
     */
    public function index(string $title, string $text, ?string $sourcePath = null): Document
    {
        $chunks = $this->chunker->chunk($text);

        $document = Document::create([
            'title' => $title,
            'source_path' => $sourcePath,
        ]);

        if ($chunks === []) {
            return $document;
        }

        $embeddings = $this->embedder->embed(array_map(static fn ($c): string => $c->content, $chunks));

        $this->store->storeDocument($document, $chunks, $embeddings);

        return $document;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Rag\Contracts;

use App\Models\Document;
use App\Services\Rag\Data\Chunk;

interface VectorStore extends Retriever
{
    /**
     * Persist a document's chunks together with their embeddings.
     *
     * @param  list<Chunk>  $chunks
     * @param  list<list<float>>  $embeddings  Aligned 1:1 with $chunks.
     */
    public function storeDocument(Document $document, array $chunks, array $embeddings): void;
}

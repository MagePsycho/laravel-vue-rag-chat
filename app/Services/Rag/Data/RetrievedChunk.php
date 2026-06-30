<?php

declare(strict_types=1);

namespace App\Services\Rag\Data;

/**
 * A chunk returned by the retriever, with the source document it came from and a
 * relevance score in [0, 1] (1 = closest match by cosine similarity).
 */
final readonly class RetrievedChunk
{
    public function __construct(
        public int $chunkId,
        public int $documentId,
        public string $documentTitle,
        public int $position,
        public string $content,
        public float $score,
    ) {}

    /**
     * @return array{chunk_id:int,document_id:int,document_title:string,position:int,content:string,score:float}
     */
    public function toArray(): array
    {
        return [
            'chunk_id' => $this->chunkId,
            'document_id' => $this->documentId,
            'document_title' => $this->documentTitle,
            'position' => $this->position,
            'content' => $this->content,
            'score' => round($this->score, 4),
        ];
    }
}

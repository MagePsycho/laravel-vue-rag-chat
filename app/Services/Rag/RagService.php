<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Services\Rag\Contracts\Chatter;
use App\Services\Rag\Contracts\Embedder;
use App\Services\Rag\Contracts\Retriever;
use App\Services\Rag\Data\RetrievedChunk;

/**
 * The RAG orchestrator: embed the question, retrieve the most relevant chunks,
 * then stream a grounded answer from those chunks.
 *
 * It depends only on contracts (Embedder / Retriever / Chatter), so the backend
 * — fake stubs, pgvector, Anthropic — is swappable without touching this class.
 */
final class RagService
{
    public function __construct(
        private readonly Embedder $embedder,
        private readonly Retriever $retriever,
        private readonly Chatter $chatter,
        private readonly int $topK = 5,
    ) {}

    /**
     * Retrieve the chunks most relevant to a question.
     *
     * @return list<RetrievedChunk>
     */
    public function retrieve(string $question, ?int $k = null): array
    {
        $question = trim($question);
        if ($question === '') {
            return [];
        }

        [$queryVector] = $this->embedder->embed([$question]);

        return $this->retriever->search($queryVector, $k ?? $this->topK);
    }

    /**
     * Answer a question, streaming text tokens. Yields a leading "sources" record
     * (the retrieved chunks) followed by answer-text fragments.
     *
     * @return iterable<array{type:'sources',sources:list<array<string,mixed>>}|array{type:'token',value:string}|array{type:'done'}>
     */
    public function answer(string $question, ?int $k = null): iterable
    {
        $context = $this->retrieve($question, $k);

        yield [
            'type' => 'sources',
            'sources' => array_map(static fn (RetrievedChunk $c): array => $c->toArray(), $context),
        ];

        foreach ($this->chatter->stream($question, $context) as $fragment) {
            yield ['type' => 'token', 'value' => $fragment];
        }

        yield ['type' => 'done'];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Rag\Contracts;

use App\Services\Rag\Data\RetrievedChunk;

interface Chatter
{
    /**
     * Stream a grounded answer for $question using the retrieved $context chunks.
     *
     * Implementations MUST yield only text fragments (tokens) and MUST answer from
     * the provided context, not prior knowledge.
     *
     * @param  list<RetrievedChunk>  $context
     * @return iterable<string>
     */
    public function stream(string $question, array $context): iterable;
}

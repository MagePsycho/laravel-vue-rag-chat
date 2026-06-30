<?php

declare(strict_types=1);

namespace App\Services\Rag\Contracts;

use App\Services\Rag\Data\RetrievedChunk;

interface Retriever
{
    /**
     * Return the $k chunks most similar to the given query vector, best first.
     *
     * @param  list<float>  $queryVector
     * @return list<RetrievedChunk>
     */
    public function search(array $queryVector, int $k): array;
}

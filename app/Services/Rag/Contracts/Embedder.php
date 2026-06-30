<?php

declare(strict_types=1);

namespace App\Services\Rag\Contracts;

interface Embedder
{
    /**
     * Embed one or more texts into fixed-length float vectors.
     *
     * @param  list<string>  $texts
     * @return list<list<float>> One vector per input text, in the same order.
     */
    public function embed(array $texts): array;

    /**
     * Dimensionality of the vectors this embedder produces.
     */
    public function dimensions(): int;
}

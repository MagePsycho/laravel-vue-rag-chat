<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Services\Rag\Data\Chunk;

/**
 * Splits text into overlapping, word-bounded windows. Overlap preserves context
 * across chunk boundaries so a sentence split in two is still retrievable.
 */
final class DocumentChunker
{
    public function __construct(
        private readonly int $size = 900,
        private readonly int $overlap = 150,
    ) {
        if ($this->size <= 0) {
            throw new \InvalidArgumentException('Chunk size must be positive.');
        }

        if ($this->overlap < 0 || $this->overlap >= $this->size) {
            throw new \InvalidArgumentException('Overlap must be >= 0 and smaller than the chunk size.');
        }
    }

    /**
     * @return list<Chunk>
     */
    public function chunk(string $text): array
    {
        $text = trim(preg_replace('/[ \t]+/', ' ', $text) ?? $text);

        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= $this->size) {
            return [new Chunk(0, $text)];
        }

        $chunks = [];
        $position = 0;
        $start = 0;
        $length = mb_strlen($text);
        // Fixed stride keeps the window count bounded and the overlap predictable.
        $step = $this->size - $this->overlap;

        while ($start < $length) {
            $window = mb_substr($text, $start, $this->size);

            // For display, avoid cutting mid-word: trim the stored content back to the
            // last whitespace when this isn't the final window. Stepping is unaffected.
            $isFinal = ($start + $this->size) >= $length;
            if (! $isFinal) {
                $lastSpace = mb_strrpos($window, ' ');
                if ($lastSpace !== false && $lastSpace > 0) {
                    $window = mb_substr($window, 0, $lastSpace);
                }
            }

            $trimmed = trim($window);
            if ($trimmed !== '') {
                $chunks[] = new Chunk($position++, $trimmed);
            }

            $start += $step;
        }

        return $chunks;
    }
}

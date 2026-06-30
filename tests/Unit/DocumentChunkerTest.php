<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Rag\DocumentChunker;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DocumentChunkerTest extends TestCase
{
    public function test_short_text_yields_a_single_chunk(): void
    {
        $chunker = new DocumentChunker(size: 100, overlap: 20);

        $chunks = $chunker->chunk('A short document.');

        $this->assertCount(1, $chunks);
        $this->assertSame(0, $chunks[0]->position);
        $this->assertSame('A short document.', $chunks[0]->content);
    }

    public function test_empty_text_yields_no_chunks(): void
    {
        $chunker = new DocumentChunker;

        $this->assertSame([], $chunker->chunk("   \n\t  "));
    }

    public function test_long_text_is_split_into_multiple_ordered_chunks(): void
    {
        $chunker = new DocumentChunker(size: 50, overlap: 10);
        $text = str_repeat('word ', 60); // 300 chars

        $chunks = $chunker->chunk($text);

        $this->assertGreaterThan(1, count($chunks));

        // Positions are sequential starting at 0.
        foreach ($chunks as $i => $chunk) {
            $this->assertSame($i, $chunk->position);
            $this->assertLessThanOrEqual(50, mb_strlen($chunk->content));
            $this->assertNotSame('', trim($chunk->content));
        }
    }

    public function test_consecutive_chunks_overlap(): void
    {
        $chunker = new DocumentChunker(size: 40, overlap: 10);
        // No spaces, so word-boundary backup doesn't apply and overlap is exact.
        $text = implode('', array_map(static fn (int $i): string => chr(97 + ($i % 26)), range(0, 99)));

        $chunks = $chunker->chunk($text);

        $this->assertGreaterThanOrEqual(2, count($chunks));

        // The last `overlap` chars of chunk 0 are the first `overlap` chars of chunk 1.
        $this->assertSame(
            mb_substr($chunks[0]->content, -10),
            mb_substr($chunks[1]->content, 0, 10),
        );
    }

    public function test_invalid_overlap_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DocumentChunker(size: 100, overlap: 100);
    }
}

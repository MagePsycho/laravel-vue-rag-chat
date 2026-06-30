<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Rag\Chatters\FakeChatter;
use App\Services\Rag\Data\RetrievedChunk;
use App\Services\Rag\Embedders\FakeEmbedder;
use App\Services\Rag\RagService;
use App\Services\Rag\Stores\InMemoryVectorStore;
use PHPUnit\Framework\TestCase;

class RagServiceTest extends TestCase
{
    private function makeService(int $topK = 3): array
    {
        $embedder = new FakeEmbedder(64);
        $store = new InMemoryVectorStore;

        $documents = [
            'Payments' => 'We support Stripe and PayPal for online payments and refunds.',
            'Shipping' => 'Orders ship within two business days via courier worldwide.',
            'Returns' => 'You can return any item within thirty days for a full refund.',
        ];

        foreach (array_values($documents) as $i => $content) {
            [$vector] = $embedder->embed([$content]);
            $store->add(new RetrievedChunk(
                chunkId: $i + 1,
                documentId: $i + 1,
                documentTitle: array_keys($documents)[$i],
                position: 0,
                content: $content,
                score: 0.0,
            ), $vector);
        }

        return [new RagService($embedder, $store, new FakeChatter, $topK), $store];
    }

    public function test_retrieve_ranks_the_most_relevant_chunk_first(): void
    {
        [$service] = $this->makeService();

        $results = $service->retrieve('how do refunds and payments work?');

        $this->assertNotEmpty($results);
        // The payments doc shares the most words with the query, so it should rank first.
        $this->assertSame('Payments', $results[0]->documentTitle);
        $this->assertGreaterThan(0.0, $results[0]->score);
    }

    public function test_retrieve_respects_top_k(): void
    {
        [$service] = $this->makeService(topK: 2);

        $this->assertCount(2, $service->retrieve('shipping returns payments'));
    }

    public function test_blank_question_returns_no_results(): void
    {
        [$service] = $this->makeService();

        $this->assertSame([], $service->retrieve('   '));
    }

    public function test_answer_emits_sources_then_tokens_then_done(): void
    {
        [$service] = $this->makeService();

        $events = iterator_to_array($service->answer('what about returns?'), false);

        $this->assertSame('sources', $events[0]['type']);
        $this->assertNotEmpty($events[0]['sources']);

        $this->assertSame('done', $events[array_key_last($events)]['type']);

        $tokens = array_values(array_filter($events, static fn ($e): bool => $e['type'] === 'token'));
        $this->assertNotEmpty($tokens);

        $answer = implode('', array_map(static fn ($e): string => $e['value'], $tokens));
        $this->assertStringContainsString('return', strtolower($answer));
    }
}

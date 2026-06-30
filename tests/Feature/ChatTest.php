<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Rag\Indexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function seedKnowledgeBase(): void
    {
        app(Indexer::class)->index(
            'Database',
            'AskDocs stores chunk embeddings in PostgreSQL using the pgvector extension for fast similarity search.',
        );
    }

    public function test_chat_page_requires_authentication(): void
    {
        $this->get('/chat')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_the_chat_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/chat')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Chat')->has('topK')->has('driver'));
    }

    public function test_ask_streams_sources_answer_and_done(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seedKnowledgeBase();

        $response = $this->post('/chat/ask', ['question' => 'What database does AskDocs use?']);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/x-ndjson');

        $body = $response->streamedContent();
        $this->assertStringContainsString('"type":"sources"', $body);
        $this->assertStringContainsString('Database', $body);
        $this->assertStringContainsString('"type":"token"', $body);
        $this->assertStringContainsString('"type":"done"', $body);
    }

    public function test_ask_validates_the_question(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/chat/ask', ['question' => 'x'])
            ->assertSessionHasErrors('question');
    }

    public function test_search_endpoint_rejects_missing_token(): void
    {
        $this->seedKnowledgeBase();

        $this->getJson('/api/search?q=database')->assertUnauthorized();
    }

    public function test_search_endpoint_returns_results_with_valid_token(): void
    {
        $this->seedKnowledgeBase();

        $this->getJson('/api/search?q=which database is used&k=3', [
            'Authorization' => 'Bearer '.config('rag.api_token'),
        ])
            ->assertOk()
            ->assertJsonStructure([
                'query',
                'results' => [['chunk_id', 'document_title', 'content', 'score']],
            ]);
    }
}

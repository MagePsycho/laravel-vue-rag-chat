<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Rag\Chatters\AnthropicChatter;
use App\Services\Rag\Chatters\FakeChatter;
use App\Services\Rag\Contracts\Chatter;
use App\Services\Rag\Contracts\Embedder;
use App\Services\Rag\Contracts\Retriever;
use App\Services\Rag\Contracts\VectorStore;
use App\Services\Rag\DocumentChunker;
use App\Services\Rag\Embedders\FakeEmbedder;
use App\Services\Rag\Embedders\VoyageEmbedder;
use App\Services\Rag\RagService;
use App\Services\Rag\Stores\InMemoryVectorStore;
use App\Services\Rag\Stores\PgVectorStore;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class RagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentChunker::class, fn (): DocumentChunker => new DocumentChunker(
            size: (int) config('rag.chunking.size'),
            overlap: (int) config('rag.chunking.overlap'),
        ));

        $this->app->singleton(Embedder::class, fn (Application $app): Embedder => $this->makeEmbedder($app));
        $this->app->singleton(Chatter::class, fn (Application $app): Chatter => $this->makeChatter($app));

        // One store instance backs both the read (Retriever) and write (VectorStore) sides.
        $this->app->singleton(VectorStore::class, fn (Application $app): VectorStore => $this->makeStore($app));
        $this->app->alias(VectorStore::class, Retriever::class);

        $this->app->singleton(RagService::class, fn (Application $app): RagService => new RagService(
            embedder: $app->make(Embedder::class),
            retriever: $app->make(Retriever::class),
            chatter: $app->make(Chatter::class),
            topK: (int) config('rag.retrieval.top_k'),
        ));
    }

    private function makeEmbedder(Application $app): Embedder
    {
        $dimensions = (int) config('rag.embedding.dimensions');

        return match (config('rag.driver')) {
            'fake' => new FakeEmbedder($dimensions),
            'anthropic' => new VoyageEmbedder(
                http: $app->make(HttpFactory::class),
                apiKey: (string) config('rag.voyage.key'),
                baseUrl: (string) config('rag.voyage.base_url'),
                model: (string) config('rag.embedding.voyage_model'),
                dimensions: $dimensions,
            ),
            default => throw new InvalidArgumentException('Unknown rag.driver: '.config('rag.driver')),
        };
    }

    private function makeChatter(Application $app): Chatter
    {
        return match (config('rag.driver')) {
            'fake' => new FakeChatter,
            'anthropic' => new AnthropicChatter(
                http: $app->make(HttpFactory::class),
                apiKey: (string) config('rag.anthropic.key'),
                baseUrl: (string) config('rag.anthropic.base_url'),
                version: (string) config('rag.anthropic.version'),
                model: (string) config('rag.chat.model'),
                maxTokens: (int) config('rag.chat.max_tokens'),
            ),
            default => throw new InvalidArgumentException('Unknown rag.driver: '.config('rag.driver')),
        };
    }

    private function makeStore(Application $app): VectorStore
    {
        return match (config('rag.store')) {
            'pgvector' => new PgVectorStore($app->make('db')->connection()),
            'memory' => new InMemoryVectorStore,
            default => throw new InvalidArgumentException('Unknown rag.store: '.config('rag.store')),
        };
    }
}

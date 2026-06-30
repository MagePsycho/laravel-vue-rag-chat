<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | Which embedding + chat backend to use:
    |   "fake"      -> deterministic local stubs, no API keys (default / tests)
    |   "anthropic" -> Voyage AI embeddings + Anthropic Claude for answers
    |
    */
    'driver' => env('RAG_DRIVER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Retrieval store
    |--------------------------------------------------------------------------
    |
    | "pgvector" persists embeddings in Postgres via the pgvector extension.
    | "memory" keeps everything in process — useful for tests and quick demos.
    |
    */
    'store' => env('RAG_STORE', 'pgvector'),

    'embedding' => [
        // Must match the vector(N) column created by the migration.
        'dimensions' => (int) env('RAG_EMBED_DIM', 1024),
        'voyage_model' => env('VOYAGE_MODEL', 'voyage-3.5'),
    ],

    'chat' => [
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'max_tokens' => (int) env('RAG_MAX_TOKENS', 1024),
    ],

    'chunking' => [
        'size' => (int) env('RAG_CHUNK_SIZE', 900),
        'overlap' => (int) env('RAG_CHUNK_OVERLAP', 150),
    ],

    'retrieval' => [
        'top_k' => (int) env('RAG_TOP_K', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | API token
    |--------------------------------------------------------------------------
    |
    | Static bearer token guarding the read-only /api/search endpoint that the
    | MCP server calls. Swap for real auth (Sanctum/OAuth) in production.
    |
    */
    'api_token' => env('RAG_API_TOKEN', 'local-dev-token'),

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
    ],

    'voyage' => [
        'key' => env('VOYAGE_API_KEY'),
        'base_url' => env('VOYAGE_BASE_URL', 'https://api.voyageai.com'),
    ],
];

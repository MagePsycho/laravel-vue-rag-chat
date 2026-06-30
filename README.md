# AskDocs — Laravel + Vue 3 RAG starter

A small but complete **Retrieval-Augmented Generation** app: ask natural-language questions about
your own documents and get answers **grounded in those documents, with citations**.

It's built on the official **Laravel Vue starter kit** and is meant as a clean reference for a
full RAG pipeline — ingestion, vector storage, retrieval, streamed generation — plus an **MCP
server** that exposes the same search to AI agents.

> Runs end-to-end with **no API keys** out of the box (a deterministic `fake` driver powers
> embeddings and answers), so you can see the whole pipeline work in a couple of minutes.

## What it demonstrates

| Job stack item            | Where it lives                                                                 |
| ------------------------- | ----------------------------------------------------------------------------- |
| **PHP / Laravel**         | `app/Services/Rag/*` service layer, `app/Http/Controllers/ChatController.php`  |
| **Vue 3 + TypeScript**    | `resources/js/pages/Chat.vue`, `resources/js/components/chat/*`                |
| **Streaming UI**          | `resources/js/composables/useChatStream.ts` (NDJSON over `fetch` + ReadableStream) |
| **PostgreSQL + pgvector** | `app/Services/Rag/Stores/PgVectorStore.php`, `database/migrations/*_create_rag_tables.php` |
| **Vectorization**         | `app/Services/Rag/Embedders/*` (Voyage AI + a deterministic fake)             |
| **RAG**                   | `app/Services/Rag/RagService.php` (embed → retrieve → grounded, streamed answer) |
| **MCP server**            | `mcp/` (TypeScript, exposes `search_knowledge_base`)                          |
| **Tests**                 | `tests/Unit/*`, `tests/Feature/ChatTest.php` (run on sqlite, no DB/keys)      |

## Architecture

```
 ingest ──► DocumentChunker ──► Embedder ──► VectorStore (pgvector)
                                                  │
 question ─► Embedder ─► VectorStore.search() ─► top-k chunks ─► Chatter (Claude) ─► streamed answer + citations
```

The orchestrator (`RagService`) depends only on three interfaces — `Embedder`, `Retriever`,
`Chatter` — so the backend (fake stubs, pgvector, Voyage, Anthropic) is swappable via config with
no change to the pipeline. The driver is chosen in `config/rag.php` (`RAG_DRIVER`, `RAG_STORE`).

## Quick start

Requirements: PHP 8.2+, Composer, Node 20+, Docker.

```bash
# 1. Postgres + pgvector
docker compose up -d

# 2. Dependencies + env
composer install
npm install
cp .env.example .env
php artisan key:generate

# 3. Schema + sample documents
php artisan migrate
php artisan rag:ingest storage/sample-docs

# 4. Run it (Vite + the app)
composer run dev      # or: php artisan serve & npm run dev
```

Open the app, register a user, and go to **Ask Docs** in the sidebar. Ask something like
*"What database does AskDocs use, and why?"* — you'll see the retrieved sources, then the answer
streaming in token by token.

## Using real models (optional)

Set these in `.env` to switch from the fake driver to live embeddings + answers:

```dotenv
RAG_DRIVER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-sonnet-4-6
VOYAGE_API_KEY=pa-...           # Anthropic's recommended embeddings provider
VOYAGE_MODEL=voyage-3.5         # 1024 dims — matches RAG_EMBED_DIM
```

Answers stream from Anthropic's Messages API; embeddings come from Voyage AI.

## Ingesting your own documents

```bash
php artisan rag:ingest /path/to/docs            # a directory of .md/.txt files
php artisan rag:ingest notes.md                  # a single file
php artisan rag:ingest /path/to/docs --ext=md,txt,markdown
```

## MCP server

The `mcp/` directory contains a Model Context Protocol server exposing `search_knowledge_base`, so
an MCP client (e.g. Claude) can query the same knowledge base. See [`mcp/README.md`](mcp/README.md).

## Tests

```bash
php artisan test
```

Tests run against sqlite with the `fake` driver and an in-memory vector store, so they need
**no Postgres and no API keys**. They cover the chunker, the RAG orchestration (ranking,
top-k, streamed events), the auth-gated streaming endpoint, and the token-guarded search API.

## Notes / production hardening

This is a focused reference implementation. For production you'd swap the static `/api/search`
token for real auth (Sanctum/OAuth), add rate limiting, batch embeddings on ingest, and tune
`RAG_CHUNK_SIZE` / `RAG_TOP_K` to your corpus.

---

## Author

**Rajendra Kumar Bhatta (MagePsycho)** — [MagePsycho](https://github.com/magepsycho)

A full-stack + LLM work sample.

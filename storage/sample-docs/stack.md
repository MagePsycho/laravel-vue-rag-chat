# AskDocs Technology Stack

AskDocs deliberately uses the same stack a modern full-stack team would reach for.

- **Backend:** PHP 8.4 and Laravel 12. The RAG logic lives in a small service layer under
  `app/Services/Rag` and depends only on interfaces (Embedder, Retriever, Chatter), so the
  backend implementation is swappable.
- **Frontend:** Vue 3 with the Composition API and TypeScript, served through Inertia 2 from
  the official Laravel Vue starter kit. Styling is Tailwind CSS with shadcn-vue components.
- **Database:** PostgreSQL 16 with the pgvector extension for vector similarity search.
- **Embeddings:** Voyage AI (Anthropic's recommended embeddings provider) in production, with a
  deterministic fake embedder for local development and tests.
- **Answers:** Anthropic Claude via the Messages API, streamed token by token over Server-Sent
  style newline-delimited JSON.
- **MCP:** a small Model Context Protocol server exposes the knowledge-base search as a tool so
  AI agents can query the same retrieval endpoint.

## Running with no API keys

The default driver is `fake`, which uses local stubs for both embeddings and answers. This means
you can clone the repo, start Postgres, ingest the sample docs, and see the full pipeline work
end to end without signing up for any third-party service.

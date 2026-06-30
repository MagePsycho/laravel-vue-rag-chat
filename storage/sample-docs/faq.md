# AskDocs FAQ

## What problem does AskDocs solve?

Large language models don't know about your private documents and will happily make things up.
AskDocs grounds answers in your own content by retrieving the most relevant passages first and
asking the model to answer only from those passages, with citations.

## How are answers kept accurate?

Two ways. First, retrieval narrows the model's input to passages that actually match the question.
Second, the system prompt instructs the model to answer only from the provided context and to say
when the answer isn't there, instead of guessing.

## How do I add my own documents?

Drop `.md` or `.txt` files anywhere and run `php artisan rag:ingest path/to/files`. The command
chunks, embeds, and stores them. Re-running ingestion adds new documents.

## What is the MCP server for?

The Model Context Protocol (MCP) server exposes the same retrieval as a tool named
`search_knowledge_base`. An MCP-compatible agent (for example Claude) can call it to search your
documents directly, reusing the exact pipeline the web UI uses.

## Is it production ready?

It's a focused reference implementation. For production you would swap the static API token for
real authentication, add rate limiting, and tune the chunk size and top-k for your corpus.

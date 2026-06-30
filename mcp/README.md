# AskDocs MCP server

A small [Model Context Protocol](https://modelcontextprotocol.io) server that exposes the AskDocs
RAG knowledge base as a tool. An MCP-compatible agent (e.g. Claude Desktop / Claude Code) can call
`search_knowledge_base` to retrieve relevant document chunks — reusing the same `/api/search`
endpoint the web UI uses.

## Build

```bash
cd mcp
npm install
npm run build
```

## Configure

The server talks to the Laravel app over HTTP and authenticates with the static token from the
Laravel `.env` (`RAG_API_TOKEN`).

| Variable        | Default                  | Purpose                         |
| --------------- | ------------------------ | ------------------------------- |
| `RAG_BASE_URL`  | `http://localhost:8000`  | Base URL of the Laravel app     |
| `RAG_API_TOKEN` | `local-dev-token`        | Bearer token for `/api/search`  |

## Register with an MCP client

Add to your client's MCP config (e.g. `claude_desktop_config.json`):

```json
{
    "mcpServers": {
        "askdocs": {
            "command": "node",
            "args": ["/absolute/path/to/laravel-vue-rag-chat/mcp/dist/index.js"],
            "env": {
                "RAG_BASE_URL": "http://localhost:8000",
                "RAG_API_TOKEN": "local-dev-token"
            }
        }
    }
}
```

## Tool

- **`search_knowledge_base(query, k = 5)`** — semantic search over the knowledge base. Returns the
  top-`k` chunks with document titles and similarity scores.

Make sure the Laravel app is running (`php artisan serve`) and documents have been ingested
(`php artisan rag:ingest storage/sample-docs`) before calling the tool.

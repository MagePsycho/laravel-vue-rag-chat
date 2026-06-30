#!/usr/bin/env node
/**
 * AskDocs MCP server.
 *
 * Exposes the RAG knowledge base as a Model Context Protocol tool so any
 * MCP-compatible agent (e.g. Claude) can search your documents using the exact
 * same retrieval endpoint the web UI uses. Talks over stdio.
 *
 * Config via environment:
 *   RAG_BASE_URL   Base URL of the Laravel app   (default http://localhost:8000)
 *   RAG_API_TOKEN  Bearer token for /api/search  (default local-dev-token)
 */
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';

const BASE_URL = (process.env.RAG_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '');
const API_TOKEN = process.env.RAG_API_TOKEN ?? 'local-dev-token';

interface SearchResult {
    chunk_id: number;
    document_id: number;
    document_title: string;
    position: number;
    content: string;
    score: number;
}

async function searchKnowledgeBase(query: string, k: number): Promise<SearchResult[]> {
    const url = new URL(`${BASE_URL}/api/search`);
    url.searchParams.set('q', query);
    url.searchParams.set('k', String(k));

    const response = await fetch(url, {
        headers: { Authorization: `Bearer ${API_TOKEN}`, Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Search request failed: ${response.status} ${await response.text()}`);
    }

    const body = (await response.json()) as { results: SearchResult[] };
    return body.results ?? [];
}

const server = new McpServer({ name: 'askdocs', version: '1.0.0' });

server.registerTool(
    'search_knowledge_base',
    {
        title: 'Search knowledge base',
        description:
            'Semantic search over the AskDocs knowledge base. Returns the most relevant document ' +
            'chunks (with titles and similarity scores) for a natural-language query. Use this to ' +
            'ground answers in the indexed documents instead of guessing.',
        inputSchema: {
            query: z.string().min(2).describe('The natural-language search query'),
            k: z.number().int().min(1).max(20).default(5).describe('How many chunks to return'),
        },
    },
    async ({ query, k }) => {
        const results = await searchKnowledgeBase(query, k);

        if (results.length === 0) {
            return { content: [{ type: 'text', text: `No matching passages found for "${query}".` }] };
        }

        const text = results
            .map(
                (r, i) =>
                    `[#${i + 1}] ${r.document_title} (${Math.round(r.score * 100)}% match)\n${r.content}`,
            )
            .join('\n\n');

        return { content: [{ type: 'text', text }] };
    },
);

async function main(): Promise<void> {
    const transport = new StdioServerTransport();
    await server.connect(transport);
    console.error(`askdocs-mcp running (base: ${BASE_URL})`);
}

main().catch((error) => {
    console.error('Fatal error starting askdocs-mcp:', error);
    process.exit(1);
});

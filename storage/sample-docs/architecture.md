# AskDocs Architecture

AskDocs is a Retrieval-Augmented Generation (RAG) application built on Laravel and Vue 3.
It lets a user ask natural-language questions about a private collection of documents and
returns answers grounded in those documents, with citations back to the source passages.

## Pipeline

The retrieval pipeline has four stages:

1. **Ingestion** — the `rag:ingest` Artisan command reads files, splits each document into
   overlapping text chunks, and embeds each chunk into a vector.
2. **Storage** — chunk vectors are stored in PostgreSQL using the pgvector extension. An HNSW
   index on the embedding column makes nearest-neighbour search fast.
3. **Retrieval** — at question time the question is embedded and the store returns the top-k
   most similar chunks using cosine distance (the `<=>` operator in pgvector).
4. **Generation** — the retrieved chunks are passed to a chat model, which writes an answer
   using only that context and cites the passages it relied on.

## Why overlapping chunks

Splitting a document into fixed windows can cut a sentence in half, which hurts retrieval.
AskDocs overlaps consecutive chunks (150 characters by default) so a concept that straddles a
boundary still appears whole in at least one chunk.

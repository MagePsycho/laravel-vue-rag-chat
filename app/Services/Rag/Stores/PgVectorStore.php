<?php

declare(strict_types=1);

namespace App\Services\Rag\Stores;

use App\Models\Document;
use App\Services\Rag\Contracts\VectorStore;
use App\Services\Rag\Data\RetrievedChunk;
use Illuminate\Database\ConnectionInterface;

/**
 * Persists chunk embeddings in Postgres (pgvector) and retrieves the nearest
 * neighbours by cosine distance using the `<=>` operator.
 */
final class PgVectorStore implements VectorStore
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function storeDocument(Document $document, array $chunks, array $embeddings): void
    {
        $this->db->transaction(function () use ($document, $chunks, $embeddings): void {
            foreach ($chunks as $i => $chunk) {
                $id = $this->db->table('document_chunks')->insertGetId([
                    'document_id' => $document->id,
                    'position' => $chunk->position,
                    'content' => $chunk->content,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // pgvector accepts the textual "[v1,v2,...]" form, cast to ::vector.
                $this->db->statement(
                    'UPDATE document_chunks SET embedding = ?::vector WHERE id = ?',
                    [$this->toVectorLiteral($embeddings[$i]), $id],
                );
            }
        });
    }

    public function search(array $queryVector, int $k): array
    {
        $rows = $this->db->select(
            <<<'SQL'
            SELECT c.id, c.document_id, c.position, c.content, d.title AS document_title,
                   1 - (c.embedding <=> ?::vector) AS score
            FROM document_chunks c
            JOIN documents d ON d.id = c.document_id
            WHERE c.embedding IS NOT NULL
            ORDER BY c.embedding <=> ?::vector
            LIMIT ?
            SQL,
            [$this->toVectorLiteral($queryVector), $this->toVectorLiteral($queryVector), $k],
        );

        return array_map(static fn (object $row): RetrievedChunk => new RetrievedChunk(
            chunkId: (int) $row->id,
            documentId: (int) $row->document_id,
            documentTitle: (string) $row->document_title,
            position: (int) $row->position,
            content: (string) $row->content,
            score: (float) $row->score,
        ), $rows);
    }

    /**
     * @param  list<float>  $vector
     */
    private function toVectorLiteral(array $vector): string
    {
        return '['.implode(',', array_map(static fn (float $v): string => rtrim(rtrim(sprintf('%.8f', $v), '0'), '.'), $vector)).']';
    }
}

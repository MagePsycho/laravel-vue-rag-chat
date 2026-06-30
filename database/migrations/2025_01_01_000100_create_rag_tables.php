<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $dimensions = (int) config('rag.embedding.dimensions', 1024);

        if ($isPgsql) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('source_path')->nullable();
            $table->timestamps();
        });

        Schema::create('document_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('content');
            $table->timestamps();
        });

        if ($isPgsql) {
            // Native pgvector column + an approximate-nearest-neighbour HNSW index
            // tuned for cosine distance — this is what makes retrieval fast at scale.
            DB::statement("ALTER TABLE document_chunks ADD COLUMN embedding vector({$dimensions})");
            DB::statement('CREATE INDEX document_chunks_embedding_idx ON document_chunks USING hnsw (embedding vector_cosine_ops)');
        } else {
            // Fallback for sqlite (tests use the in-memory retriever, so this is just
            // here to keep migrations green on non-pgsql connections).
            Schema::table('document_chunks', function (Blueprint $table): void {
                $table->text('embedding')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
        Schema::dropIfExists('documents');
    }
};

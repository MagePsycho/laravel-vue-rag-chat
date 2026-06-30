<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Rag\Indexer;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class RagIngest extends Command
{
    protected $signature = 'rag:ingest
        {path : A file or directory to ingest}
        {--ext=md,txt : Comma-separated file extensions to include when a directory is given}';

    protected $description = 'Chunk, embed, and store documents into the RAG knowledge base';

    public function handle(Indexer $indexer): int
    {
        $path = (string) $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Path not found: {$path}");

            return self::FAILURE;
        }

        $files = is_dir($path)
            ? $this->filesIn($path)
            : [$path];

        if ($files === []) {
            $this->warn('No matching files to ingest.');

            return self::SUCCESS;
        }

        $totalChunks = 0;

        foreach ($files as $file) {
            $text = (string) file_get_contents($file);
            $title = pathinfo($file, PATHINFO_FILENAME);

            $document = $indexer->index($title, $text, $file);
            $count = $document->chunks()->count();
            $totalChunks += $count;

            $this->line("  ✓ <info>{$title}</info> ({$count} chunks)");
        }

        $this->info(sprintf('Ingested %d document(s), %d chunk(s).', count($files), $totalChunks));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function filesIn(string $dir): array
    {
        $extensions = array_filter(array_map('trim', explode(',', (string) $this->option('ext'))));
        $names = array_map(static fn (string $ext): string => '*.'.ltrim($ext, '.'), $extensions);

        $finder = (new Finder)->files()->in($dir)->name($names);

        return array_values(array_map(
            static fn ($file): string => $file->getRealPath(),
            iterator_to_array($finder),
        ));
    }
}

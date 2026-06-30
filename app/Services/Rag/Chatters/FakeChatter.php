<?php

declare(strict_types=1);

namespace App\Services\Rag\Chatters;

use App\Services\Rag\Contracts\Chatter;

/**
 * Offline chatter for local dev and tests. It doesn't call an LLM; instead it
 * composes a short, grounded answer from the retrieved context and cites the
 * sources by number — enough to demonstrate the full RAG round-trip with no key.
 */
final class FakeChatter implements Chatter
{
    public function stream(string $question, array $context): iterable
    {
        if ($context === []) {
            yield "I couldn't find anything relevant in the knowledge base to answer that.";

            return;
        }

        yield 'Based on the retrieved documents, here is what I found';
        yield " for \"{$question}\":\n\n";

        foreach ($context as $i => $chunk) {
            $n = $i + 1;
            $snippet = $this->firstSentence($chunk->content);
            yield "• {$snippet} [#{$n}]\n";
        }

        yield "\nSources: ";
        yield implode(', ', array_map(
            static fn ($i, $chunk) => '#'.($i + 1).' '.$chunk->documentTitle,
            array_keys($context),
            $context,
        ));
    }

    private function firstSentence(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        $parts = preg_split('/(?<=[.!?])\s+/', $text, 2) ?: [$text];
        $sentence = $parts[0];

        return mb_strlen($sentence) > 220 ? mb_substr($sentence, 0, 217).'...' : $sentence;
    }
}

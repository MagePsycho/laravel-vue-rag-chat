<?php

declare(strict_types=1);

namespace App\Services\Rag\Chatters;

use App\Services\Rag\Contracts\Chatter;
use App\Services\Rag\Data\RetrievedChunk;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Streams a grounded answer from Anthropic's Messages API (SSE), reading
 * `content_block_delta` events and yielding their text as it arrives.
 */
final class AnthropicChatter implements Chatter
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $version,
        private readonly string $model,
        private readonly int $maxTokens,
    ) {}

    public function stream(string $question, array $context): iterable
    {
        $response = $this->http
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->version,
            ])
            ->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/v1/messages", [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'stream' => true,
                'system' => $this->systemPrompt(),
                'messages' => [
                    ['role' => 'user', 'content' => $this->userPrompt($question, $context)],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Anthropic request failed: {$response->status()} {$response->body()}");
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($newline = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newline);
                $buffer = substr($buffer, $newline + 1);

                $text = $this->textFromSseLine(trim($line));
                if ($text !== null) {
                    yield $text;
                }
            }
        }
    }

    private function textFromSseLine(string $line): ?string
    {
        if (! str_starts_with($line, 'data:')) {
            return null;
        }

        $payload = trim(substr($line, strlen('data:')));
        if ($payload === '' || $payload === '[DONE]') {
            return null;
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || ($event['type'] ?? null) !== 'content_block_delta') {
            return null;
        }

        $text = $event['delta']['text'] ?? null;

        return is_string($text) ? $text : null;
    }

    private function systemPrompt(): string
    {
        return 'You are a precise retrieval assistant. Answer ONLY using the numbered '
            .'context passages provided. Cite the passages you use with their bracketed '
            .'number, e.g. [#2]. If the context does not contain the answer, say so plainly '
            .'instead of guessing.';
    }

    /**
     * @param  list<RetrievedChunk>  $context
     */
    private function userPrompt(string $question, array $context): string
    {
        $passages = '';
        foreach ($context as $i => $chunk) {
            $n = $i + 1;
            $passages .= "[#{$n}] (source: {$chunk->documentTitle})\n{$chunk->content}\n\n";
        }

        return "Context passages:\n\n{$passages}Question: {$question}";
    }
}

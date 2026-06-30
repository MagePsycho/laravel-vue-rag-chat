<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Rag\RagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(private readonly RagService $rag) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Chat', [
            'topK' => (int) config('rag.retrieval.top_k'),
            'driver' => (string) config('rag.driver'),
        ]);
    }

    /**
     * Stream a grounded answer as newline-delimited JSON (NDJSON):
     *   {"type":"sources","sources":[...]}
     *   {"type":"token","value":"..."}   (many)
     *   {"type":"done"}
     */
    public function ask(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $rag = $this->rag;

        return response()->stream(function () use ($rag, $validated): void {
            foreach ($rag->answer($validated['question']) as $event) {
                echo json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Read-only retrieval endpoint consumed by the MCP server. Guarded by a static
     * bearer token (swap for Sanctum/OAuth in production).
     */
    public function search(Request $request): JsonResponse
    {
        $expected = (string) Config::get('rag.api_token');
        if ($expected === '' || ! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:2000'],
            'k' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $chunks = $this->rag->retrieve($validated['q'], isset($validated['k']) ? (int) $validated['k'] : null);

        return response()->json([
            'query' => $validated['q'],
            'results' => array_map(static fn ($c): array => $c->toArray(), $chunks),
        ]);
    }
}

import type { ChatMessage, Source, StreamEvent } from '@/types/chat';
import { ref } from 'vue';

/**
 * Reads the Laravel XSRF-TOKEN cookie so we can send it as a header on POST
 * requests (Laravel's web middleware expects X-XSRF-TOKEN for non-GET calls).
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function uid(): string {
    return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

/**
 * Drives a streaming RAG conversation against POST /chat/ask. The endpoint returns
 * newline-delimited JSON; we parse each line as it arrives and update reactive state
 * so the UI renders sources first, then the answer token by token.
 */
export function useChatStream(endpoint: string) {
    const messages = ref<ChatMessage[]>([]);
    const isStreaming = ref(false);
    const error = ref<string | null>(null);

    async function ask(question: string): Promise<void> {
        const trimmed = question.trim();
        if (!trimmed || isStreaming.value) {
            return;
        }

        error.value = null;
        messages.value.push({ id: uid(), role: 'user', content: trimmed });

        const assistant: ChatMessage = {
            id: uid(),
            role: 'assistant',
            content: '',
            sources: [],
            streaming: true,
        };
        messages.value.push(assistant);
        isStreaming.value = true;

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/x-ndjson',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ question: trimmed }),
            });

            if (!response.ok || !response.body) {
                throw new Error(`Request failed (${response.status})`);
            }

            await consume(response.body, assistant);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Something went wrong.';
            assistant.content ||= 'Sorry — I could not complete that request.';
        } finally {
            assistant.streaming = false;
            isStreaming.value = false;
        }
    }

    async function consume(body: ReadableStream<Uint8Array>, assistant: ChatMessage): Promise<void> {
        const reader = body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        for (;;) {
            const { value, done } = await reader.read();
            if (done) {
                break;
            }

            buffer += decoder.decode(value, { stream: true });

            let newline: number;
            while ((newline = buffer.indexOf('\n')) !== -1) {
                const line = buffer.slice(0, newline).trim();
                buffer = buffer.slice(newline + 1);
                if (line) {
                    applyEvent(JSON.parse(line) as StreamEvent, assistant);
                }
            }
        }

        const tail = buffer.trim();
        if (tail) {
            applyEvent(JSON.parse(tail) as StreamEvent, assistant);
        }
    }

    function applyEvent(event: StreamEvent, assistant: ChatMessage): void {
        if (event.type === 'sources') {
            assistant.sources = event.sources as Source[];
        } else if (event.type === 'token') {
            assistant.content += event.value;
        }
    }

    return { messages, isStreaming, error, ask };
}

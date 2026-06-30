export interface Source {
    chunk_id: number;
    document_id: number;
    document_title: string;
    position: number;
    content: string;
    score: number;
}

export interface ChatMessage {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    sources?: Source[];
    streaming?: boolean;
}

/** One newline-delimited JSON record streamed by the `chat.ask` endpoint. */
export type StreamEvent = { type: 'sources'; sources: Source[] } | { type: 'token'; value: string } | { type: 'done' };

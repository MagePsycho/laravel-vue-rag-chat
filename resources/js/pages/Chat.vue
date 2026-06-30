<script setup lang="ts">
import ChatInput from '@/components/chat/ChatInput.vue';
import ChatMessage from '@/components/chat/ChatMessage.vue';
import { useChatStream } from '@/composables/useChatStream';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Sparkles } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    topK: number;
    driver: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ask Docs', href: '/chat' }];

const { messages, isStreaming, error, ask } = useChatStream('/chat/ask');

const examples = ['What database does AskDocs use, and why?', 'How do I add my own documents?', 'What is the MCP server for?'];

const scrollRegion = ref<HTMLElement | null>(null);

watch(
    () => messages.value.map((m) => m.content).join('|'),
    async () => {
        await nextTick();
        scrollRegion.value?.scrollTo({ top: scrollRegion.value.scrollHeight, behavior: 'smooth' });
    },
);
</script>

<template>
    <Head title="Ask Docs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-[calc(100vh-8rem)] w-full max-w-3xl flex-1 flex-col gap-4 p-4">
            <div ref="scrollRegion" class="flex-1 space-y-6 overflow-y-auto rounded-xl p-1">
                <!-- Empty state -->
                <div v-if="messages.length === 0" class="flex h-full flex-col items-center justify-center gap-6 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <Sparkles class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <h1 class="text-xl font-semibold">Ask your documents</h1>
                        <p class="text-sm text-muted-foreground">Retrieval-augmented answers grounded in your knowledge base, with citations.</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2">
                        <button
                            v-for="example in examples"
                            :key="example"
                            type="button"
                            class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground dark:border-sidebar-border"
                            @click="ask(example)"
                        >
                            {{ example }}
                        </button>
                    </div>
                </div>

                <ChatMessage v-for="message in messages" :key="message.id" :message="message" />
            </div>

            <p v-if="error" class="text-center text-sm text-destructive">{{ error }}</p>

            <div class="space-y-2">
                <ChatInput :disabled="isStreaming" @submit="ask" />
                <p class="text-center text-xs text-muted-foreground">
                    Retrieving top {{ props.topK }} chunks · driver: <span class="font-mono">{{ props.driver }}</span>
                </p>
            </div>
        </div>
    </AppLayout>
</template>

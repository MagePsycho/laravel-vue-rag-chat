<script setup lang="ts">
import SourceCitation from '@/components/chat/SourceCitation.vue';
import { cn } from '@/lib/utils';
import type { ChatMessage } from '@/types/chat';
import { Bot, User } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ message: ChatMessage }>();

const isUser = computed(() => props.message.role === 'user');
const hasSources = computed(() => (props.message.sources?.length ?? 0) > 0);
</script>

<template>
    <div :class="cn('flex gap-3', isUser ? 'flex-row-reverse' : 'flex-row')">
        <div
            :class="
                cn(
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                    isUser ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground',
                )
            "
        >
            <User v-if="isUser" class="h-4 w-4" />
            <Bot v-else class="h-4 w-4" />
        </div>

        <div :class="cn('flex max-w-[80%] flex-col gap-2', isUser ? 'items-end' : 'items-start')">
            <div
                :class="
                    cn(
                        'whitespace-pre-wrap rounded-2xl px-4 py-2 text-sm leading-relaxed',
                        isUser ? 'bg-primary text-primary-foreground' : 'border border-sidebar-border/70 bg-card dark:border-sidebar-border',
                    )
                "
            >
                <template v-if="message.content">{{ message.content }}</template>
                <span v-else-if="message.streaming" class="inline-flex gap-1 py-1" aria-label="Thinking">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-current [animation-delay:-0.3s]" />
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-current [animation-delay:-0.15s]" />
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-current" />
                </span>
            </div>

            <div v-if="hasSources" class="flex w-full flex-col gap-1.5">
                <p class="text-xs font-medium text-muted-foreground">Sources</p>
                <SourceCitation v-for="(source, i) in message.sources" :key="source.chunk_id" :index="i + 1" :source="source" />
            </div>
        </div>
    </div>
</template>

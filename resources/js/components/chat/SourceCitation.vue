<script setup lang="ts">
import type { Source } from '@/types/chat';
import { FileText } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    index: number;
    source: Source;
}>();

const scorePercent = computed(() => Math.round(props.source.score * 100));
</script>

<template>
    <details class="group rounded-lg border border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
        <summary class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm">
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                {{ index }}
            </span>
            <FileText class="h-4 w-4 shrink-0 text-muted-foreground" />
            <span class="truncate font-medium">{{ source.document_title }}</span>
            <span class="ml-auto shrink-0 text-xs text-muted-foreground">{{ scorePercent }}% match</span>
        </summary>
        <p class="border-t border-sidebar-border/70 px-3 py-2 text-sm text-muted-foreground dark:border-sidebar-border">
            {{ source.content }}
        </p>
    </details>
</template>

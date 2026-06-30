<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { SendHorizontal } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ disabled?: boolean }>();
const emit = defineEmits<{ submit: [question: string] }>();

const question = ref('');

function submit(): void {
    const value = question.value.trim();
    if (!value || props.disabled) {
        return;
    }
    emit('submit', value);
    question.value = '';
}

// Enter sends; Shift+Enter inserts a newline.
function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submit();
    }
}
</script>

<template>
    <form class="flex items-end gap-2" @submit.prevent="submit">
        <textarea
            v-model="question"
            rows="1"
            placeholder="Ask a question about your documents…"
            class="flex-1 resize-none rounded-lg border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
            :disabled="disabled"
            @keydown="onKeydown"
        />
        <Button type="submit" size="icon" :disabled="disabled || !question.trim()" aria-label="Send">
            <SendHorizontal class="h-4 w-4" />
        </Button>
    </form>
</template>

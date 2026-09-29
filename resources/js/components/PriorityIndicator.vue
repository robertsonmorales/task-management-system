<script setup lang="ts">
import { priorityColorClass } from '@/lib/priority';
import { cn } from '@/lib/utils';
import type { Priority } from '@/types';
import { Flag } from 'lucide-vue-next';

defineProps<{
    priority: Priority;
    class?: string;
}>();
</script>

<template>
    <span :class="cn('inline-flex items-center gap-1.5 text-xs', priority === 'High' || priority === 'Urgent' ? 'font-medium text-foreground' : 'text-muted-foreground', $props.class)">
        <Flag :class="`${priorityColorClass(priority)} size-3.5 shrink-0`" aria-hidden="true" />
        {{ priority }}
    </span>
</template>

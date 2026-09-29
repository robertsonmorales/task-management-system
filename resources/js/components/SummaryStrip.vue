<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

export interface SummaryItem {
    label: string;
    count: number;
    href: string;
    /** Applied to the count when it is above zero, to pull the eye toward exceptions. */
    emphasisClass?: string;
}

defineProps<{
    items: SummaryItem[];
    class?: string;
}>();
</script>

<template>
    <nav
        aria-label="Task summary"
        :class="cn('grid grid-cols-4 gap-px overflow-hidden rounded-xl border border-sidebar-border/70 bg-sidebar-border/70', $props.class)"
    >
        <Link
            v-for="item in items"
            :key="item.label"
            :href="item.href"
            class="flex flex-col gap-0.5 bg-card px-3 py-3 transition-colors hover:bg-muted sm:px-5"
        >
            <span class="truncate text-[11px] font-medium text-muted-foreground sm:text-xs">{{ item.label }}</span>
            <span :class="cn('text-xl font-semibold sm:text-2xl', item.count > 0 && item.emphasisClass)">
                {{ item.count.toLocaleString() }}
            </span>
        </Link>
    </nav>
</template>

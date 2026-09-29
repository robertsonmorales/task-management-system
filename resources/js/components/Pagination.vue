<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { type PaginationLink } from '@/types';
import { router } from '@inertiajs/vue3';

defineProps<{
    links: PaginationLink[];
}>();

function visit(url: string | null) {
    if (!url) {
        return;
    }

    router.get(url, {}, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <nav class="flex items-center gap-1">
        <Button
            v-for="(link, index) in links"
            :key="index"
            variant="outline"
            size="sm"
            :disabled="!link.url"
            :class="link.active && 'bg-accent text-accent-foreground'"
            @click="visit(link.url)"
        >
            <span v-html="link.label" />
        </Button>
    </nav>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import type { AssigneeOption } from '@/types';
import { watchDebounced } from '@vueuse/core';
import { Check, ChevronDown, LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const MIN_SEARCH_LENGTH = 3;

const assignee = defineModel<AssigneeOption | null>({ default: null });

const open = ref(false);
const term = ref('');
const results = ref<AssigneeOption[]>([]);
const isLoading = ref(false);
let abortController: AbortController | null = null;

const hasSearchTerm = computed(() => term.value.trim().length >= MIN_SEARCH_LENGTH);

async function searchUsers(keyword: string) {
    abortController?.abort();

    if (keyword.length < MIN_SEARCH_LENGTH) {
        results.value = [];
        isLoading.value = false;

        return;
    }

    abortController = new AbortController();
    isLoading.value = true;

    try {
        const response = await fetch(route('users.search', { q: keyword }), {
            headers: { Accept: 'application/json' },
            signal: abortController.signal,
        });

        results.value = response.ok ? await response.json() : [];
        isLoading.value = false;
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
            results.value = [];
            isLoading.value = false;
        }
    }
}

watchDebounced(term, (value) => searchUsers(value.trim()), { debounce: 300 });

watch(open, (isOpen) => {
    if (!isOpen) {
        term.value = '';
        results.value = [];
    }
});

function selectAssignee(option: AssigneeOption) {
    assignee.value = option;
    open.value = false;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button type="button" variant="outline" class="w-full justify-between font-normal">
                <span :class="assignee ? '' : 'text-muted-foreground'">{{ assignee?.name ?? 'Select a user' }}</span>
                <ChevronDown class="size-4 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[--radix-popover-trigger-width] p-2" align="start">
            <Input v-model="term" placeholder="Search by name or email..." autocomplete="off" />
            <p class="px-1 pt-2 text-xs text-muted-foreground">Type at least 3 characters to search for a user.</p>

            <div v-if="hasSearchTerm" class="mt-2 max-h-60 overflow-y-auto">
                <div v-if="isLoading" class="flex items-center gap-x-2 px-2 py-2 text-sm text-muted-foreground">
                    <LoaderCircle class="size-4 animate-spin" />
                    Searching...
                </div>
                <p v-else-if="results.length === 0" class="px-2 py-2 text-sm text-muted-foreground">No users found.</p>
                <template v-else>
                    <button
                        v-for="option in results"
                        :key="option.id"
                        type="button"
                        class="flex w-full items-center justify-between rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                        @click="selectAssignee(option)"
                    >
                        <span class="flex flex-col">
                            <span>{{ option.name }}</span>
                            <span class="text-xs text-muted-foreground">{{ option.email }}</span>
                        </span>
                        <Check v-if="assignee?.id === option.id" class="size-4" />
                    </button>
                </template>
            </div>
        </PopoverContent>
    </Popover>
</template>

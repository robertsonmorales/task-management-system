<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        modelValue: number;
        options?: number[];
    }>(),
    {
        options: () => [10, 25, 50, 100],
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number];
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-1">
                {{ modelValue }} / page
                <ChevronDown class="size-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuRadioGroup :model-value="String(modelValue)" @update:model-value="(value) => emit('update:modelValue', Number(value))">
                <DropdownMenuRadioItem v-for="option in options" :key="option" :value="String(option)"> {{ option }} / page </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { DateFormatter, getLocalTimeZone, parseDate, today, type DateValue } from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<{
    modelValue?: string;
    placeholder?: string;
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | undefined];
}>();

const formatter = new DateFormatter('en-US', { dateStyle: 'long' });

const dateValue = computed<DateValue | undefined>(() => (props.modelValue ? parseDate(props.modelValue) : undefined));

const displayValue = computed(() => (dateValue.value ? formatter.format(dateValue.value.toDate(getLocalTimeZone())) : undefined));

function onSelect(value: DateValue | undefined) {
    emit('update:modelValue', value ? value.toString() : undefined);
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                :class="cn('w-full justify-start text-left font-normal', !modelValue && 'text-muted-foreground', props.class)"
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ displayValue ?? placeholder ?? 'Pick a date' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <Calendar :model-value="dateValue" :min-value="today(getLocalTimeZone())" @update:model-value="onSelect" />
        </PopoverContent>
    </Popover>
</template>

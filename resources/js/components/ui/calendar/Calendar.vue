<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import {
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNext,
    CalendarPrev,
    CalendarRoot,
    type CalendarRootEmits,
    type CalendarRootProps,
} from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<CalendarRootProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<CalendarRootEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <CalendarRoot
        v-slot="{ grid, weekDays }"
        v-bind="delegatedProps"
        :class="cn('p-3', props.class)"
        @update:model-value="(value) => emits('update:modelValue', value)"
        @update:placeholder="(value) => emits('update:placeholder', value)"
    >
        <CalendarHeader class="relative flex items-center justify-between pt-1">
            <CalendarPrev
                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-input bg-transparent opacity-70 hover:opacity-100 disabled:pointer-events-none disabled:opacity-30"
            >
                <ChevronLeft class="h-4 w-4" />
            </CalendarPrev>
            <CalendarHeading class="text-sm font-medium" />
            <CalendarNext
                class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-input bg-transparent opacity-70 hover:opacity-100 disabled:pointer-events-none disabled:opacity-30"
            >
                <ChevronRight class="h-4 w-4" />
            </CalendarNext>
        </CalendarHeader>
        <div class="mt-4 flex flex-col gap-y-4">
            <CalendarGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse space-y-1">
                <CalendarGridHead>
                    <CalendarGridRow class="flex">
                        <CalendarHeadCell v-for="day in weekDays" :key="day" class="w-9 rounded-md text-[0.8rem] font-normal text-muted-foreground">
                            {{ day }}
                        </CalendarHeadCell>
                    </CalendarGridRow>
                </CalendarGridHead>
                <CalendarGridBody>
                    <CalendarGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`" class="mt-2 flex w-full">
                        <CalendarCell
                            v-for="weekDate in weekDates"
                            :key="weekDate.toString()"
                            :date="weekDate"
                            class="relative h-9 w-9 p-0 text-center text-sm focus-within:relative focus-within:z-20 [&:has([data-selected])]:rounded-md [&:has([data-selected])]:bg-accent"
                        >
                            <CalendarCellTrigger
                                :day="weekDate"
                                :month="month.value"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-md p-0 text-sm font-normal ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 data-[disabled]:text-muted-foreground data-[disabled]:opacity-50 data-[outside-view]:text-muted-foreground data-[outside-view]:opacity-50 data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[selected]:opacity-100 data-[today]:bg-accent data-[today]:text-accent-foreground"
                            />
                        </CalendarCell>
                    </CalendarGridRow>
                </CalendarGridBody>
            </CalendarGrid>
        </div>
    </CalendarRoot>
</template>

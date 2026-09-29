<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { StatusBreakdown } from '@/types';
import { TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    breakdown: StatusBreakdown;
    /** Describes the period in the empty state, e.g. "this week". */
    periodLabel: string;
}>();

/**
 * Exceptions first. Colors were validated as an adjacent set for color-vision deficiency in light and dark mode;
 * keep this order if you change them.
 */
const SEGMENTS = [
    { key: 'overdue', label: 'Overdue', swatchClass: 'bg-[#d03b3b]' },
    { key: 'pending', label: 'Pending', swatchClass: 'bg-[#eda100] dark:bg-[#c98500]' },
    { key: 'inProgress', label: 'In Progress', swatchClass: 'bg-[#2a78d6] dark:bg-[#3987e5]' },
    { key: 'completed', label: 'Completed', swatchClass: 'bg-[#0ca30c]' },
] as const;

const total = computed(() => SEGMENTS.reduce((sum, segment) => sum + props.breakdown[segment.key], 0));

function percentOf(count: number): number {
    return total.value === 0 ? 0 : Math.round((count / total.value) * 100);
}

const segments = computed(() =>
    SEGMENTS.map((segment) => ({ ...segment, count: props.breakdown[segment.key], percent: percentOf(props.breakdown[segment.key]) })),
);

const chartLabel = computed(() => segments.value.map((segment) => `${segment.label}: ${segment.count}`).join(', '));

const completionRate = computed(() => percentOf(props.breakdown.completed));
</script>

<template>
    <div class="space-y-4">
        <p class="flex items-baseline gap-2">
            <span class="text-3xl font-semibold">{{ total.toLocaleString() }}</span>
            <span class="text-sm text-muted-foreground">
                tasks due {{ periodLabel }}<template v-if="total > 0"> · {{ completionRate }}% completed</template>
            </span>
        </p>

        <TooltipProvider :delay-duration="0">
            <div
                v-if="total > 0"
                class="flex h-3 w-full gap-[2px]"
                role="img"
                :aria-label="chartLabel"
            >
                <template v-for="segment in segments" :key="segment.key">
                    <Tooltip v-if="segment.count > 0">
                        <TooltipTrigger as-child>
                            <div
                                tabindex="0"
                                :class="`${segment.swatchClass} h-full min-w-1 rounded-[3px] transition-opacity hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring`"
                                :style="{ flexGrow: segment.count, flexBasis: 0 }"
                            />
                        </TooltipTrigger>
                        <TooltipContent>
                            <span class="font-semibold">{{ segment.count.toLocaleString() }}</span>
                            <span class="text-muted-foreground"> {{ segment.label }} · {{ segment.percent }}%</span>
                        </TooltipContent>
                    </Tooltip>
                </template>
            </div>
            <div v-else class="h-3 w-full rounded-[3px] bg-muted" aria-hidden="true" />
        </TooltipProvider>

        <!-- Legend doubles as the table view: every value is readable without hovering -->
        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-4">
            <div v-for="segment in segments" :key="segment.key" class="space-y-0.5">
                <dt class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span :class="`${segment.swatchClass} size-2.5 shrink-0 rounded-[2px]`" aria-hidden="true" />
                    {{ segment.label }}
                    <TriangleAlert v-if="segment.key === 'overdue' && segment.count > 0" class="size-3 text-red-600 dark:text-red-400" aria-hidden="true" />
                </dt>
                <dd class="text-sm">
                    <span class="font-semibold">{{ segment.count.toLocaleString() }}</span>
                    <span class="text-muted-foreground"> · {{ segment.percent }}%</span>
                </dd>
            </div>
        </dl>

        <p v-if="total === 0" class="text-xs text-muted-foreground">No tasks are due {{ periodLabel }}.</p>
    </div>
</template>

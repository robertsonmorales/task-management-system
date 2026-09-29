<script setup lang="ts">
import PriorityIndicator from '@/components/PriorityIndicator.vue';
import StatusIndicator from '@/components/StatusIndicator.vue';
import { describeDueDate, dueToneClass } from '@/lib/due-date';
import type { AttentionReason, DashboardTask } from '@/types';
import { ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        tasks: DashboardTask[];
        grouped?: boolean;
        showAssignee?: boolean;
    }>(),
    {
        grouped: false,
        showAssignee: false,
    },
);

const emit = defineEmits<{
    select: [task: DashboardTask];
}>();

const GROUP_LABELS: Record<AttentionReason, string> = {
    overdue: 'Overdue',
    today: 'Due today',
    high_priority: 'High priority',
};

/**
 * Split the tasks into their attention groups, keeping the server's urgency order.
 * Ungrouped lists render as a single group without a heading.
 */
const groups = computed(() => {
    if (!props.grouped) {
        return [{ key: 'all', label: null, tasks: props.tasks }];
    }

    return (Object.keys(GROUP_LABELS) as AttentionReason[])
        .map((reason) => ({ key: reason, label: GROUP_LABELS[reason], tasks: props.tasks.filter((task) => task.attention === reason) }))
        .filter((group) => group.tasks.length > 0);
});

const columnCount = computed(() => (props.showAssignee ? 5 : 4));
</script>

<template>
    <div>
        <!-- Desktop / tablet: table -->
        <table class="hidden w-full table-fixed md:table">
            <colgroup>
                <col />
                <col class="w-24" />
                <col class="w-36" />
                <col class="w-28" />
                <col v-if="showAssignee" class="w-36" />
            </colgroup>
            <thead class="sr-only">
                <tr>
                    <th scope="col">Task</th>
                    <th scope="col">Priority</th>
                    <th scope="col">Due date</th>
                    <th scope="col">Status</th>
                    <th v-if="showAssignee" scope="col">Assignee</th>
                </tr>
            </thead>
            <tbody v-for="group in groups" :key="group.key">
                <tr v-if="group.label">
                    <th
                        :colspan="columnCount"
                        scope="colgroup"
                        class="bg-muted/40 px-4 py-1.5 text-left text-[11px] font-semibold uppercase tracking-wide text-muted-foreground"
                    >
                        {{ group.label }} <span class="font-normal">· {{ group.tasks.length }}</span>
                    </th>
                </tr>
                <tr
                    v-for="task in group.tasks"
                    :key="task.id"
                    class="group cursor-pointer border-t border-sidebar-border/60 transition-colors first:border-t-0 hover:bg-muted/50"
                    @click="emit('select', task)"
                >
                    <td class="px-4 py-3">
                        <button
                            type="button"
                            class="block w-full truncate text-left text-sm font-medium text-foreground focus-visible:underline focus-visible:outline-none"
                            @click.stop="emit('select', task)"
                        >
                            {{ task.task_name }}
                        </button>
                    </td>
                    <td class="px-2 py-3"><PriorityIndicator :priority="task.priority" /></td>
                    <td class="px-2 py-3 text-xs" :class="dueToneClass(describeDueDate(task.due_date).tone)">
                        {{ describeDueDate(task.due_date).label }}
                    </td>
                    <td class="px-2 py-3"><StatusIndicator :status="task.status" /></td>
                    <td v-if="showAssignee" class="truncate px-2 py-3 pr-4 text-xs text-muted-foreground">
                        {{ task.assign_to?.name ?? 'Unassigned' }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Mobile: vertical cards -->
        <div class="md:hidden">
            <section v-for="group in groups" :key="group.key">
                <h3 v-if="group.label" class="bg-muted/40 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ group.label }} <span class="font-normal">· {{ group.tasks.length }}</span>
                </h3>
                <ul>
                    <li v-for="task in group.tasks" :key="task.id" class="border-t border-sidebar-border/60 first:border-t-0">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 px-4 py-3.5 text-left active:bg-muted/60"
                            @click="emit('select', task)"
                        >
                            <div class="min-w-0 flex-1 space-y-1">
                                <PriorityIndicator :priority="task.priority" class="text-[11px] uppercase tracking-wide" />
                                <p class="line-clamp-2 text-[15px] font-medium leading-snug">{{ task.task_name }}</p>
                                <p v-if="showAssignee" class="truncate text-xs text-muted-foreground">
                                    {{ task.assign_to ? `Assigned to ${task.assign_to.name}` : 'Unassigned' }}
                                </p>
                                <div class="flex items-center gap-3 text-xs">
                                    <span :class="dueToneClass(describeDueDate(task.due_date).tone)">{{ describeDueDate(task.due_date).label }}</span>
                                    <StatusIndicator :status="task.status" />
                                </div>
                            </div>
                            <ChevronRight class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>

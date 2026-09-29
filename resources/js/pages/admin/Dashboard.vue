<script setup lang="ts">
import CreateTaskDialog from '@/components/CreateTaskDialog.vue';
import DashboardTaskList from '@/components/DashboardTaskList.vue';
import StatusBreakdownChart from '@/components/StatusBreakdownChart.vue';
import SummaryStrip, { type SummaryItem } from '@/components/SummaryStrip.vue';
import TaskDetailSheet from '@/components/TaskDetailSheet.vue';
import UserWorkloadList from '@/components/UserWorkloadList.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AdminAttention, AdminSummary, AnalyticsPeriod, BreadcrumbItem, StatusBreakdown, Task, UserWorkload } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ChevronDown, ChevronRight, CircleCheck, Flag, Plus, TriangleAlert, UserX } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin-dashboard',
    },
];

const props = defineProps<{
    summary: AdminSummary;
    attention: AdminAttention;
    period: AnalyticsPeriod;
    statusBreakdown: StatusBreakdown;
    overdueTasks: Task[];
    workload: UserWorkload[];
    workloadTotal: number;
}>();

const summaryItems = computed<SummaryItem[]>(() => [
    { label: 'Total Tasks', count: props.summary.total, href: route('tasks.index') },
    { label: 'In Progress', count: props.summary.inProgress, href: route('tasks.index', { status: 'In Progress' }) },
    {
        label: 'Overdue',
        count: props.summary.overdue,
        href: route('tasks.index', { due: 'overdue' }),
        emphasisClass: 'text-red-600 dark:text-red-400',
    },
    { label: 'Completed', count: props.summary.completed, href: route('tasks.index', { status: 'Completed' }) },
]);

const attentionItems = computed(() => [
    {
        key: 'overdue',
        count: props.attention.overdue,
        label: props.attention.overdue === 1 ? 'overdue task' : 'overdue tasks',
        hint: 'Past their due date and not completed',
        href: route('tasks.index', { due: 'overdue' }),
        icon: TriangleAlert,
        iconClass: 'text-red-600 dark:text-red-400',
    },
    {
        key: 'high-priority-today',
        count: props.attention.highPriorityDueToday,
        label: props.attention.highPriorityDueToday === 1 ? 'high-priority task due today' : 'high-priority tasks due today',
        hint: 'High or Urgent, still open',
        href: route('tasks.index', { due: 'today', sort: 'priority', direction: 'desc' }),
        icon: Flag,
        iconClass: 'text-amber-600 dark:text-amber-400',
    },
    {
        key: 'unassigned',
        count: props.attention.unassigned,
        label: props.attention.unassigned === 1 ? 'unassigned task' : 'unassigned tasks',
        hint: 'Open tasks nobody owns',
        href: route('tasks.index', { assignee: 'unassigned' }),
        icon: UserX,
        iconClass: 'text-foreground',
    },
]);

const isAllClear = computed(() => attentionItems.value.every((item) => item.count === 0));

const PERIOD_OPTIONS: { value: AnalyticsPeriod; label: string; phrase: string }[] = [
    { value: 'today', label: 'Today', phrase: 'today' },
    { value: 'week', label: 'This Week', phrase: 'this week' },
    { value: 'month', label: 'This Month', phrase: 'this month' },
];

const currentPeriod = computed(() => PERIOD_OPTIONS.find((option) => option.value === props.period) ?? PERIOD_OPTIONS[1]);

/**
 * Only the chart depends on the period, so reload just its props.
 */
function changePeriod(period: AnalyticsPeriod) {
    router.get(route('admin-dashboard'), { period }, { only: ['period', 'statusBreakdown'], preserveState: true, preserveScroll: true, replace: true });
}

const taskDialogOpen = ref(false);
const editingTask = ref<Task | null>(null);

function openCreateDialog() {
    editingTask.value = null;
    taskDialogOpen.value = true;
}

function openEditDialog(task: Task) {
    editingTask.value = task;
    taskDialogOpen.value = true;
}

const detailOpen = ref(false);
const selectedTask = ref<Task | null>(null);

function openTaskDetail(task: Task) {
    selectedTask.value = task;
    detailOpen.value = true;
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!--
            Mobile reads top to bottom by urgency (attention, metrics, status, overdue, workload) via `order-*`;
            the row wrappers are `contents` until they become grids, so their sections join that ordering.
        -->
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
            <header class="flex items-end justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">Dashboard</h1>
                    <p class="text-sm text-muted-foreground">Overview of tasks and team activity.</p>
                </div>
                <Button size="sm" class="hidden shrink-0 md:inline-flex" @click="openCreateDialog">
                    <Plus />
                    Create Task
                </Button>
            </header>

            <SummaryStrip :items="summaryItems" class="order-2 grid-cols-2 sm:grid-cols-4 lg:order-none" />

            <div class="contents lg:grid lg:grid-cols-3 lg:gap-6">
                <!-- Status overview -->
                <section
                    aria-labelledby="status-heading"
                    class="order-3 rounded-xl border border-sidebar-border/70 bg-card p-4 lg:order-none lg:col-span-2 md:p-5"
                >
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h2 id="status-heading" class="text-base font-semibold">Task status</h2>
                            <p class="text-xs text-muted-foreground">Where the work due {{ currentPeriod.phrase }} stands.</p>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm" class="h-9 shrink-0 gap-1 sm:h-8">
                                    {{ currentPeriod.label }}
                                    <ChevronDown class="size-4 opacity-60" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuRadioGroup :model-value="period" @update:model-value="(value) => changePeriod(value as AnalyticsPeriod)">
                                    <DropdownMenuRadioItem v-for="option in PERIOD_OPTIONS" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </DropdownMenuRadioItem>
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <StatusBreakdownChart :breakdown="statusBreakdown" :period-label="currentPeriod.phrase" />
                </section>

                <!-- Needs attention -->
                <section
                    aria-labelledby="attention-heading"
                    class="order-1 overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm lg:order-none"
                >
                    <div class="border-b border-sidebar-border/70 px-4 py-4">
                        <h2 id="attention-heading" class="text-base font-semibold">Needs Attention</h2>
                        <p class="text-xs text-muted-foreground">Where to step in.</p>
                    </div>

                    <div v-if="isAllClear" class="flex items-center gap-3 px-4 py-4">
                        <CircleCheck class="size-5 shrink-0 text-green-600" aria-hidden="true" />
                        <p class="text-sm">Nothing needs intervention right now.</p>
                    </div>

                    <ul v-else>
                        <li v-for="item in attentionItems" :key="item.key" class="border-t border-sidebar-border/60 first:border-t-0">
                            <Link
                                :href="item.href"
                                :class="[
                                    'flex items-center gap-3 px-4 py-3.5 transition-colors hover:bg-muted/50',
                                    item.count === 0 && 'text-muted-foreground',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    :class="['size-4 shrink-0', item.count > 0 ? item.iconClass : 'text-muted-foreground']"
                                    aria-hidden="true"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm">
                                        <span :class="item.count > 0 && 'text-lg font-semibold'">{{ item.count.toLocaleString() }}</span>
                                        {{ item.label }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ item.hint }}</p>
                                </div>
                                <ChevronRight class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>

            <div class="contents xl:grid xl:grid-cols-5 xl:gap-6">
                <!-- Overdue tasks -->
                <section
                    aria-labelledby="overdue-heading"
                    class="order-4 xl:self-start overflow-hidden rounded-xl border border-sidebar-border/70 bg-card lg:order-none xl:col-span-3"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-sidebar-border/70 px-4 py-3">
                        <div>
                            <h2 id="overdue-heading" class="text-sm font-semibold">Overdue Tasks</h2>
                            <p class="text-xs text-muted-foreground">Highest priority first, then the longest overdue.</p>
                        </div>
                        <Link
                            :href="route('tasks.index', { due: 'overdue' })"
                            class="inline-flex shrink-0 items-center gap-1 py-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                        >
                            View all<span class="hidden sm:inline">&nbsp;overdue tasks</span>
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>

                    <DashboardTaskList v-if="overdueTasks.length" :tasks="overdueTasks" show-assignee @select="openTaskDetail" />

                    <div v-else class="flex items-center gap-3 px-4 py-6">
                        <CircleCheck class="size-5 shrink-0 text-green-600" aria-hidden="true" />
                        <p class="text-sm text-muted-foreground">No overdue tasks.</p>
                    </div>

                    <p v-if="summary.overdue > overdueTasks.length" class="border-t border-sidebar-border/70 px-4 py-2.5 text-xs text-muted-foreground">
                        Showing {{ overdueTasks.length }} of {{ summary.overdue.toLocaleString() }} overdue tasks.
                    </p>
                </section>

                <!-- User workload -->
                <section
                    aria-labelledby="workload-heading"
                    class="order-5 xl:self-start overflow-hidden rounded-xl border border-sidebar-border/70 bg-card lg:order-none xl:col-span-2"
                >
                    <div class="border-b border-sidebar-border/70 px-4 py-3">
                        <h2 id="workload-heading" class="text-sm font-semibold">User Workload</h2>
                        <p class="text-xs text-muted-foreground">Open tasks per user, most overdue first.</p>
                    </div>

                    <div class="pt-3">
                        <UserWorkloadList v-if="workload.length" :users="workload" />
                        <p v-else class="px-4 pb-4 text-sm text-muted-foreground">No users yet.</p>
                    </div>

                    <p v-if="workloadTotal > workload.length" class="border-t border-sidebar-border/70 px-4 py-2.5 text-xs text-muted-foreground">
                        Showing the {{ workload.length }} busiest of {{ workloadTotal.toLocaleString() }} users.
                    </p>
                </section>
            </div>
        </div>

        <TaskDetailSheet v-model:open="detailOpen" :task="selectedTask" @edit="openEditDialog" />
        <CreateTaskDialog v-model:open="taskDialogOpen" :task="editingTask" />
    </AppLayout>
</template>

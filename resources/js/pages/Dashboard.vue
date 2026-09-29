<script setup lang="ts">
import CreateTaskDialog from '@/components/CreateTaskDialog.vue';
import DashboardTaskList from '@/components/DashboardTaskList.vue';
import SummaryStrip, { type SummaryItem } from '@/components/SummaryStrip.vue';
import TaskDetailSheet from '@/components/TaskDetailSheet.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, DashboardSummary, DashboardTask, SharedData, Task } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, CircleCheck, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const props = defineProps<{
    summary: DashboardSummary;
    attentionTasks: DashboardTask[];
    attentionTotal: number;
    myTasks: DashboardTask[];
}>();

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user.user_role_id === 1);
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const greeting = computed(() => {
    const hour = new Date().getHours();

    return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
});

const summaryItems = computed<SummaryItem[]>(() => [
    {
        label: 'Overdue',
        count: props.summary.overdue,
        href: route('tasks.index', { due: 'overdue' }),
        emphasisClass: 'text-red-600 dark:text-red-400',
    },
    {
        label: 'Due Today',
        count: props.summary.dueToday,
        href: route('tasks.index', { due: 'today' }),
        emphasisClass: 'text-amber-600 dark:text-amber-400',
    },
    {
        label: 'Upcoming',
        count: props.summary.upcoming,
        href: route('tasks.index', { due: 'next_7_days' }),
    },
    {
        label: 'My Tasks',
        count: props.summary.open,
        href: route('tasks.index'),
    },
]);

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
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 md:gap-8 md:p-6 lg:p-8">
            <!-- Header -->
            <header class="flex items-end justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Dashboard</h1>
                    <p class="text-2xl font-semibold tracking-tight md:text-3xl">{{ greeting }}, {{ firstName }}</p>
                    <p class="text-sm text-muted-foreground">Here's what needs your attention today.</p>
                </div>
                <Button size="sm" class="hidden shrink-0 md:inline-flex" @click="openCreateDialog">
                    <!-- <Plus /> -->
                    Create Task
                </Button>
            </header>

            <!-- Summary indicators -->
            <SummaryStrip :items="summaryItems" />

            <!-- Needs your attention (primary) -->
            <section aria-labelledby="attention-heading" class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-sidebar-border/70 px-4 py-4">
                    <div>
                        <h2 id="attention-heading" class="text-base font-semibold">Needs your attention</h2>
                        <p class="text-xs text-muted-foreground">Overdue, due today, and high-priority tasks, most urgent first.</p>
                    </div>
                    <Link
                        :href="route('tasks.index', { sort: 'due_date', direction: 'asc' })"
                        class="inline-flex shrink-0 items-center gap-1 py-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                    >
                        View all
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <DashboardTaskList v-if="attentionTasks.length" :tasks="attentionTasks" grouped :show-assignee="isAdmin" @select="openTaskDetail" />

                <div v-else class="flex flex-col items-center gap-2 px-4 py-10 text-center">
                    <CircleCheck class="size-8 text-green-600" aria-hidden="true" />
                    <p class="text-sm font-medium">You're all caught up</p>
                    <p class="text-xs text-muted-foreground">Nothing is overdue, due today, or high priority.</p>
                </div>

                <p
                    v-if="attentionTotal > attentionTasks.length"
                    class="border-t border-sidebar-border/70 px-4 py-2.5 text-xs text-muted-foreground"
                >
                    Showing {{ attentionTasks.length }} of {{ attentionTotal }} tasks that need attention.
                </p>
            </section>

            <!-- My tasks (secondary) -->
            <section aria-labelledby="my-tasks-heading" class="overflow-hidden rounded-xl border border-sidebar-border/70">
                <div class="flex items-start justify-between gap-4 border-b border-sidebar-border/70 px-4 py-3">
                    <div>
                        <h2 id="my-tasks-heading" class="text-sm font-semibold">My Tasks</h2>
                        <p class="text-xs text-muted-foreground">Your other open tasks, soonest due first.</p>
                    </div>
                    <Link
                        :href="route('tasks.index')"
                        class="inline-flex shrink-0 items-center gap-1 py-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                    >
                        View all
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <DashboardTaskList v-if="myTasks.length" :tasks="myTasks" :show-assignee="isAdmin" @select="openTaskDetail" />

                <div v-else class="flex flex-col items-center gap-3 px-4 py-8 text-center">
                    <p class="text-xs text-muted-foreground">
                        {{ summary.open === 0 ? 'You have no open tasks.' : 'Everything else you have open is listed above.' }}
                    </p>
                    <Button v-if="summary.open === 0" size="sm" variant="outline" @click="openCreateDialog">
                        <Plus />
                        Create Task
                    </Button>
                </div>
            </section>
        </div>

        <TaskDetailSheet v-model:open="detailOpen" :task="selectedTask" @edit="openEditDialog" />
        <CreateTaskDialog v-model:open="taskDialogOpen" :task="editingTask" />
    </AppLayout>
</template>

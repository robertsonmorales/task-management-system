<script setup lang="ts">
import CreateTaskDialog from '@/components/CreateTaskDialog.vue';
import DeleteTaskDialog from '@/components/DeleteTaskDialog.vue';
import FilterTasksDialog, { type AppliedFilters } from '@/components/FilterTasksDialog.vue';
import PageSizeDropdown from '@/components/PageSizeDropdown.vue';
import Pagination from '@/components/Pagination.vue';
import Button from '@/components/ui/button/Button.vue';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { dueDateLabel } from '@/lib/due-date';
import { priorityColorClass } from '@/lib/priority';
import { statusBadgeClass } from '@/lib/status';
import { type BreadcrumbItem, type Paginated, type Task, type TaskFilters, type TaskSortColumn } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { ArrowDown, ArrowUp, ArrowUpDown, EllipsisVertical, Flag, ListFilter, Pencil, Search, Trash, Check, X, Notebook } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
];

const props = defineProps<{
    tasks: Paginated<Task>;
    filters: TaskFilters;
    canSortByAssignee: boolean;
}>();

type QueryOverrides = Partial<Record<keyof TaskFilters | 'per_page', string | number | null>>;

/**
 * Reload the task list, keeping the current search, filters and page size unless overridden.
 */
function visit(overrides: QueryOverrides = {}) {
    const query: Record<string, string | number | null> = {
        ...props.filters,
        search: search.value,
        per_page: props.tasks.per_page,
        ...overrides,
        page: 1,
    };

    const cleanedQuery = Object.fromEntries(Object.entries(query).filter(([, value]) => value !== null && value !== ''));

    router.get(route('tasks.index'), cleanedQuery, { preserveState: true, preserveScroll: true, replace: true });
}

function handlePerPageChange(perPage: number) {
    visit({ per_page: perPage });
}

const search = ref(props.filters.search ?? '');
const isSearchOpen = ref(search.value !== '');
const searchInput = ref<InstanceType<typeof Input> | null>(null);

watchDebounced(search, (keyword) => visit({ search: keyword.trim() }), { debounce: 300 });

async function openSearch() {
    isSearchOpen.value = true;

    await nextTick();
    searchInput.value?.$el.focus();
}

function collapseSearchIfEmpty() {
    if (search.value.trim() === '') {
        isSearchOpen.value = false;
    }
}

function clearSearch() {
    search.value = '';
    isSearchOpen.value = false;
}

const filterDialogOpen = ref(false);

function applyFilters(filters: AppliedFilters) {
    visit(filters);
}

const activeFilterChips = computed(() => {
    const chips: { key: keyof AppliedFilters; label: string }[] = [];

    if (props.filters.priority) {
        chips.push({ key: 'priority', label: `Priority: ${props.filters.priority}` });
    }

    if (props.filters.status) {
        chips.push({ key: 'status', label: `Status: ${props.filters.status}` });
    }

    if (props.filters.due) {
        chips.push({ key: 'due', label: `Due: ${dueDateLabel(props.filters.due)}` });
    }

    return chips;
});

function removeFilter(key: keyof AppliedFilters) {
    visit({ [key]: null });
}

function clearAllFilters() {
    visit({ priority: null, status: null, due: null });
}

interface TableColumn {
    key: TaskSortColumn;
    label: string;
    sortable: boolean;
}

const columns = computed<TableColumn[]>(() => [
    { key: 'task_name', label: 'Name', sortable: true },
    { key: 'assignee', label: 'Assignee', sortable: props.canSortByAssignee },
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'priority', label: 'Priority', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
]);

function sortDirectionOf(column: TaskSortColumn) {
    return props.filters.sort === column ? props.filters.direction : null;
}

/**
 * Cycle a column through ascending, descending, then back to the default order.
 */
function toggleSort(column: TaskSortColumn) {
    const nextSort = {
        null: { sort: column, direction: 'asc' },
        asc: { sort: column, direction: 'desc' },
        desc: { sort: null, direction: null },
    }[sortDirectionOf(column) ?? 'null'];

    visit(nextSort);
}

function ariaSortOf(column: TableColumn) {
    if (!column.sortable) {
        return undefined;
    }

    const direction = sortDirectionOf(column.key);

    return direction === 'asc' ? 'ascending' : direction === 'desc' ? 'descending' : 'none';
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

const deleteDialogOpen = ref(false);
const deletingTask = ref<Task | null>(null);

function openDeleteDialog(task: Task) {
    deletingTask.value = task;
    deleteDialogOpen.value = true;
}
</script>

<template>
    <Head title="Tasks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between">
                <h1 class="text-2xl font-bold">Tasks</h1>

                <div class="flex items-center gap-2">
                    <div :class="['relative transition-[width] duration-300 ease-in-out', isSearchOpen ? 'w-64' : 'w-9']">
                        <template v-if="isSearchOpen">
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                ref="searchInput"
                                v-model="search"
                                placeholder="Search tasks..."
                                class="h-9 px-8"
                                @blur="collapseSearchIfEmpty"
                                @keydown.esc="clearSearch"
                            />
                            <button
                                v-if="search"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                aria-label="Clear search"
                                @mousedown.prevent
                                @click="clearSearch"
                            >
                                <X class="size-4" />
                            </button>
                        </template>
                        <Button v-else variant="outline" class="size-9 p-0" aria-label="Search tasks" @click="openSearch">
                            <Search class="size-4" />
                        </Button>
                    </div>
                    <Button variant="outline" size="sm" class="gap-1" @click="filterDialogOpen = true">
                        <ListFilter class="size-4" />
                        Filter
                    </Button>
                    <Button size="sm" @click="openCreateDialog">Add Task</Button>
                </div>
            </div>

            <div v-if="activeFilterChips.length" class="flex flex-wrap items-center gap-2">
                <span
                    v-for="chip in activeFilterChips"
                    :key="chip.key"
                    class="flex items-center gap-1 rounded-full border border-sidebar-border bg-muted px-3 py-1 text-xs font-medium"
                >
                    {{ chip.label }}
                    <button
                        type="button"
                        class="rounded-full text-muted-foreground hover:text-foreground"
                        :aria-label="`Remove ${chip.label} filter`"
                        @click="removeFilter(chip.key)"
                    >
                        <X class="size-3" />
                    </button>
                </span>
                <button type="button" class="text-xs text-muted-foreground underline-offset-4 hover:underline" @click="clearAllFilters">
                    Clear all
                </button>
            </div>

            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                :aria-sort="ariaSortOf(column)"
                                class="border-b border-sidebar-border px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400"
                            >
                                <button
                                    v-if="column.sortable"
                                    type="button"
                                    class="group inline-flex items-center gap-1 hover:text-foreground"
                                    @click="toggleSort(column.key)"
                                >
                                    {{ column.label }}
                                    <ArrowUp v-if="sortDirectionOf(column.key) === 'asc'" class="size-3.5 text-foreground" />
                                    <ArrowDown v-else-if="sortDirectionOf(column.key) === 'desc'" class="size-3.5 text-foreground" />
                                    <ArrowUpDown v-else class="size-3.5 opacity-40 group-hover:opacity-100" />
                                </button>
                                <template v-else>{{ column.label }}</template>
                            </th>
                            <th
                                class="border-b border-sidebar-border px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400"
                            ></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="task in tasks.data" :key="task.id">
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm font-medium">
                                <div class="cursor-pointer hover:text-blue-900" @click="openEditDialog(task)">{{ task.task_name }}</div>
                            </td>
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm font-medium">
                                {{ task.assign_to?.name ?? 'Unassigned' }}
                            </td>
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm">{{ task.due_date }}</td>
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm">
                                <div class="flex items-center gap-x-2">
                                    <Flag :class="`${priorityColorClass(task.priority)} size-4`" />
                                    <span>{{ task.priority }}</span>
                                </div>
                            </td>
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm">
                                <div :class="`${statusBadgeClass(task.status)}`">
                                    {{ task.status }}
                                </div>
                            </td>
                            <td>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" class="size-8">
                                            <EllipsisVertical class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        class="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg" side="bottom" align="end" :side-offset="4"
                                    >
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem :as-child="true">
                                                <Button
                                                    variant="ghost"
                                                    class="flex w-full cursor-pointer items-center justify-start"
                                                    @click="openEditDialog(task)"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    <span>Edit Task</span>
                                                </Button>
                                            </DropdownMenuItem>
                                        </DropdownMenuGroup>
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem :as-child="true">
                                                <Button
                                                    variant="ghost"
                                                    class="flex w-full cursor-pointer items-center justify-start"
                                                >
                                                    <Check class="mr-2 h-4 w-4" />
                                                    <span>Mark as Completed</span>
                                                </Button>
                                            </DropdownMenuItem>
                                        </DropdownMenuGroup>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem :as-child="true">
                                            <Button
                                                variant="ghost"
                                                class="flex w-full cursor-pointer items-center justify-start text-red-600"
                                                @click="openDeleteDialog(task)"
                                            >
                                                <Trash class="mr-2 h-4 w-4" />
                                                <span>Delete</span>
                                            </Button>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                        <tr v-if="tasks.data.length <= 0">
                            <td colspan="6" class="text-center w-full h-full py-10">
                                <div class="flex items-center flex-col gap-4">
                                    <Notebook />
                                    <p class="text-sm text-neutral-600">You have no assigned Task yet.</p>
                                    <Button size="sm" @click="openCreateDialog">Add Task</Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>

            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-gray-400">Showing {{ tasks.from ?? 0 }}–{{ tasks.to ?? 0 }} of {{ tasks.total }}</p>
                <div class="flex items-center gap-2">
                    <PageSizeDropdown :model-value="tasks.per_page" @update:model-value="handlePerPageChange" />
                    <Pagination :links="tasks.links" />
                </div>
            </div>
        </div>

        <CreateTaskDialog v-model:open="taskDialogOpen" :task="editingTask" />
        <DeleteTaskDialog v-model:open="deleteDialogOpen" :task="deletingTask" />
        <FilterTasksDialog v-model:open="filterDialogOpen" :filters="filters" @apply="applyFilters" />
    </AppLayout>
</template>

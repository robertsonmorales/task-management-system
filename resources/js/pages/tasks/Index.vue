<script setup lang="ts">
import CreateTaskDialog from '@/components/CreateTaskDialog.vue';
import DeleteTaskDialog from '@/components/DeleteTaskDialog.vue';
import PageSizeDropdown from '@/components/PageSizeDropdown.vue';
import Pagination from '@/components/Pagination.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { priorityColorClass } from '@/lib/priority';
import { statusBadgeClass } from '@/lib/status';
import { type BreadcrumbItem, type Paginated, type Task } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { EllipsisVertical, Flag, Pencil, Trash, Check } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
];

defineProps<{
    tasks: Paginated<Task>;
}>();

function handlePerPageChange(perPage: number) {
    router.get(route('tasks.index'), { per_page: perPage, page: 1 }, { preserveState: true, preserveScroll: true, replace: true });
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
                <h1 class="text-2xl font-bold">My Tasks</h1>

                <div class="flex items-center">
                    <!-- Add a Simple Search bar here -->
                    <Button size="sm" @click="openCreateDialog">Add Task</Button>
                </div>
            </div>

            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="border-b border-sidebar-border px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">
                                Title
                            </th>
                            <th class="border-b border-sidebar-border px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">
                                Due Date
                            </th>
                            <th class="border-b border-sidebar-border px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">
                                Status
                            </th>
                            <th class="border-b border-sidebar-border px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">
                                Priority
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
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm">{{ task.due_date }}</td>
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm">
                                <div :class="`${statusBadgeClass(task.status)}`">
                                    {{ task.status }}
                                </div>
                            </td>
                            <td class="border-b border-sidebar-border px-4 py-2 text-sm">
                                <div class="flex items-center gap-x-2">
                                    <Flag :class="`${priorityColorClass(task.priority)} size-4`" />
                                    <span>{{ task.priority }}</span>
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
                                                    @click="openEditDialog(task)"
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
    </AppLayout>
</template>

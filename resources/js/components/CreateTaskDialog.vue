<script setup lang="ts">
import AssigneeSearchSelect from '@/components/AssigneeSearchSelect.vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PRIORITY_OPTIONS, priorityColorClass } from '@/lib/priority';
import { STATUS_OPTIONS, statusBadgeClass } from '@/lib/status';
import type { AssigneeOption, Priority, SharedData, Status, Task } from '@/types';
import { ChevronDown, Flag } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

export interface NewTaskPayload {
    task_name: string;
    task_description: string;
    due_date: string;
    priority: Priority;
    assign_to?: number;
}

export interface UpdateTaskPayload extends NewTaskPayload {
    id: number;
    status: Status;
}

const props = defineProps<{
    task?: Task | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    created: [payload: NewTaskPayload];
    updated: [payload: UpdateTaskPayload];
}>();

interface FormState {
    task_name: string;
    task_description: string;
    due_date: string | undefined;
    priority: Priority;
    status: Status;
    assignee: AssigneeOption | null;
}

function defaultForm(): FormState {
    return {
        task_name: '',
        task_description: '',
        due_date: undefined,
        priority: 'Normal',
        status: 'Pending',
        assignee: null,
    };
}

// TaskController@index currently returns due_date as 'MM/DD/YYYY'; the date picker needs ISO 'YYYY-MM-DD'.
function mdyToIso(mdy: string): string | undefined {
    const [month, day, year] = mdy.split('/');

    return month && day && year ? `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}` : undefined;
}

function formStateFromTask(task: Task): FormState {
    return {
        task_name: task.task_name,
        task_description: task.task_description,
        due_date: mdyToIso(task.due_date),
        priority: task.priority,
        status: task.status,
        assignee: task.assign_to ? { id: task.assign_to.id, name: task.assign_to.name, email: '' } : null,
    };
}

const isEditMode = computed(() => !!props.task?.id);

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user.user_role_id === 1);

const form = reactive<FormState>(defaultForm());
const errors = reactive<Partial<Record<keyof FormState, string>>>({});

function clearErrors() {
    (Object.keys(errors) as (keyof FormState)[]).forEach((key) => delete errors[key]);
}

watch(open, (isOpen) => {
    if (isOpen) {
        Object.assign(form, props.task ? formStateFromTask(props.task) : defaultForm());
        clearErrors();
    }
});

function stripHtml(html: string): string {
    return html.replace(/<[^>]*>/g, '').trim();
}

function validate(): boolean {
    errors.task_name = form.task_name.trim() ? undefined : 'Task name is required.';
    errors.task_description = stripHtml(form.task_description) ? undefined : 'Task description is required.';
    errors.due_date = form.due_date ? undefined : 'Due date is required.';
    errors.priority = form.priority ? undefined : 'Priority is required.';
    errors.assignee = !isAdmin.value || form.assignee ? undefined : 'Assignee is required.';

    return !Object.values(errors).some(Boolean);
}

function submitTask() {
    if (!validate() || !form.due_date) {
        return;
    }

    const basePayload: NewTaskPayload = {
        task_name: form.task_name.trim(),
        task_description: form.task_description,
        due_date: form.due_date,
        priority: form.priority,
        ...(isAdmin.value && form.assignee ? { assign_to: form.assignee.id } : {}),
    };

    if (isEditMode.value && props.task) {
        const payload: UpdateTaskPayload = { ...basePayload, id: props.task.id, status: form.status };

        useForm(payload as Record<string, any>).put(route('tasks.update', String(payload.id)));

        emit('updated', payload);
    } else {
        useForm(basePayload as Record<string, any>).post(route('tasks.store'))

        emit('created', basePayload);
    }

    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="space-y-6" @submit.prevent="submitTask">
                <DialogHeader>
                    <DialogTitle>{{ isEditMode ? 'Edit Task' : 'Create Task' }}</DialogTitle>
                    <DialogDescription>
                        {{
                            isEditMode
                                ? 'Update the details below and save your changes.'
                                : 'Fill out the details below to add a new task to your list.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="task_name">Task Name</Label>
                    <Input id="task_name" v-model="form.task_name" placeholder="e.g. Write project proposal" />
                    <InputError :message="errors.task_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_description">Task Description</Label>
                    <RichTextEditor id="task_description" v-model="form.task_description" placeholder="Add more details..." />
                    <InputError :message="errors.task_description" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label>Due Date</Label>
                        <DatePicker v-model="form.due_date" />
                        <InputError :message="errors.due_date" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Priority Level</Label>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button type="button" variant="outline" class="w-full justify-between">
                                    <span class="flex items-center gap-x-2">
                                        <Flag :class="`${priorityColorClass(form.priority)} size-4`" />
                                        {{ form.priority }}
                                    </span>
                                    <ChevronDown class="size-4 opacity-50" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width]" align="start">
                                <DropdownMenuRadioGroup
                                    :model-value="form.priority"
                                    @update:model-value="(value) => (form.priority = value as Priority)"
                                >
                                    <DropdownMenuRadioItem v-for="option in PRIORITY_OPTIONS" :key="option" :value="option">
                                        <Flag :class="`${priorityColorClass(option)} mr-2 size-4`" />
                                        {{ option }}
                                    </DropdownMenuRadioItem>
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <InputError :message="errors.priority" />
                    </div>
                </div>

                <div v-if="isAdmin" class="grid gap-2">
                    <Label>Assign To</Label>
                    <AssigneeSearchSelect v-model="form.assignee" />
                    <InputError :message="errors.assignee" />
                </div>

                <div v-if="isEditMode" class="grid gap-2">
                    <Label>Status</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-between">
                                <span :class="statusBadgeClass(form.status)">{{ form.status }}</span>
                                <ChevronDown class="size-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width]" align="start">
                            <DropdownMenuRadioGroup :model-value="form.status" @update:model-value="(value) => (form.status = value as Status)">
                                <DropdownMenuRadioItem v-for="option in STATUS_OPTIONS" :key="option" :value="option">
                                    <span :class="statusBadgeClass(option)">{{ option }}</span>
                                </DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit">{{ isEditMode ? 'Save Changes' : 'Create Task' }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

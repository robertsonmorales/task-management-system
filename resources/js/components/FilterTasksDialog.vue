<script setup lang="ts">
import AssigneeSearchSelect from '@/components/AssigneeSearchSelect.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { DUE_DATE_OPTIONS, dueDateLabel } from '@/lib/due-date';
import { PRIORITY_OPTIONS, priorityColorClass } from '@/lib/priority';
import { STATUS_OPTIONS, statusBadgeClass } from '@/lib/status';
import type { AssigneeFilter, AssigneeOption, Priority, SharedData, Status, TaskFilters } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown, Flag } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';

export type AppliedFilters = Pick<TaskFilters, 'priority' | 'status' | 'due' | 'assignee'>;

const UNASSIGNED: AssigneeFilter = { id: 'unassigned', name: 'Unassigned' };

const ANY = 'any';

const props = defineProps<{
    filters: TaskFilters;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    apply: [filters: AppliedFilters];
}>();

const form = reactive<AppliedFilters>({ priority: null, status: null, due: null, assignee: null });

watch(open, (isOpen) => {
    if (isOpen) {
        Object.assign(form, {
            priority: props.filters.priority,
            status: props.filters.status,
            due: props.filters.due,
            assignee: props.filters.assignee,
        });
    }
});

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user.user_role_id === 1);

const isUnassignedOnly = computed({
    get: () => form.assignee?.id === UNASSIGNED.id,
    set: (checked: boolean) => (form.assignee = checked ? UNASSIGNED : null),
});

/**
 * Bridge the filter's string id to the user picker's numeric one.
 */
const selectedAssignee = computed<AssigneeOption | null>({
    get: () => (form.assignee && !isUnassignedOnly.value ? { id: Number(form.assignee.id), name: form.assignee.name, email: '' } : null),
    set: (user) => (form.assignee = user ? { id: String(user.id), name: user.name } : null),
});

function fromRadioValue(value: unknown): string | null {
    return value === ANY ? null : (value as string);
}

function resetFilters() {
    Object.assign(form, { priority: null, status: null, due: null, assignee: null });
}

function applyFilters() {
    emit('apply', { ...form });

    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="max-sm:bottom-0 max-sm:top-auto max-sm:max-h-[90dvh] max-sm:translate-y-0 max-sm:overflow-y-auto max-sm:rounded-t-2xl sm:max-w-md"
        >
            <form class="space-y-6" @submit.prevent="applyFilters">
                <DialogHeader>
                    <DialogTitle>Filter Tasks</DialogTitle>
                    <DialogDescription>
                        Narrow down your tasks by priority, status, due date{{ isAdmin ? ', or assignee' : '' }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label>Priority</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-between">
                                <span v-if="form.priority" class="flex items-center gap-x-2">
                                    <Flag :class="`${priorityColorClass(form.priority)} size-4`" />
                                    {{ form.priority }}
                                </span>
                                <span v-else class="text-muted-foreground">Any priority</span>
                                <ChevronDown class="size-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width]" align="start">
                            <DropdownMenuRadioGroup
                                :model-value="form.priority ?? ANY"
                                @update:model-value="(value) => (form.priority = fromRadioValue(value) as Priority | null)"
                            >
                                <DropdownMenuRadioItem :value="ANY">Any priority</DropdownMenuRadioItem>
                                <DropdownMenuRadioItem v-for="option in PRIORITY_OPTIONS" :key="option" :value="option">
                                    <Flag :class="`${priorityColorClass(option)} mr-2 size-4`" />
                                    {{ option }}
                                </DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div class="grid gap-2">
                    <Label>Status</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-between">
                                <span v-if="form.status" :class="statusBadgeClass(form.status)">{{ form.status }}</span>
                                <span v-else class="text-muted-foreground">Any status</span>
                                <ChevronDown class="size-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width]" align="start">
                            <DropdownMenuRadioGroup
                                :model-value="form.status ?? ANY"
                                @update:model-value="(value) => (form.status = fromRadioValue(value) as Status | null)"
                            >
                                <DropdownMenuRadioItem :value="ANY">Any status</DropdownMenuRadioItem>
                                <DropdownMenuRadioItem v-for="option in STATUS_OPTIONS" :key="option" :value="option">
                                    <span :class="statusBadgeClass(option)">{{ option }}</span>
                                </DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div class="grid gap-2">
                    <Label>Due Date</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button type="button" variant="outline" class="w-full justify-between">
                                <span v-if="form.due">{{ dueDateLabel(form.due) }}</span>
                                <span v-else class="text-muted-foreground">Any due date</span>
                                <ChevronDown class="size-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width]" align="start">
                            <DropdownMenuRadioGroup :model-value="form.due ?? ANY" @update:model-value="(value) => (form.due = fromRadioValue(value))">
                                <DropdownMenuRadioItem :value="ANY">Any due date</DropdownMenuRadioItem>
                                <DropdownMenuRadioItem v-for="option in DUE_DATE_OPTIONS" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div v-if="isAdmin" class="grid gap-2">
                    <Label>Assignee</Label>
                    <AssigneeSearchSelect v-if="!isUnassignedOnly" v-model="selectedAssignee" />
                    <label class="flex min-h-9 items-center gap-2 text-sm">
                        <Checkbox :checked="isUnassignedOnly" @update:checked="(checked: boolean) => (isUnassignedOnly = checked)" />
                        Only unassigned tasks
                    </label>
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="resetFilters">Reset</Button>
                    <Button type="submit">Apply Filters</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

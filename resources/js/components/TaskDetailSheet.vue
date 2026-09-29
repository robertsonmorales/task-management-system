<script setup lang="ts">
import PriorityIndicator from '@/components/PriorityIndicator.vue';
import RichTextContent from '@/components/RichTextContent.vue';
import StatusIndicator from '@/components/StatusIndicator.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { describeDueDate, dueToneClass, parseDueDate } from '@/lib/due-date';
import type { Task } from '@/types';
import { router } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { Check, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    task: Task | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    edit: [task: Task];
}>();

const isDesktop = useMediaQuery('(min-width: 768px)');

const due = computed(() => (props.task ? describeDueDate(props.task.due_date) : null));

const fullDueDate = computed(() =>
    props.task
        ? parseDueDate(props.task.due_date)?.toLocaleDateString(undefined, { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' })
        : null,
);

const isCompleting = ref(false);

function markComplete() {
    if (!props.task) {
        return;
    }

    router.patch(
        route('tasks.complete', String(props.task.id)),
        {},
        {
            preserveScroll: true,
            onStart: () => (isCompleting.value = true),
            onFinish: () => (isCompleting.value = false),
            onSuccess: () => (open.value = false),
        },
    );
}

function editTask() {
    if (!props.task) {
        return;
    }

    open.value = false;
    emit('edit', props.task);
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            :side="isDesktop ? 'right' : 'bottom'"
            :class="isDesktop ? 'flex w-full flex-col sm:max-w-md' : 'flex max-h-[88vh] flex-col rounded-t-2xl'"
        >
            <template v-if="task">
                <div v-if="!isDesktop" class="mx-auto -mt-2 h-1.5 w-10 rounded-full bg-muted" aria-hidden="true" />

                <SheetHeader class="space-y-3 pr-6 text-left">
                    <div class="flex items-center gap-4">
                        <PriorityIndicator :priority="task.priority" />
                        <StatusIndicator :status="task.status" />
                    </div>
                    <SheetTitle class="text-xl leading-snug">{{ task.task_name }}</SheetTitle>
                    <SheetDescription class="sr-only">Task details</SheetDescription>
                </SheetHeader>

                <div class="-mx-6 flex-1 space-y-6 overflow-y-auto px-6">
                    <dl class="grid grid-cols-[7rem_1fr] gap-x-4 gap-y-3 rounded-lg border border-sidebar-border/70 p-4 text-sm">
                        <dt class="text-muted-foreground">Due date</dt>
                        <dd>
                            <span :class="due ? dueToneClass(due.tone) : ''">{{ due?.label }}</span>
                            <span v-if="due?.tone !== 'later'" class="block text-xs text-muted-foreground">{{ fullDueDate }}</span>
                        </dd>

                        <dt class="text-muted-foreground">Assignee</dt>
                        <dd>{{ task.assign_to?.name ?? 'Unassigned' }}</dd>

                        <dt class="text-muted-foreground">Created by</dt>
                        <dd>{{ task.created_by?.name ?? '—' }}</dd>
                    </dl>

                    <section class="space-y-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Description</h3>
                        <RichTextContent v-if="task.task_description" :content="task.task_description" />
                        <p v-else class="text-sm text-muted-foreground">No description.</p>
                    </section>
                </div>

                <SheetFooter class="flex-col-reverse gap-2 border-t border-sidebar-border/70 pt-4 sm:flex-row sm:justify-end sm:space-x-0">
                    <Button variant="outline" class="h-11 sm:h-9" @click="editTask">
                        <Pencil class="size-4" />
                        Edit Task
                    </Button>
                    <Button v-if="task.status !== 'Completed'" class="h-11 sm:h-9" :disabled="isCompleting" @click="markComplete">
                        <Check class="size-4" />
                        Mark Complete
                    </Button>
                </SheetFooter>
            </template>
        </SheetContent>
    </Sheet>
</template>

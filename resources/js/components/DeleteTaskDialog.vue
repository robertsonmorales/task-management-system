<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { Task } from '@/types';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    task?: Task | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    deleted: [id: number];
}>();

function confirmDelete() {
    if (!props.task) {
        return;
    }

    useForm({}).delete(route('tasks.destroy', { task: props.task.id }));

    emit('deleted', props.task.id);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete Task</DialogTitle>
                <DialogDescription class="text-dark"> Delete "<strong>{{ task?.task_name }}</strong>"? This action cannot be undone. </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary">Cancel</Button>
                </DialogClose>
                <Button type="button" variant="destructive" @click="confirmDelete">I understand</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

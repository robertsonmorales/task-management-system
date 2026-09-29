<script setup lang="ts">
import { cn } from '@/lib/utils';
import { CircleCheck, CircleX, X } from 'lucide-vue-next';
import { ToastClose, ToastDescription, ToastPortal, ToastProvider, ToastRoot, ToastViewport } from 'radix-vue';
import { ref } from 'vue';

interface Props {
    success: boolean;
    message: string;
}

defineProps<Props>();

const open = ref(true);
</script>

<template>
    <ToastProvider swipe-direction="right">
        <ToastRoot
            v-model:open="open"
            :duration="6000"
            :class="
                cn(
                    'pointer-events-auto flex items-start gap-3 rounded-lg border p-4 shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-80 data-[state=closed]:slide-out-to-right-full data-[state=open]:slide-in-from-top-full',
                    success
                        ? 'border-green-200 bg-green-50 text-green-900 dark:border-green-900 dark:bg-green-950 dark:text-green-100'
                        : 'border-destructive/30 bg-destructive/10 text-destructive',
                )
            "
        >
            <component :is="success ? CircleCheck : CircleX" class="mt-0.5 h-5 w-5 shrink-0" />

            <ToastDescription class="flex-1 text-sm font-medium">
                {{ message }}
            </ToastDescription>

            <ToastClose
                aria-label="Dismiss"
                class="shrink-0 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring"
            >
                <X class="h-4 w-4" />
            </ToastClose>
        </ToastRoot>

        <ToastPortal>
            <ToastViewport class="fixed right-0 top-0 z-[100] m-0 flex w-full max-w-sm list-none flex-col gap-2 p-6 outline-none" />
        </ToastPortal>
    </ToastProvider>
</template>

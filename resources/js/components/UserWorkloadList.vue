<script setup lang="ts">
import type { UserWorkload } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    users: UserWorkload[];
}>();

/** Bars share one scale: the busiest listed user fills the track. */
const maxOpen = computed(() => Math.max(1, ...props.users.map((user) => user.open)));

function barWidth(count: number): string {
    return `${(count / maxOpen.value) * 100}%`;
}

function tasksHref(user: UserWorkload): string {
    return route('tasks.index', { assignee: user.id });
}
</script>

<template>
    <div>
        <div class="flex items-center gap-4 px-4 pb-2 text-[11px] text-muted-foreground">
            <span class="flex items-center gap-1.5">
                <span class="size-2.5 rounded-[2px] bg-[#d03b3b]" aria-hidden="true" />
                Overdue
            </span>
            <span class="flex items-center gap-1.5">
                <span class="size-2.5 rounded-[2px] bg-[#2a78d6] dark:bg-[#3987e5]" aria-hidden="true" />
                Other open
            </span>
        </div>

        <!-- Tablet / desktop: table -->
        <table class="hidden w-full table-fixed sm:table">
            <colgroup>
                <col />
                <col class="w-20" />
                <col class="w-24" />
                <col class="w-20" />
            </colgroup>
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                    <th scope="col" class="px-4 py-2 font-semibold">User</th>
                    <th scope="col" class="px-2 py-2 text-right font-semibold">Open</th>
                    <th scope="col" class="px-2 py-2 text-right font-semibold">In Progress</th>
                    <th scope="col" class="px-4 py-2 text-right font-semibold">Overdue</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id" class="border-t border-sidebar-border/60 transition-colors hover:bg-muted/50">
                    <td class="px-4 py-2.5">
                        <Link :href="tasksHref(user)" class="block truncate text-sm font-medium hover:underline">{{ user.name }}</Link>
                        <div class="mt-1.5 flex h-1.5 gap-[2px]" aria-hidden="true">
                            <span v-if="user.overdue" class="h-full rounded-[2px] bg-[#d03b3b]" :style="{ width: barWidth(user.overdue) }" />
                            <span
                                v-if="user.open - user.overdue"
                                class="h-full rounded-[2px] bg-[#2a78d6] dark:bg-[#3987e5]"
                                :style="{ width: barWidth(user.open - user.overdue) }"
                            />
                        </div>
                    </td>
                    <td class="px-2 py-2.5 text-right text-sm tabular-nums">{{ user.open }}</td>
                    <td class="px-2 py-2.5 text-right text-sm tabular-nums text-muted-foreground">{{ user.inProgress }}</td>
                    <td
                        :class="[
                            'px-4 py-2.5 text-right text-sm tabular-nums',
                            user.overdue > 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'text-muted-foreground',
                        ]"
                    >
                        {{ user.overdue }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Mobile: list -->
        <ul class="sm:hidden">
            <li v-for="user in users" :key="user.id" class="border-t border-sidebar-border/60">
                <Link :href="tasksHref(user)" class="flex items-center gap-3 px-4 py-3 active:bg-muted/60">
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <p class="truncate text-sm font-medium">{{ user.name }}</p>
                        <div class="flex h-1.5 gap-[2px]" aria-hidden="true">
                            <span v-if="user.overdue" class="h-full rounded-[2px] bg-[#d03b3b]" :style="{ width: barWidth(user.overdue) }" />
                            <span
                                v-if="user.open - user.overdue"
                                class="h-full rounded-[2px] bg-[#2a78d6] dark:bg-[#3987e5]"
                                :style="{ width: barWidth(user.open - user.overdue) }"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ user.open }} open · {{ user.inProgress }} in progress ·
                            <span :class="user.overdue > 0 && 'font-semibold text-red-600 dark:text-red-400'">{{ user.overdue }} overdue</span>
                        </p>
                    </div>
                    <ChevronRight class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                </Link>
            </li>
        </ul>
    </div>
</template>

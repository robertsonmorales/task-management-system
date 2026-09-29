<script setup lang="ts">
import CreateTaskDialog from '@/components/CreateTaskDialog.vue';
import UserInfo from '@/components/UserInfo.vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { House, ListTodo, LogOut, Menu, Palette, Plus, Settings } from 'lucide-vue-next';
import { ref } from 'vue';

const page = usePage<SharedData>();

const isAdmin = page.props.auth.user.user_role_id === 1;

const navItems = [
    { title: 'Home', href: isAdmin ? '/admin-dashboard' : '/dashboard', icon: House },
    { title: 'Tasks', href: '/tasks', icon: ListTodo },
];

function isCurrent(href: string): boolean {
    return page.url === href || page.url.startsWith(`${href}?`) || page.url.startsWith(`${href}/`);
}

const createOpen = ref(false);
const moreOpen = ref(false);

const itemClass = 'flex min-h-14 flex-col items-center justify-center gap-1 text-[11px] font-medium';
</script>

<template>
    <nav
        aria-label="Primary"
        class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-sidebar-border/70 bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
    >
        <Link
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            :aria-current="isCurrent(item.href) ? 'page' : undefined"
            :class="cn(itemClass, isCurrent(item.href) ? 'text-foreground' : 'text-muted-foreground')"
        >
            <component :is="item.icon" class="size-5" />
            {{ item.title }}
        </Link>

        <button type="button" :class="cn(itemClass, 'text-muted-foreground')" @click="createOpen = true">
            <span class="flex size-9 items-center justify-center rounded-full bg-primary text-primary-foreground shadow">
                <Plus class="size-5" />
            </span>
            <span class="sr-only">Create Task</span>
        </button>

        <button type="button" :class="cn(itemClass, 'text-muted-foreground')" @click="moreOpen = true">
            <Menu class="size-5" />
            More
        </button>
    </nav>

    <CreateTaskDialog v-model:open="createOpen" />

    <Sheet v-model:open="moreOpen">
        <SheetContent side="bottom" class="rounded-t-2xl pb-[calc(1.5rem+env(safe-area-inset-bottom))]">
            <SheetHeader class="text-left">
                <SheetTitle class="sr-only">More</SheetTitle>
                <SheetDescription class="sr-only">Account and settings</SheetDescription>
                <div class="flex items-center gap-2 pr-6">
                    <UserInfo :user="page.props.auth.user" :show-email="true" />
                </div>
            </SheetHeader>

            <ul class="mt-4 divide-y divide-sidebar-border/70 rounded-lg border border-sidebar-border/70">
                <li>
                    <Link :href="route('profile.edit')" class="flex min-h-12 items-center gap-3 px-4 text-sm" @click="moreOpen = false">
                        <Settings class="size-4 text-muted-foreground" />
                        Settings
                    </Link>
                </li>
                <li>
                    <Link :href="route('appearance')" class="flex min-h-12 items-center gap-3 px-4 text-sm" @click="moreOpen = false">
                        <Palette class="size-4 text-muted-foreground" />
                        Appearance
                    </Link>
                </li>
                <li>
                    <Link :href="route('logout')" method="post" as="button" class="flex min-h-12 w-full items-center gap-3 px-4 text-sm text-red-600">
                        <LogOut class="size-4" />
                        Log out
                    </Link>
                </li>
            </ul>
        </SheetContent>
    </Sheet>
</template>

<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';

interface NavItem {
    title: string;
    href: string;
    icon?: Component;
    isActive: boolean;
}

defineProps<{
    items: NavItem[];
}>();

const page = usePage<SharedData>();

/**
 * Treat filtered or nested URLs (e.g. /tasks?due=today) as part of their nav item.
 */
function isCurrent(href: string): boolean {
    return page.url === href || page.url.startsWith(`${href}?`) || page.url.startsWith(`${href}/`);
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Workspace</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton as-child v-if="item.isActive" :is-active="isCurrent(item.href)">
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

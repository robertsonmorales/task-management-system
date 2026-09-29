<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { computed } from "vue";
import { Link, usePage } from '@inertiajs/vue3';
import { 
    // BookOpen, Folder, 
    LayoutGrid, ListTodo, ListChecks
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const auth = page.props.auth as { user: { user_role_id: number } };
// const isRegUser = auth.user.user_role_id === 2;
// const isAdmin = auth.user.user_role_id === 1;

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        isActive: true
    },
    {
        title: 'My Tasks',
        href: '/tasks',
        icon: ListTodo,
        isActive: true // isRegUser
    },
    {
        title: 'All Tasks',
        href: '/all-tasks',
        icon: ListChecks,
        isActive: true // isAdmin
    },
];

// const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits',
    //     icon: BookOpen,
    // },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

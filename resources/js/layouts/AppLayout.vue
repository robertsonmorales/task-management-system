<script setup lang="ts">
import Toast from '@/components/Toast.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />

        <Toast v-if="flash.message" :key="flash.message" :success="!!flash.success" :message="flash.message" />
    </AppLayout>
</template>

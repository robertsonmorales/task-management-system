<script setup lang="ts">
import { statusBadgeClass } from '@/lib/status';
import type { Status } from '@/types';

defineProps<{
    status: Status;
}>();
</script>

<template>
    <div :class="`${statusBadgeClass(status)}`">{{ status }}</div>
</template>

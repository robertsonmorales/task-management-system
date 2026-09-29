import type { Priority } from '@/types';

export const PRIORITY_OPTIONS: Priority[] = ['Low', 'Normal', 'High', 'Urgent'];

export function priorityColorClass(priority: string): string | undefined {
    switch (priority) {
        case 'Low':
            return 'text-gray-500';
        case 'Normal':
            return 'text-blue-500';
        case 'High':
            return 'text-yellow-500';
        case 'Urgent':
            return 'text-red-500';
        default:
            return undefined;
    }
}

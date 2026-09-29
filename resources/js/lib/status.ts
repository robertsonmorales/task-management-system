import type { Status } from '@/types';

export const STATUS_OPTIONS: Status[] = ['Pending', 'In Progress', 'Completed'];

export function statusBadgeClass(status: string): string {
    let state;

    switch (status) {
        case 'Pending':
            state = 'bg-blue-500 border border-blue-400';
            break;
        case 'In Progress':
            state = 'bg-yellow-600 border border-yellow-500';
            break;
        case 'Completed':
            state = 'bg-green-700 border border-green-600';
            break;
        default:
            break;
    }

    return `${state} flex items-center justify-center rounded-full px-2 w-fit py-0.5 text-white text-xs font-medium shadow`;
}

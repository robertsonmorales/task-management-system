import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: {
        success: boolean | null;
        message: string | null;
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    user_role_id: number;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface AssigneeOption {
    id: number;
    name: string;
    email: string;
}

export type Priority = 'Low' | 'Normal' | 'High' | 'Urgent';

export type Status = 'Pending' | 'In Progress' | 'Completed';

export interface Task {
    id: number;
    task_name: string;
    assign_to: {
        id: number;
        name: string;
    } | null;
    task_description: string;
    due_date: string;
    status: Status;
    priority: Priority;
}

export interface TaskFilters {
    search: string | null;
    priority: Priority | null;
    status: Status | null;
    due: string | null;
    sort: TaskSortColumn | null;
    direction: SortDirection | null;
}

export type TaskSortColumn = 'task_name' | 'assignee' | 'due_date' | 'priority' | 'status';

export type SortDirection = 'asc' | 'desc';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export type BreadcrumbItemType = BreadcrumbItem;

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
    created_by?: {
        id: number;
        name: string;
    } | null;
    task_description: string;
    due_date: string;
    status: Status;
    priority: Priority;
}

export type AttentionReason = 'overdue' | 'today' | 'high_priority';

export interface DashboardTask extends Task {
    attention?: AttentionReason;
}

/**
 * Filter tasks by one user ("id" is the user id) or by tasks nobody is assigned to ("id" is "unassigned").
 */
export interface AssigneeFilter {
    id: string;
    name: string;
}

export interface AdminSummary {
    total: number;
    inProgress: number;
    overdue: number;
    completed: number;
}

export interface AdminAttention {
    overdue: number;
    unassigned: number;
    highPriorityDueToday: number;
}

export type AnalyticsPeriod = 'today' | 'week' | 'month';

export interface StatusBreakdown {
    overdue: number;
    pending: number;
    inProgress: number;
    completed: number;
}

export interface UserWorkload {
    id: number;
    name: string;
    open: number;
    inProgress: number;
    overdue: number;
}

export interface DashboardSummary {
    overdue: number;
    dueToday: number;
    upcoming: number;
    open: number;
}

export interface TaskFilters {
    search: string | null;
    priority: Priority | null;
    status: Status | null;
    due: string | null;
    assignee: AssigneeFilter | null;
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

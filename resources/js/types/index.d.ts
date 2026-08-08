export interface Department {
    id: number;
    name: string;
    code: string;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    employee_number: string | null;
    profile_photo_url: string | null;
    department: Department | null;
    role: string | null;
    permissions: string[];
}

export interface Flash {
    success?: string | null;
    error?: string | null;
}

export interface AppNotification {
    id: string;
    read_at: string | null;
    created_at: string;
    title: string;
    message: string;
    url: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: AuthUser;
    };
    flash: Flash;
    notificationCenter: {
        unread_count: number;
        recent: AppNotification[];
    } | null;
};

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

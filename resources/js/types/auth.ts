export type RoleSummary = {
    name: string;
    display_name: string;
};

export type User = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    is_active?: boolean;
    avatar?: string;
    primary_role?: string | null;
    email_verified_at: string | null;
    last_login_at?: string | null;
    created_at: string | null;
    updated_at: string | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions: string[];
    roles: RoleSummary[];
};

export type BusinessProfile = {
    company_name: string;
    phone: string;
    whatsapp_number: string;
    email: string;
    address: string;
    currency_code: string;
    currency_symbol: string;
    timezone: string;
};

export type NotificationItem = {
    id: string;
    title: string;
    message: string;
    category: string;
    action_url: string | null;
    severity: string;
    read_at: string | null;
    created_at: string | null;
};

export type NotificationsState = {
    unread_count: number;
    recent: NotificationItem[];
};

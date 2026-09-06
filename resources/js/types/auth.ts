export type UserRole = 'user' | 'admin' | 'school';

export type UserDirection = {
    id: number;
    code: string;
    name: string;
};

export type User = {
    id: number;
    name: string;
    iin: string | null;
    email: string;
    role: UserRole;
    direction: UserDirection | null;
    must_select_direction: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type DirectionOption = {
    id: number;
    code: string;
    name: string;
    subjects: Array<{ id: number; name: string }>;
};

export type Auth = {
    user: User | null;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

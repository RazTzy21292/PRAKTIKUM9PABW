import { usePage } from '@inertiajs/react';

export type UserRole = 'admin' | 'user';

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
};

type SharedProps = {
    auth: { user: AuthUser };
    flash: { success?: string | null; error?: string | null };
};

export function useSharedProps() {
    // Cast: tipe shared props starter kit tidak mengenal role dan flash
    const { auth, flash } = usePage().props as unknown as SharedProps;

    return {
        user: auth.user,
        isAdmin: auth.user?.role === 'admin',
        flash: flash ?? {},
    };
}

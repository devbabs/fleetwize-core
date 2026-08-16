import type { ReactNode } from 'react';

import { AdminHeader } from '@/components/admin/admin-header';
import { AdminSidebar } from '@/components/admin/admin-sidebar';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';

type Props = {
    children: ReactNode;
    title?: string;
};

export default function AdminLayout({ children, title }: Props) {
    return (
        <div className="portal-shell">
            <AppShell variant="sidebar">
                <AdminSidebar />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <AdminHeader title={title} />
                    <div className="flex-1 space-y-6 p-6">{children}</div>
                </AppContent>
            </AppShell>
        </div>
    );
}

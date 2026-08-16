import type { ReactNode } from 'react';

import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { WorkshopHeader } from '@/components/workshop/workshop-header';
import { WorkshopSidebar } from '@/components/workshop/workshop-sidebar';

type Props = {
    children: ReactNode;
    title?: string;
};

export default function WorkshopLayout({ children, title }: Props) {
    return (
        <div className="portal-shell">
            <AppShell variant="sidebar">
                <WorkshopSidebar />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <WorkshopHeader title={title} />
                    <div className="flex-1 space-y-6 p-6">{children}</div>
                </AppContent>
            </AppShell>
        </div>
    );
}

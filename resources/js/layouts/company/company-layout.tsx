import type { ReactNode } from 'react';

import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { CompanyHeader } from '@/components/company/company-header';
import { CompanySidebar } from '@/components/company/company-sidebar';

type Props = {
    children: ReactNode;
    title?: string;
};

export default function CompanyLayout({ children, title }: Props) {
    return (
        <div className="portal-shell">
            <AppShell variant="sidebar">
                <CompanySidebar />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <CompanyHeader title={title} />
                    <div className="flex-1 space-y-6 p-6">{children}</div>
                </AppContent>
            </AppShell>
        </div>
    );
}

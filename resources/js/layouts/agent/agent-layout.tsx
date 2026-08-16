import type { ReactNode } from 'react';

import { AgentHeader } from '@/components/agent/agent-header';
import { AgentSidebar } from '@/components/agent/agent-sidebar';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';

type Props = {
    children: ReactNode;
    title?: string;
};

export default function AgentLayout({ children, title }: Props) {
    return (
        <div className="portal-shell">
            <AppShell variant="sidebar">
                <AgentSidebar />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <AgentHeader title={title} />
                    <div className="flex-1 space-y-6 p-6">{children}</div>
                </AppContent>
            </AppShell>
        </div>
    );
}

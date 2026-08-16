import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import AdminLayout from '@/layouts/admin/admin-layout';
import AgentLayout from '@/layouts/agent/agent-layout';
import AppLayout from '@/layouts/app-layout';
import CompanyLayout from '@/layouts/company/company-layout';
import WorkshopLayout from '@/layouts/workshop/workshop-layout';

/**
 * Picks the sidebar shell to match wherever the user actually is (company,
 * workshop, agent, or admin portal) for pages shared across all of them —
 * currently just /settings/*. Those routes aren't gated by any tenant
 * middleware, so they can't rely on route-specific attributes; `portal` is
 * shared on every request instead (see App\Support\PortalContext).
 */
export default function DynamicPortalLayout({ children }: { children: ReactNode }) {
    const { portal } = usePage().props;

    switch (portal) {
        case 'workshop':
            return <WorkshopLayout title="Settings">{children}</WorkshopLayout>;
        case 'company':
            return <CompanyLayout title="Settings">{children}</CompanyLayout>;
        case 'agent':
            return <AgentLayout title="Settings">{children}</AgentLayout>;
        case 'admin':
            return <AdminLayout title="Settings">{children}</AdminLayout>;
        default:
            return <AppLayout>{children}</AppLayout>;
    }
}

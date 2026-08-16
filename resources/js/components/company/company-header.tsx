import { usePage } from '@inertiajs/react';

import { SidebarTrigger } from '@/components/ui/sidebar';

export function CompanyHeader({ title }: { title?: string }) {
    const { company } = usePage().props;

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <SidebarTrigger className="-ml-1" />
            <div className="leading-tight">
                {title ? <h1 className="text-sm font-semibold text-foreground">{title}</h1> : null}
                {company ? <p className="text-xs text-muted-foreground">{company.name}</p> : null}
            </div>
        </header>
    );
}

import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, UserPlus, Users } from 'lucide-react';

import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const navItems: NavItem[] = [
    { title: 'Dashboard', href: '/agent/dashboard', icon: LayoutDashboard },
    { title: 'Customers', href: '/agent/customers', icon: Users },
    { title: 'Onboard Customer', href: '/agent/customers/create', icon: UserPlus },
];

export function AgentSidebar() {
    const { url } = usePage();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/agent/dashboard" prefetch>
                                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand-green/15 text-brand-green">
                                    <UserPlus className="size-4" />
                                </div>
                                <div className="ml-1 grid flex-1 text-left leading-tight">
                                    <span className="truncate text-sm font-semibold">Fleetwize</span>
                                    <span className="truncate text-xs text-sidebar-foreground/60">Agent</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Onboarding</SidebarGroupLabel>
                    <SidebarMenu>
                        {navItems.map((item) => {
                            const isActive = url === item.href || url.startsWith(`${String(item.href)}/`);

                            return (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive}
                                        tooltip={{ children: item.title }}
                                        className="data-[active=true]:border-l-2 data-[active=true]:border-sidebar-primary data-[active=true]:bg-sidebar-primary/15 data-[active=true]:text-sidebar-primary"
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

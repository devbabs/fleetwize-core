import { Link, usePage } from '@inertiajs/react';
import { FilePlus2, LayoutDashboard, Wrench as WrenchIcon, ClipboardList, Truck } from 'lucide-react';

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
    { title: 'Dashboard', href: '/workshop/dashboard', icon: LayoutDashboard },
    { title: 'Vehicles', href: '/workshop/vehicles', icon: Truck },
    { title: 'New Report', href: '/workshop/reports/create', icon: FilePlus2 },
    { title: 'Reports', href: '/workshop/reports', icon: ClipboardList },
];

export function WorkshopSidebar() {
    const { url } = usePage();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/workshop/dashboard" prefetch>
                                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand-green/15 text-brand-green">
                                    <WrenchIcon className="size-4" />
                                </div>
                                <div className="ml-1 grid flex-1 text-left leading-tight">
                                    <span className="truncate text-sm font-semibold">Fleetwize</span>
                                    <span className="truncate text-xs text-sidebar-foreground/60">Workshop</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Workshop</SidebarGroupLabel>
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

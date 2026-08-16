import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types/pagination';

export function Pagination({ links, from, to, total }: { links: PaginationLink[]; from: number | null; to: number | null; total: number }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border px-6 py-3">
            <p className="text-xs text-muted-foreground">
                {from && to ? `Showing ${from}–${to} of ${total}` : `${total} total`}
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {links.map((link, index) => (
                    <Link
                        key={index}
                        href={link.url ?? '#'}
                        preserveScroll
                        preserveState
                        disabled={!link.url}
                        className={cn(
                            'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                            link.active
                                ? 'bg-brand-navy text-white dark:bg-brand-green dark:text-brand-navy'
                                : link.url
                                  ? 'text-muted-foreground hover:bg-muted'
                                  : 'cursor-not-allowed text-muted-foreground/40',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </div>
        </div>
    );
}

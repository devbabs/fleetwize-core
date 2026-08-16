import type { Auth } from '@/types/auth';
import type { Company } from '@/types/company';
import type { Workshop } from '@/types/workshop';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            portal: 'company' | 'workshop' | 'agent' | 'admin' | null;
            company: Company | null;
            workshop: Workshop | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

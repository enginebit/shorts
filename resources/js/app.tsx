import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route } from 'ziggy-js';
import { Ziggy } from './ziggy';
import { Toaster } from 'sonner';
import { getCurrentCSRFToken, synchronizeCSRFToken } from '@/lib/csrf-utils';

const appName = import.meta.env.VITE_APP_NAME || 'branchy.io';
// Dev-only canonical host guard: prevent accidental hyphenated host (brachy-io)
if (typeof window !== 'undefined') {
  const h = window.location.host;
  if (h.includes('brachy-io')) {
    const url = window.location.href.replace('brachy-io', 'brachy.io');
    window.location.replace(url);
  }
}


// Make route function globally available
declare global {
    var route: typeof import('ziggy-js').route;
}
window.route = (name, params, absolute, config = Ziggy) => route(name, params, absolute, config);

// Configure Inertia.js to include CSRF token in all requests
router.on('before', async (event) => {
    // Ensure XSRF cookie exists and synchronize tokens before first POST
    const { ensureFreshCsrf } = await import('./lib/csrf-utils');
    const tokenInfo = await ensureFreshCsrf();

    console.debug('Inertia router before event:', {
        method: event.detail.visit.method,
        url: event.detail.visit.url,
        tokenInfo: {
            hasToken: tokenInfo.isValid,
            source: tokenInfo.source,
            tokenPrefix: tokenInfo.token?.substring(0, 10) + '...',
        },
        timestamp: tokenInfo.timestamp,
    });

    if (tokenInfo.isValid && tokenInfo.token) {
        // Resolve XSRF cookie token (used by Laravel when header X-XSRF-TOKEN matches this cookie)
        const xsrfMatch = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
        const xsrfToken = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : undefined;

        // Prefer cookie header for Sanctum; use session meta for X-CSRF-TOKEN if available
        const sessionMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || undefined;
        const headers: Record<string, string> = { ...(event.detail.visit.headers as any) };
        if (xsrfToken) headers['X-XSRF-TOKEN'] = xsrfToken;
        if (sessionMeta) headers['X-CSRF-TOKEN'] = sessionMeta;
        event.detail.visit.headers = headers as any;

        // Also add CSRF token to request data for mutating requests
        if (['post','POST','put','PATCH','patch','delete','DELETE'].includes(event.detail.visit.method as string)) {
            const data: Record<string, any> = { ...(event.detail.visit.data as any) };
            if (sessionMeta) data._token = sessionMeta;
            event.detail.visit.data = data as any;
        }

        console.debug('CSRF token added to visit headers and data', {
            tokenSource: tokenInfo.source,


            tokenPrefix: tokenInfo.token.substring(0, 10) + '...',
        });
    } else {
        console.error('No valid CSRF token found:', {
            tokenInfo,
            allMetaTags: Array.from(document.querySelectorAll('meta')).map(m => ({
                name: m.getAttribute('name'),
                content: m.getAttribute('content')?.substring(0, 20) + '...'
            })),
        });
    }
});

// Refresh CSRF token synchronization after page loads
router.on('success', () => {
    // Small delay to ensure page props are updated
    setTimeout(() => {
        const tokenInfo = synchronizeCSRFToken();
        console.debug('CSRF token synchronized after navigation:', {
            source: tokenInfo.source,
            isValid: tokenInfo.isValid,
            tokenPrefix: tokenInfo.token?.substring(0, 10) + '...',
        });
    }, 100);
});

// Theme boot using cookie + localStorage for cross-subdomain persistence
import { initTheme } from '@/lib/theme';

// Normalize any absolute visit URL to current origin + relative path to avoid domain drift
router.on('before', (event) => {
  try {
    const u = new URL((event.detail.visit.url as any), window.location.href);
    u.protocol = window.location.protocol;
    u.host = window.location.host;
    (event.detail.visit as any).url = u;
  } catch {}
});

if (typeof document !== 'undefined') initTheme();

if (typeof document !== 'undefined') initTheme();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        if (typeof window !== 'undefined') {
            (window as any).APP_HOST = window.location.host;
        }
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <Toaster />
            </>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// Debug: Check CSRF token on page load
document.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.debug('Page loaded - CSRF token check:', {
        hasToken: !!token,
        tokenPrefix: token?.substring(0, 10) + '...',
        url: window.location.href,
        timestamp: new Date().toISOString(),
    });
});

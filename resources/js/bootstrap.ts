import axios from 'axios';


// Configure Axios for Laravel integration (define first)
// @ts-expect-error - attach to window
window.axios = axios;

// Normalize axios base URL and credentials to current origin
if (typeof window !== 'undefined') {
  const origin = `${window.location.protocol}//${window.location.host}`;
  axios.defaults.baseURL = origin;
  axios.defaults.withCredentials = true;
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Ensure the XSRF-TOKEN cookie exists for Laravel's CSRF protections
(function ensureCsrfCookie() {
  try {
    if (!window.axios) return; // guard against undefined during early init
    const hasXsrf = document.cookie.includes('XSRF-TOKEN=');
    if (!hasXsrf) {
      fetch('/sanctum/csrf-cookie', { credentials: 'include' })
        .then(() => {
          const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

// Harden against host drift from extensions/proxies by normalizing request URLs
(function hardenRequestURLs() {
  if (typeof window === 'undefined') return;
  const normalize = (inputUrl: string) => {
    try {
      const u = new URL(inputUrl, window.location.href);
      // Normalize hyphenated/underscored host to dotted
      if (u.host.includes('brachy-io')) {
        u.host = u.host.replace('brachy-io', 'brachy.io');
      }
      if (u.host.includes('brachy_io') || u.host.includes('_')) {
        u.host = u.host.replace(/_/g, '.');
      }
      // Force same origin as current page
      u.protocol = window.location.protocol;
      u.host = window.location.host;
      return u.toString();
    } catch {
      return inputUrl;
    }
  };

  // Patch fetch
  const origFetch = window.fetch.bind(window);
  window.fetch = (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
    try {
      if (typeof input === 'string') {
        input = normalize(input);
      } else if (input instanceof URL) {
        input = new URL(normalize(input.toString()));
      } else if (input instanceof Request) {
        input = new Request(normalize(input.url), input);
      }
    } catch {}
    return origFetch(input as any, init);
  };

  // Patch XMLHttpRequest
  const origOpen = XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open = function(method: string, url: string, async?: boolean, user?: string | null, password?: string | null) {
    try { url = normalize(url); } catch {}
    // Always send cookies for CSRF/session
    try { (this as any).withCredentials = true; } catch {}
    return origOpen.call(this, method, url, async ?? true, user ?? null as any, password ?? null as any);
  };
})();

          const xsrf = match ? decodeURIComponent(match[1]) : undefined;
          if (xsrf) {
            // Let Axios send cookie-based CSRF header too (fallback)
            window.axios.defaults.headers.common['X-XSRF-TOKEN'] = xsrf;
          }
        })
        .catch(() => { /* ignore */ });
    }
  } catch { /* ignore */ }
})();

// CSRF token handling via meta tag (session token)
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
    // Also set it for axios defaults (in case Inertia uses axios)
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
    console.log('CSRF token configured:', token.getAttribute('content')?.substring(0, 10) + '...');
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

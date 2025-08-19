import axios from 'axios';


// Configure Axios for Laravel integration (define first)
// @ts-expect-error - attach to window
window.axios = axios;

// Normalize axios base URL and credentials to current origin
if (typeof window !== 'undefined') {
  const origin = `${window.location.protocol}//${window.location.host}`;
  axios.defaults.baseURL = origin;
  axios.defaults.withCredentials = true;
  // Ensure axios uses Laravel Sanctum cookie/header names
  axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
  axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Harden against host drift from extensions/proxies by normalizing request URLs
(function hardenRequestURLs() {
  if (typeof window === 'undefined') return;
  const normalize = (inputUrl: string) => {
    try {
      const u = new URL(inputUrl, window.location.href);

      // Canonicalize current host and enforce it on any absolute target
      const canonHost = window.location.host.replace('brachy-io', 'brachy.io').replace(/_/g, '.');

      // Enforce canonical host and protocol
      u.protocol = window.location.protocol;
      u.host = canonHost;
      return u.toString();
    } catch {
      return inputUrl;
    }
  };

  // Patch fetch with CSRF-aware, origin-normalized wrapper
  const origFetch = window.fetch.bind(window);
  window.fetch = async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
    try {
      if (typeof input === 'string') {
        input = normalize(input);
      } else if (input instanceof URL) {
        input = new URL(normalize(input.toString()));
      } else if (input instanceof Request) {
        input = new Request(normalize(input.url), input);
      }
    } catch {}

    // Ensure CSRF cookie before any mutating request and attach headers if missing
    try {
      const method = (init?.method || (input as any)?.method || 'GET').toUpperCase();
      if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        const { ensureFreshCsrf, getCurrentCSRFToken } = await import('@/lib/csrf-utils');
        await ensureFreshCsrf();

        // Build headers
        const headers = new Headers(init?.headers as any);
        // XSRF cookie → X-XSRF-TOKEN header (only if not set)
        const xsrfMatch = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
        const xsrfToken = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : undefined;
        if (xsrfToken && !headers.has('X-XSRF-TOKEN')) headers.set('X-XSRF-TOKEN', xsrfToken);

        // Session CSRF token → X-CSRF-TOKEN header (prefer Inertia props)
        let sessionToken: string | undefined;
        try { // @ts-ignore
          sessionToken = window.Inertia?.page?.props?.csrf_token || undefined;
        } catch {}
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || undefined;
        sessionToken = sessionToken || metaToken || getCurrentCSRFToken().token || undefined;
        if (sessionToken && !headers.has('X-CSRF-TOKEN')) headers.set('X-CSRF-TOKEN', sessionToken);

        init = { ...(init || {}), headers, credentials: init?.credentials || 'include' };
      }
    } catch {}

    return origFetch(input as any, init);
  };

  // Patch XMLHttpRequest to normalize URLs and attach CSRF headers on mutating requests
  const origOpen = XMLHttpRequest.prototype.open;
  const origSend = XMLHttpRequest.prototype.send;

  XMLHttpRequest.prototype.open = function(method: string, url: string, async?: boolean, user?: string | null, password?: string | null) {
    try { url = normalize(url); } catch {}
    try { (this as any).withCredentials = true; } catch {}
    (this as any)._method = method ? method.toUpperCase() : 'GET';
    return origOpen.call(this, method, url, async ?? true, user ?? null as any, password ?? null as any);
  };

  XMLHttpRequest.prototype.send = function(body?: Document | BodyInit | null) {
    try {
      const method = (this as any)._method || 'GET';
      if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        // Only use XSRF cookie token - don't mix with session tokens to avoid conflicts
        const xsrfMatch = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
        const xsrfToken = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : undefined;

        if (xsrfToken) {
          this.setRequestHeader('X-XSRF-TOKEN', xsrfToken);
        }
      }
    } catch {}
    return origSend.call(this, body as any);
  };
})();

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

      // Canonicalize current host and enforce it on any absolute target
      const canonHost = window.location.host.replace('brachy-io', 'brachy.io').replace(/_/g, '.');

      // Enforce canonical host and protocol
      u.protocol = window.location.protocol;
      u.host = canonHost;
      return u.toString();
    } catch {
      return inputUrl;
    }
  };

  // Patch fetch with CSRF-aware, origin-normalized wrapper
  const origFetch = window.fetch.bind(window);
  window.fetch = async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
    try {
      if (typeof input === 'string') {
        input = normalize(input);
      } else if (input instanceof URL) {
        input = new URL(normalize(input.toString()));
      } else if (input instanceof Request) {
        input = new Request(normalize(input.url), input);
      }
    } catch {}

    // Ensure CSRF cookie before any mutating request and attach headers if missing
    try {
      const method = (init?.method || (input as any)?.method || 'GET').toUpperCase();
      if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        const { ensureFreshCsrf, getCurrentCSRFToken } = await import('@/lib/csrf-utils');
        await ensureFreshCsrf();

        // Build headers - only use XSRF cookie to avoid token conflicts
        const headers = new Headers(init?.headers as any);
        const xsrfMatch = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
        const xsrfToken = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : undefined;
        if (xsrfToken && !headers.has('X-XSRF-TOKEN')) {
          headers.set('X-XSRF-TOKEN', xsrfToken);
        }

        init = { ...(init || {}), headers, credentials: init?.credentials || 'include' };
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

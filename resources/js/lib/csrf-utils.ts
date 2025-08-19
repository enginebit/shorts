/**
 * CSRF Token Utilities
 *
 * Handles CSRF token synchronization and race condition prevention
 * for Laravel + Inertia.js applications
 */

export interface CSRFTokenInfo {
  token: string | null;
  source: 'meta' | 'props' | 'axios' | 'cookie' | 'none';
  isValid: boolean;
  timestamp: string;
}

function readXsrfCookie(): string | null {
  try {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
  } catch (e) {
    return null;
  }
}

/**
 * Get the most current CSRF token from available sources
 */
export function getCurrentCSRFToken(): CSRFTokenInfo {
  const timestamp = new Date().toISOString();

  // Source 1: Meta tag (may be stale after session regeneration)
  const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  // Source 2: Inertia page props (most current) - robust DOM fallback
  let propsToken: string | null = null;
  try {
    // Try reading via Inertia globals
    // @ts-ignore
    const pagePropsGlobal = (window?.Inertia?.page?.props) || (window as any)?.__INERTIA__?.page?.props;
    if (pagePropsGlobal && pagePropsGlobal.csrf_token) {
      propsToken = pagePropsGlobal.csrf_token as string;
    } else {
      // Fallback: parse any element with data-page attribute (adapter-specific)
      const dataPageEl = document.querySelector('[data-page]');
      const raw = dataPageEl?.getAttribute('data-page');
      if (raw) {
        const parsed = JSON.parse(raw);
        const p = parsed?.props || {};
        if (p.csrf_token) {
          propsToken = p.csrf_token as string;
        }
      }
    }
  } catch (e) {
    // ignore
  }

  // Source 3: Cookie (XSRF-TOKEN) - Laravel sets this cookie and VerifyCsrfToken will accept it if sent as header X-XSRF-TOKEN
  const cookieToken: string | null = readXsrfCookie();

  // Source 4: Axios defaults (fallback)
  let axiosToken: string | null = null;
  try {
    // @ts-ignore - Access axios defaults
    axiosToken = window.axios?.defaults?.headers?.common['X-CSRF-TOKEN'] || null;
  } catch (e) {
    // Axios not available
  }

  // Determine the best token to use
  // Prefer cookie (XSRF) first to avoid races with Sanctum in dev
  let bestToken: string | null = null;
  let source: CSRFTokenInfo['source'] = 'none';

  if (cookieToken) {
    bestToken = cookieToken;
    source = 'cookie';
  } else if (propsToken) {
    bestToken = propsToken;
    source = 'props';
  } else if (metaToken) {
    bestToken = metaToken;
    source = 'meta';
  } else if (axiosToken) {
    bestToken = axiosToken;
    source = 'axios';
  }

  return {
    token: bestToken,
    source,
    isValid: !!bestToken && bestToken.length > 10,
    timestamp,
  };
}

/**
 * Update the meta tag with a fresh CSRF token
 */
export function updateCSRFMetaTag(token: string): void {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  if (metaTag) {
    metaTag.setAttribute('content', token);
    console.log('CSRF meta tag updated with fresh token:', token.substring(0, 10) + '...');
  }
}

/**
 * Synchronize CSRF token across all sources
 */
export function synchronizeCSRFToken(): CSRFTokenInfo {
  const tokenInfo = getCurrentCSRFToken();

  if (tokenInfo.isValid && tokenInfo.token) {
    // If we have a cookie token, keep meta in sync with it to avoid mismatch
    if (tokenInfo.source === 'cookie') {
      updateCSRFMetaTag(tokenInfo.token);
    }

    // Update axios defaults if available
    try {
      // @ts-ignore
      if (window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenInfo.token;
      }
    } catch (e) {
      // Axios not available
    }
  }

  return tokenInfo;
}

/**
 * Ensure XSRF cookie exists and meta/header are synchronized before a POST
 */
export async function ensureFreshCsrf(): Promise<CSRFTokenInfo> {
  let info = getCurrentCSRFToken();
  const hasCookie = !!readXsrfCookie();

  if (!hasCookie) {
    try {
      await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
    } catch {}
  }

  // Re-evaluate and sync
  info = synchronizeCSRFToken();
  return info;
}

/**
 * Debug CSRF token state
 */
export function debugCSRFTokenState(): void {
  const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const cookieToken = readXsrfCookie();
  let propsToken: string | null = null;
  let sessionId: string | null = null;

  try {
    // @ts-ignore
    const pageProps = window.Inertia?.page?.props || {};
    propsToken = pageProps.csrf_token || null;
    sessionId = pageProps.session_id || null;
  } catch (e) {
    // Not available
  }

  console.log('CSRF Token Debug State:', {
    metaToken: metaToken?.substring(0, 10) + '...',
    cookieToken: cookieToken?.substring(0, 10) + '...',
    propsToken: propsToken?.substring(0, 10) + '...',
    sessionId: sessionId?.substring(0, 10) + '...',
    tokensMatch: !!metaToken && !!cookieToken ? metaToken === cookieToken : metaToken === propsToken,
    timestamp: new Date().toISOString(),
  });
}

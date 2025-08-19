export type Theme = 'light' | 'dark';

const THEME_KEY = 'theme';
const COOKIE_NAME = 'theme';

function getCookie(name: string): string | null {
  const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
  return m ? decodeURIComponent(m[1]) : null;
}

function setCookie(name: string, value: string, days = 365, domain?: string) {
  const expires = new Date(Date.now() + days * 864e5).toUTCString();
  const parts = [
    `${name}=${encodeURIComponent(value)}`,
    `expires=${expires}`,
    'path=/',
    'SameSite=Lax',
  ];
  if (domain) parts.push(`domain=${domain}`);
  // Do not set Secure for http dev
  document.cookie = parts.join('; ');
}

export function readPreferredTheme(): Theme {
  // 1) Cookie (for cross-subdomain persistence)
  const cookieTheme = getCookie(COOKIE_NAME);
  if (cookieTheme === 'light' || cookieTheme === 'dark') return cookieTheme;

  // 2) localStorage (per-origin)
  const stored = localStorage.getItem(THEME_KEY);
  if (stored === 'light' || stored === 'dark') return stored;

  // 3) System preference
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  return prefersDark ? 'dark' : 'light';
}

export function applyTheme(theme: Theme) {
  document.documentElement.classList.remove('light', 'dark');
  document.documentElement.classList.add(theme);
  document.body?.setAttribute('data-theme', theme);
}

export function persistTheme(theme: Theme) {
  try { localStorage.setItem(THEME_KEY, theme); } catch {}
  // Use apex from env if available, else derive from current host by removing first label
  let domain: string | undefined;
  try {
    const host = window.location.hostname; // e.g., app.app.brachy.io
    const parts = host.split('.');
    if (parts.length >= 3) {
      domain = '.' + parts.slice(parts.length - 3).join('.'); // e.g., .app.brachy.io
    } else if (parts.length >= 2) {
      domain = '.' + parts.slice(parts.length - 2).join('.');
    }
  } catch {}
  setCookie(COOKIE_NAME, theme, 365, domain);
}

export function setTheme(theme: Theme) {
  persistTheme(theme);
  applyTheme(theme);
}

export function initTheme() {
  const t = readPreferredTheme();
  applyTheme(t);
}


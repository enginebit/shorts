import React from 'react';
import { setTheme, readPreferredTheme, Theme } from '@/lib/theme';

function SunIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" {...props}>
      <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" d="M10 3.5v-2M10 18.5v-2M3.5 10h-2M18.5 10h-2M5.45 5.45l-1.42-1.42M15.97 15.97l-1.42-1.42M5.45 14.55l-1.42 1.42M15.97 4.03l-1.42 1.42"/>
      <circle cx="10" cy="10" r="3.5" stroke="currentColor" />
    </svg>
  );
}

function MoonIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" {...props}>
      <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" d="M16 12.5A6.5 6.5 0 1 1 7.5 4c0 .26.02.51.06.76a5 5 0 1 0 7.68 7.68c.25.04.5.06.76.06Z"/>
    </svg>
  );
}

export default function ThemeToggle() {
  const [theme, setThemeState] = React.useState<Theme>('light');

  React.useEffect(() => {
    setThemeState(readPreferredTheme());
  }, []);

  const toggle = () => {
    const next: Theme = theme === 'dark' ? 'light' : 'dark';
    setTheme(next);
    setThemeState(next);
  };

  const isDark = theme === 'dark';

  return (
    <button
      type="button"
      onClick={toggle}
      className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[rgb(var(--bg-default))] text-[rgb(var(--content-emphasis))] hover:bg-[rgb(var(--bg-muted))] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[rgb(var(--border-default))]"
      aria-label="Toggle dark mode"
      title="Toggle dark mode"
    >
      {isDark ? (
        <MoonIcon className="size-4" />
      ) : (
        <SunIcon className="size-4" />
      )}
    </button>
  );
}


import { describe, it, expect } from 'vitest';
import { formatNumber, formatDate, truncate } from '@/lib/format';

describe('format utils', () => {
  it('formatNumber formats integers', () => {
    expect(formatNumber(0)).toBe('0');
    expect(formatNumber(1234)).toMatch(/1[,.]?234/);
  });

  it('formatDate formats ISO date', () => {
    const s = formatDate('2024-01-02T00:00:00Z');
    expect(s).toMatch(/2024/);
  });

  it('truncate truncates long strings', () => {
    expect(truncate('abc', 2)).toBe('a…');
    expect(truncate('abc', 3)).toBe('abc');
  });
});


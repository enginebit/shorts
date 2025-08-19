import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { Input } from '@/components/ui/input';

describe('Input ARIA', () => {
  it('sets aria-invalid and aria-describedby when error is present', () => {
    render(<Input id="email" error="Required" />);
    const input = screen.getByRole('textbox');
    expect(input).toHaveAttribute('aria-invalid', 'true');
    expect(input).toHaveAttribute('aria-describedby', 'email-error');
    const alert = screen.getByRole('alert');
    expect(alert).toHaveTextContent('Required');
  });
});


import { render } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { Modal } from '@/components/ui/modal';

// Simple smoke test for a11y roles on modal

describe('Modal a11y', () => {
  it('renders role="dialog" and aria-modal', () => {
    const { container } = render(
      <Modal showModal>
        <div>Content</div>
      </Modal>
    );

    const dialog = container.querySelector('[role="dialog"]');
    expect(dialog).toBeTruthy();
    expect(dialog?.getAttribute('aria-modal')).toBe('true');
  });
});


import { describe, it, expect, afterEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Tiptap from '../tiptap';

describe('Tiptap DropdownMenu modal={false}', () => {
  afterEach(() => {
    document.body.removeAttribute('style');
  });

  it('段落ドロップダウンを開いても body に overflow:hidden が付与されない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const paragraphBtn = screen.getByText('段落').closest('button')!;
    await user.click(paragraphBtn);

    expect(document.body.style.overflow).not.toBe('hidden');
    expect(document.body.style.paddingRight).toBe('');
  });

  it('フォントサイズドロップダウンを開いても body に overflow:hidden が付与されない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const fontSizeBtn = screen.getByText('14px').closest('button')!;
    await user.click(fontSizeBtn);

    expect(document.body.style.overflow).not.toBe('hidden');
    expect(document.body.style.paddingRight).toBe('');
  });

  it('文字色ドロップダウンを開いても body に overflow:hidden が付与されない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const textColorBtn = screen.getByTitle('文字色');
    await user.click(textColorBtn);

    expect(document.body.style.overflow).not.toBe('hidden');
    expect(document.body.style.paddingRight).toBe('');
  });

  it('背景色ドロップダウンを開いても body に overflow:hidden が付与されない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const bgColorBtn = screen.getByTitle('背景色');
    await user.click(bgColorBtn);

    expect(document.body.style.overflow).not.toBe('hidden');
    expect(document.body.style.paddingRight).toBe('');
  });
});

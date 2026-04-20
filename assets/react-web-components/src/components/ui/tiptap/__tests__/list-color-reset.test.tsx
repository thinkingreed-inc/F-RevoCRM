import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Tiptap from '../tiptap';

// JSDOM の getClientRects / getBoundingClientRect モック
// ProseMirror の scrollToSelection → coordsAtPos → singleRect で呼ばれる
// value に初期テキストを持つ場合、focus().chain() で scrollToSelection が実行されエラーになるため
const EMPTY_RECT: DOMRect = {
  top: 0, bottom: 0, left: 0, right: 0,
  width: 0, height: 0, x: 0, y: 0,
  toJSON: () => ({}),
};
const mockGetClientRects = () => {
  Range.prototype.getClientRects = vi.fn(() => {
    const list = [EMPTY_RECT] as unknown as DOMRectList;
    (list as unknown as { item: (i: number) => DOMRect | null }).item = (i: number) => list[i] ?? null;
    return list;
  });
  Range.prototype.getBoundingClientRect = vi.fn(() => EMPTY_RECT);
  Element.prototype.getClientRects = vi.fn(() => {
    const list = [EMPTY_RECT] as unknown as DOMRectList;
    (list as unknown as { item: (i: number) => DOMRect | null }).item = (i: number) => list[i] ?? null;
    return list;
  });
};

/**
 * 色ピッカーを開き、指定インデックスのスウォッチを mousedown で選択する。
 *
 * fireEvent.mouseDown を使用してマウスボタン保持状態にならないようにする。
 * user.pointer({ keys: '[MouseLeft>]' }) はマウスボタンを保持するため、
 * 連続して別のピッカーを開く際に干渉が生じる。
 *
 * テキスト色パレット先頭は #000000（デフォルト値と同値）のため、
 * テキスト色テストでは index=1（#1e293b）を指定してデフォルト値との区別を可能にする。
 */
async function pickColorSwatch(
  user: ReturnType<typeof userEvent.setup>,
  btnTitle: string,
  swatchIndex = 0
) {
  const btn = screen.getByTitle(btnTitle);
  await user.click(btn);
  await waitFor(() => {
    expect(document.querySelectorAll('.tiptap-color-swatch').length).toBeGreaterThan(0);
  });
  const swatch = document.querySelectorAll('.tiptap-color-swatch')[swatchIndex] as HTMLElement;
  fireEvent.mouseDown(swatch);
  return btn;
}

describe('リストトグル時の文字色・背景色リセットバグ修正', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    mockGetClientRects();
    document.elementFromPoint = vi.fn(() => null);
  });

  // TC-001: カーソルのみ状態で文字色設定後に bulletList ON
  // swatchIndex=1 → #1e293b（テキスト色パレット2番目）。
  // デフォルト/リセット値 #000000（rgb(0,0,0)）とは異なるため、
  // バグ発生時（#000000 にリセット）を正しく検出できる。
  it('文字色設定後に bulletList をトグルしても文字色がリセットされない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // 非デフォルト色（#1e293b）を選択
    const colorBtn = await pickColorSwatch(user, '文字色', 1);

    // 文字色がインジケーターに反映されるまで待つ
    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      // バグ後リセット値 rgb(0,0,0) でないことを確認
      expect(indicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
    }, { timeout: 2000 });

    // bulletList ボタンを mousedown でトグル
    const bulletBtn = screen.getByTitle('箇条書きリスト');
    fireEvent.mouseDown(bulletBtn);

    // bulletList がアクティブになること
    await waitFor(() => {
      expect(bulletBtn.className).toContain('active');
    }, { timeout: 2000 });

    // 文字色インジケーターがリセット（rgb(0,0,0)）されていないこと
    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      expect(indicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
    }, { timeout: 2000 });
  });

  // TC-002: カーソルのみ状態でハイライト設定後に bulletList ON
  // ハイライトのデフォルト値は ""（falsy）。
  // バグ発生時はインジケーターが消えるため、not.toBeNull() で検出できる。
  it('ハイライト設定後に bulletList をトグルしてもハイライトがリセットされない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // ハイライト色を選択（index=0 = #fef08a、デフォルト "" とは異なる）
    const highlightBtn = await pickColorSwatch(user, '背景色', 0);

    // ハイライトインジケーターが表示されるまで待つ
    await waitFor(() => {
      const indicator = highlightBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      expect(indicator?.style.backgroundColor).not.toBe('');
    }, { timeout: 2000 });

    const bulletBtn = screen.getByTitle('箇条書きリスト');
    fireEvent.mouseDown(bulletBtn);

    await waitFor(() => {
      expect(bulletBtn.className).toContain('active');
    }, { timeout: 2000 });

    // ハイライトインジケーターがリセット（消滅）されていないこと
    await waitFor(() => {
      const indicator = highlightBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      expect(indicator?.style.backgroundColor).not.toBe('');
    }, { timeout: 2000 });
  });

  // TC-003: 文字色・ハイライト両方設定後に orderedList ON
  it('文字色とハイライト両方設定後に orderedList をトグルしても両方がリセットされない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // 文字色選択（非デフォルト色 #1e293b）
    const colorBtn = await pickColorSwatch(user, '文字色', 1);
    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
    }, { timeout: 2000 });

    // ハイライト選択
    const highlightBtn = await pickColorSwatch(user, '背景色', 0);
    await waitFor(() => {
      const indicator = highlightBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
    }, { timeout: 2000 });

    const orderedBtn = screen.getByTitle('番号付きリスト');
    fireEvent.mouseDown(orderedBtn);

    await waitFor(() => {
      expect(orderedBtn.className).toContain('active');
    }, { timeout: 2000 });

    await waitFor(() => {
      const colorIndicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      const highlightIndicator = highlightBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(colorIndicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
      expect(highlightIndicator).not.toBeNull();
      expect(highlightIndicator?.style.backgroundColor).not.toBe('');
    }, { timeout: 2000 });
  });

  // TC-004: 色未設定状態でのリストトグル（正常動作の保証）
  it('色未設定状態で bulletList をトグルしてもエラーなくアクティブになる', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const bulletBtn = screen.getByTitle('箇条書きリスト');
    fireEvent.mouseDown(bulletBtn);

    await waitFor(() => {
      expect(bulletBtn.className).toContain('active');
    }, { timeout: 2000 });
  });

  // TC-005: bulletList OFF 時（既にリスト状態からトグル）
  it('bulletList を OFF にしても文字色が維持される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const colorBtn = await pickColorSwatch(user, '文字色', 1);
    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
    }, { timeout: 2000 });

    // bulletList ON
    const bulletBtn = screen.getByTitle('箇条書きリスト');
    fireEvent.mouseDown(bulletBtn);
    await waitFor(() => {
      expect(bulletBtn.className).toContain('active');
    }, { timeout: 2000 });

    // bulletList OFF
    fireEvent.mouseDown(bulletBtn);
    await waitFor(() => {
      expect(bulletBtn.className).not.toContain('active');
    }, { timeout: 2000 });

    // 文字色が維持されていること
    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      expect(indicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
    }, { timeout: 2000 });
  });

  // TC-006: テキストを含む状態での bulletList ON（JSDOM制約によりカーソル状態で代替）
  // テキスト実選択状態は JSDOM の Selection API 制約でシミュレート困難なため、
  // テキストを含む状態でカーソルのみで検証する。
  // 実選択状態のカバレッジは Playwright e2e テストで補完する（別タスク）。
  it('テキストを含む状態で文字色設定後に bulletList をトグルしても文字色がリセットされない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="<p>テストテキスト</p>" name="test" />);

    const colorBtn = await pickColorSwatch(user, '文字色', 1);
    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
    }, { timeout: 2000 });

    const bulletBtn = screen.getByTitle('箇条書きリスト');
    fireEvent.mouseDown(bulletBtn);

    await waitFor(() => {
      expect(bulletBtn.className).toContain('active');
    }, { timeout: 2000 });

    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      expect(indicator?.style.backgroundColor).not.toMatch(/^(rgb\(0,\s*0,\s*0\)|#000000)$/);
    }, { timeout: 2000 });
  });

  // TC-007: カーソルのみ状態でフォントサイズ設定後に bulletList ON
  it('フォントサイズ設定後に bulletList をトグルしてもフォントサイズがリセットされない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // フォントサイズを 24px に変更
    const trigger = screen.getByText('14px');
    await user.click(trigger);
    const menuItem24 = Array.from(screen.getAllByText('24px')).find(
      (el) => el.closest('[role="menuitem"]') !== null
    );
    expect(menuItem24).toBeTruthy();
    await user.click(menuItem24!);

    // ツールバーが 24px に更新されるまで待つ
    const getFontSizeTrigger = () => {
      const buttons = document.querySelectorAll('.tiptap-block-select');
      return Array.from(buttons).find(
        (btn) =>
          btn.querySelector('span')?.textContent?.includes('px') &&
          !btn.querySelector('span')?.textContent?.includes('段落') &&
          !btn.querySelector('span')?.textContent?.includes('見出し')
      );
    };
    await waitFor(() => {
      expect(getFontSizeTrigger()?.querySelector('span')?.textContent).toContain('24px');
    }, { timeout: 2000 });

    // bulletList をトグル
    const bulletBtn = screen.getByTitle('箇条書きリスト');
    fireEvent.mouseDown(bulletBtn);

    await waitFor(() => {
      expect(bulletBtn.className).toContain('active');
    }, { timeout: 2000 });

    // フォントサイズが 24px のまま維持されること（リセットされて 14px になっていないこと）
    await waitFor(() => {
      expect(getFontSizeTrigger()?.querySelector('span')?.textContent).toContain('24px');
    }, { timeout: 2000 });
  });
});

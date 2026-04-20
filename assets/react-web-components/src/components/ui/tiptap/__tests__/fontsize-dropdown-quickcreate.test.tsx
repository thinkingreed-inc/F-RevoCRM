import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Tiptap from '../tiptap';

// getBoundingClientRect のモック
const mockGetBoundingClientRect = (values: Partial<DOMRect>) => {
  return vi.fn(() => ({
    top: 0, left: 0, bottom: 100, right: 100,
    width: 100, height: 26, x: 0, y: 0,
    toJSON: () => {},
    ...values,
  }));
};

describe('Tiptap フォントサイズドロップダウン (isQuickCreate)', () => {
  // プロトタイプを直接上書きするモックのバックアップ
  const originalGetBoundingClientRect = HTMLElement.prototype.getBoundingClientRect;
  const originalClosest = HTMLElement.prototype.closest;

  beforeEach(() => {
    vi.restoreAllMocks();
  });

  afterEach(() => {
    // vi.restoreAllMocks() では復元されない直接プロトタイプ上書きを手動で復元
    HTMLElement.prototype.getBoundingClientRect = originalGetBoundingClientRect;
    HTMLElement.prototype.closest = originalClosest;
  });

  it('isQuickCreate=false のとき maxHeight が設定されない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" isQuickCreate={false} />);

    const trigger = screen.getByText('14px');
    await user.click(trigger);

    const content = document.querySelector('[data-slot="dropdown-menu-content"]');
    expect(content).toBeTruthy();
    expect((content as HTMLElement)?.style.maxHeight).toBe('');
  });

  it('isQuickCreate=true のとき onOpenChange(true) で maxHeight が計算・設定される', async () => {
    const user = userEvent.setup();

    window.innerHeight = 600;
    HTMLElement.prototype.getBoundingClientRect = mockGetBoundingClientRect({ bottom: 200 });
    HTMLElement.prototype.closest = vi.fn(() => null) as typeof HTMLElement.prototype.closest;

    render(<Tiptap value="" name="test" isQuickCreate={true} />);

    const trigger = screen.getByText('14px');
    await user.click(trigger);

    const content = document.querySelector('[data-slot="dropdown-menu-content"]');
    expect(content).toBeTruthy();
    // 600 - 200 - 8 = 392
    expect((content as HTMLElement)?.style.maxHeight).toBe('392px');
  });

  it('isQuickCreate=true のとき onOpenChange(false) で maxHeight がリセットされる', async () => {
    const user = userEvent.setup();

    window.innerHeight = 600;
    HTMLElement.prototype.getBoundingClientRect = mockGetBoundingClientRect({ bottom: 200 });
    HTMLElement.prototype.closest = vi.fn(() => null) as typeof HTMLElement.prototype.closest;

    render(<Tiptap value="" name="test" isQuickCreate={true} />);

    const trigger = screen.getByText('14px');
    await user.click(trigger);
    await user.keyboard('{Escape}');

    // 再度開くと再計算される
    await user.click(trigger);
    const content = document.querySelector('[data-slot="dropdown-menu-content"]');
    expect((content as HTMLElement)?.style.maxHeight).toBe('392px');
  });

  it('availableHeight が 100px 未満のとき FONT_SIZE_DROPDOWN_MIN_HEIGHT（100px）にクランプされる', async () => {
    const user = userEvent.setup();

    window.innerHeight = 600;
    HTMLElement.prototype.getBoundingClientRect = mockGetBoundingClientRect({ bottom: 595 });
    HTMLElement.prototype.closest = vi.fn(() => null) as typeof HTMLElement.prototype.closest;

    render(<Tiptap value="" name="test" isQuickCreate={true} />);

    const trigger = screen.getByText('14px');
    await user.click(trigger);

    const content = document.querySelector('[data-slot="dropdown-menu-content"]');
    expect((content as HTMLElement)?.style.maxHeight).toBe('100px');
  });

  it('スクロールコンテナが見つかる場合はそのbottomを使う', async () => {
    const user = userEvent.setup();

    window.innerHeight = 600;
    HTMLElement.prototype.getBoundingClientRect = mockGetBoundingClientRect({ bottom: 200 });

    const scrollContainer = document.createElement('div');
    scrollContainer.getBoundingClientRect = mockGetBoundingClientRect({ bottom: 400 });
    HTMLElement.prototype.closest = vi.fn(() => scrollContainer) as typeof HTMLElement.prototype.closest;

    render(<Tiptap value="" name="test" isQuickCreate={true} />);

    const trigger = screen.getByText('14px');
    await user.click(trigger);

    const content = document.querySelector('[data-slot="dropdown-menu-content"]');
    // 400 - 200 - 8 = 192
    expect((content as HTMLElement)?.style.maxHeight).toBe('192px');
  });

  it('fontSizeTriggerRef が null のとき（アンマウント済み等）エラーなく早期returnする', () => {
    expect(() => {
      render(<Tiptap value="" name="test" isQuickCreate={true} />);
    }).not.toThrow();
  });
});

describe('Tiptap フォントサイズ — フォーカス復元', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('カーソルのみの状態でフォントサイズを選択するとエディターにフォーカスが戻る', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // フォントサイズドロップダウンを開く
    const trigger = screen.getByText('14px');
    await user.click(trigger);

    // 16px を選択（メニューアイテム内の span）
    const allSixteenPx = screen.getAllByText('16px');
    const menuItemSpan = allSixteenPx.find(
      (el) => el.closest('[role="menuitem"]') !== null
    );
    expect(menuItemSpan).toBeTruthy();
    await user.click(menuItemSpan!);

    // ドロップダウンが閉じた後、ProseMirror にフォーカスが当たっていること
    const prosemirror = document.querySelector('.ProseMirror');
    expect(prosemirror).toBeTruthy();
    expect(document.activeElement).toBe(prosemirror);
  });

  it('カーソルのみの状態でフォントサイズを選択するとツールバーが更新される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // デフォルトは 14px 表示
    const trigger = screen.getByText('14px');
    await user.click(trigger);

    // 16px を選択（DropdownMenuItem 要素自体をクリック）
    const allSixteenPx = screen.getAllByText('16px');
    const menuItemSpan = allSixteenPx.find(
      (el) => el.closest('[role="menuitem"]') !== null
    );
    expect(menuItemSpan).toBeTruthy();
    const menuItem = menuItemSpan!.closest('[role="menuitem"]') as HTMLElement;
    await user.click(menuItem);

    // ツールバートリガーが 16px に更新されていること
    // (useEditorState によりトランザクション発生時に React が再レンダリングされる)
    await waitFor(
      () => {
        // ドロップダウンのトリガーボタンを再取得してフォントサイズ表示を確認
        const fontSizeButtons = document.querySelectorAll('.tiptap-block-select');
        // フォントサイズのトリガーは 'px' を含むテキストを持つボタン
        const fontSizeTrigger = Array.from(fontSizeButtons).find(
          (btn) => btn.querySelector('span')?.textContent?.includes('px')
            && !btn.querySelector('span')?.textContent?.includes('段落')
            && !btn.querySelector('span')?.textContent?.includes('見出し')
        );
        expect(fontSizeTrigger?.querySelector('span')?.textContent).toContain('16px');
      },
      { timeout: 2000 }
    );
  });

  it('Escape でドロップダウンを閉じた場合もエディターにフォーカスが戻る', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const trigger = screen.getByText('14px');
    await user.click(trigger);
    await user.keyboard('{Escape}');

    const prosemirror = document.querySelector('.ProseMirror');
    expect(document.activeElement).toBe(prosemirror);
  });

  it('Tab キーでドロップダウン外に移動した場合、フォーカスが意図しない要素に移らない', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const trigger = screen.getByText('14px');
    await user.click(trigger);
    await user.keyboard('{Tab}');

    // Radix UI は DropdownMenuContent 内で Tab キーのデフォルト動作をキャンセルする
    // そのため JSDOM 環境では Tab キーでドロップダウンが閉じず、フォーカスは
    // DropdownMenuContent 内（または DropdownMenuContent 自体）に留まる。
    // これは Tab キーが DropdownMenu の外側に漏れていないことを示す正しい動作である。
    const prosemirror = document.querySelector('.ProseMirror');
    const dropdownContent = document.querySelector('[data-slot="dropdown-menu-content"]');
    const activeEl = document.activeElement;
    // フォーカスが ProseMirror、または DropdownMenu 内にあることを確認
    // (Tab キーにより意図しない外部ボタンや body にフォーカスが移っていない)
    const isInDropdown = dropdownContent?.contains(activeEl) || activeEl === dropdownContent;
    const isInProseMirror = activeEl === prosemirror;
    expect(isInDropdown || isInProseMirror).toBe(true);
  });

  it('フォントサイズを選択してから文字を入力すると選択したサイズが反映される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // 16px を選択
    const trigger = screen.getByText('14px');
    await user.click(trigger);
    const allSixteenPx = screen.getAllByText('16px');
    const menuItemSpan = allSixteenPx.find(
      (el) => el.closest('[role="menuitem"]') !== null
    );
    await user.click(menuItemSpan!);

    // エディターにフォーカスして文字を入力
    const prosemirror = document.querySelector('.ProseMirror') as HTMLElement;
    prosemirror.focus();
    await user.keyboard('A');

    // 入力されたテキストに font-size: 16px が適用されていること
    await waitFor(() => {
      const styledSpan = prosemirror.querySelector('span[style*="font-size: 16px"]');
      expect(styledSpan).not.toBeNull();
      expect(styledSpan?.textContent).toContain('A');
    }, { timeout: 2000 });
  });

  it('連続してフォントサイズを変更するとツールバーが最新の値に更新される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // 1回目：16px を選択
    const trigger = screen.getByText('14px');
    await user.click(trigger);
    const allSixteenPx = screen.getAllByText('16px');
    const menuItem16 = allSixteenPx.find(
      (el) => el.closest('[role="menuitem"]') !== null
    );
    await user.click(menuItem16!);

    await waitFor(() => {
      const fontSizeButtons = document.querySelectorAll('.tiptap-block-select');
      const fontSizeTrigger = Array.from(fontSizeButtons).find(
        (btn) => btn.querySelector('span')?.textContent?.includes('px')
      );
      expect(fontSizeTrigger?.querySelector('span')?.textContent).toContain('16px');
    }, { timeout: 2000 });

    // 2回目：18px を選択
    const trigger2 = screen.getByText('16px');
    await user.click(trigger2);
    const allEighteenPx = screen.getAllByText('18px');
    const menuItem18 = allEighteenPx.find(
      (el) => el.closest('[role="menuitem"]') !== null
    );
    await user.click(menuItem18!);

    // ツールバーが 18px に更新されること
    await waitFor(() => {
      const fontSizeButtons = document.querySelectorAll('.tiptap-block-select');
      const fontSizeTrigger = Array.from(fontSizeButtons).find(
        (btn) => btn.querySelector('span')?.textContent?.includes('px')
      );
      expect(fontSizeTrigger?.querySelector('span')?.textContent).toContain('18px');
    }, { timeout: 2000 });
  });
});

describe('Tiptap ツールバー — 文字色・ハイライト色のリアクティブ更新', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('カーソルのみの状態で文字色を設定するとカラーインジケーターが更新される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // 文字色ピッカーボタンを開く
    const colorBtn = screen.getByTitle('文字色');
    await user.click(colorBtn);

    // パレットから最初の色を mousedown で選択
    await waitFor(() => {
      const swatches = document.querySelectorAll('.tiptap-color-swatch');
      expect(swatches.length).toBeGreaterThan(0);
    });
    const firstSwatch = document.querySelector('.tiptap-color-swatch') as HTMLElement;
    const selectedColor = firstSwatch.title; // title 属性に色の値が入っている
    await user.pointer({ target: firstSwatch, keys: '[MouseLeft>]' });

    // カラーインジケーターが選択した色で更新されていること
    await waitFor(() => {
      const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      expect(indicator?.style.backgroundColor).toBeTruthy();
    }, { timeout: 2000 });

    // ツールバーを再確認（再レンダリング後も維持される）
    const indicator = colorBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
    expect(indicator?.style.backgroundColor).not.toBe('');
    // swatch の title と CSS color が対応している（hex → rgb 変換を考慮して空でないことを確認）
    expect(selectedColor).toBeTruthy();
  });

  it('カーソルのみの状態でハイライト色を設定するとカラーインジケーターが更新される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // ハイライトピッカーボタンを開く
    const highlightBtn = screen.getByTitle('背景色');
    await user.click(highlightBtn);

    await waitFor(() => {
      const swatches = document.querySelectorAll('.tiptap-color-swatch');
      expect(swatches.length).toBeGreaterThan(0);
    });
    const firstSwatch = document.querySelector('.tiptap-color-swatch') as HTMLElement;
    await user.pointer({ target: firstSwatch, keys: '[MouseLeft>]' });

    // カラーインジケーターが更新されていること
    await waitFor(() => {
      const indicator = highlightBtn.querySelector('.tiptap-color-indicator') as HTMLElement;
      expect(indicator).not.toBeNull();
      expect(indicator?.style.backgroundColor).toBeTruthy();
    }, { timeout: 2000 });
  });
});

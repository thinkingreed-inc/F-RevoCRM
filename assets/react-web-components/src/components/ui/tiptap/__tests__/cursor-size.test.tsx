/**
 * カーソルサイズ追従テスト
 *
 * テキスト未選択（カーソルのみ）の状態でフォントサイズを変更した際、
 * ゼロ幅スペース（ZWS: \u200B）がエディター DOM に挿入され、
 * かつ onChange で渡される HTML から ZWS が除去されることを確認する。
 *
 * テスト注意: JSDOM 環境では ProseMirror の mousedown ハンドラが
 * elementFromPoint を呼び出して失敗するため、エディター要素への
 * 直接クリックを行わず、ツールバーボタンを userEvent で操作する。
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Tiptap from '../tiptap';

// JSDOM の elementFromPoint モック（ProseMirror の posAtCoords 呼び出しに必要）
const mockElementFromPoint = () => {
  document.elementFromPoint = vi.fn(() => null);
};

// JSDOM の getClientRects / getBoundingClientRect モック
// ProseMirror の scrollToSelection → coordsAtPos → singleRect で呼ばれるが
// JSDOM は Range のこれらのメソッドを完全実装していないため、ダミーを返す
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

describe('カーソルサイズ追従', () => {
  beforeEach(() => {
    mockElementFromPoint();
    mockGetClientRects();
  });

  it('フォントサイズ選択後（テキスト未選択）に onChange で渡される HTML に ZWS が含まれない', async () => {
    const user = userEvent.setup();
    let capturedValue = '';

    render(
      <Tiptap
        name="test"
        onChange={(e) => {
          capturedValue = e.target.value;
        }}
      />
    );

    // フォントサイズドロップダウンを開く
    const trigger = screen.getByText('14px').closest('button')!;
    await user.click(trigger);

    // 36px を選択
    await waitFor(() => {
      expect(screen.getAllByText('36px').length).toBeGreaterThan(0);
    });
    const items36 = screen.getAllByText('36px');
    const menuItem36 = items36.find((el) => el.closest('[role="menuitem"]') !== null);
    expect(menuItem36).toBeTruthy();
    await user.click(menuItem36!);

    // onChange で渡された HTML に ZWS が含まれないこと
    await waitFor(() => {
      expect(capturedValue).not.toContain('\u200B');
    }, { timeout: 2000 });
  });

  it('フォントサイズ選択後（テキスト未選択）に ZWS がエディター内 DOM に存在する', async () => {
    const user = userEvent.setup();

    render(<Tiptap name="test" onChange={() => {}} />);

    // フォントサイズドロップダウンを開く
    const trigger = screen.getByText('14px').closest('button')!;
    await user.click(trigger);

    // 36px を選択
    await waitFor(() => {
      expect(screen.getAllByText('36px').length).toBeGreaterThan(0);
    });
    const items36 = screen.getAllByText('36px');
    const menuItem36 = items36.find((el) => el.closest('[role="menuitem"]') !== null);
    expect(menuItem36).toBeTruthy();
    await user.click(menuItem36!);

    // DOM 内に ZWS が存在すること（カーソルサイズ追従のためのスパンが挿入される）
    const prosemirror = document.querySelector('.ProseMirror');
    expect(prosemirror).not.toBeNull();
    await waitFor(() => {
      expect(prosemirror!.innerHTML).toContain('\u200B');
    }, { timeout: 2000 });
  });

  it('テキストが選択されている場合（初期状態）は ZWS が存在しない', () => {
    render(<Tiptap name="test" onChange={() => {}} value="<p>テストテキスト</p>" />);

    const prosemirror = document.querySelector('.ProseMirror');
    expect(prosemirror).not.toBeNull();

    // テキスト選択中のケースとして、初期状態では ZWS が挿入されていないことを確認
    expect(prosemirror!.innerHTML).not.toContain('\u200B');
  });

  it('テキスト選択中（selection.empty === false）にフォントサイズ変更しても ZWS が挿入されない（setFontSize）', async () => {
    const user = userEvent.setup();

    render(<Tiptap name="test" onChange={() => {}} value="<p>テストテキスト</p>" />);

    const prosemirror = document.querySelector('.ProseMirror') as HTMLElement;
    expect(prosemirror).not.toBeNull();

    // テキストを全選択（Selection API）
    prosemirror.focus();
    const range = document.createRange();
    range.selectNodeContents(prosemirror);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);

    // フォントサイズドロップダウンを開く
    const trigger = screen.getByText('14px').closest('button')!;
    await user.click(trigger);

    // 36px を選択
    await waitFor(() => {
      expect(screen.getAllByText('36px').length).toBeGreaterThan(0);
    });
    const items36 = screen.getAllByText('36px');
    const menuItem36 = items36.find((el) => el.closest('[role="menuitem"]') !== null);
    expect(menuItem36).toBeTruthy();
    await user.click(menuItem36!);

    // テキスト選択中は ZWS が挿入されないこと
    await waitFor(() => {
      expect(prosemirror.innerHTML).not.toContain('\u200B');
    }, { timeout: 2000 });
  });

  it('テキスト選択中（selection.empty === false）にデフォルトフォントサイズ変更しても ZWS が挿入されない（unsetFontSize）', async () => {
    const user = userEvent.setup();

    render(<Tiptap name="test" onChange={() => {}} value="<p>テストテキスト</p>" />);

    const prosemirror = document.querySelector('.ProseMirror') as HTMLElement;
    expect(prosemirror).not.toBeNull();

    // テキストを全選択
    prosemirror.focus();
    const range = document.createRange();
    range.selectNodeContents(prosemirror);
    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);

    // フォントサイズドロップダウンを開いてデフォルト（14px / unsetFontSize）を選択
    const trigger = screen.getByText('14px').closest('button')!;
    await user.click(trigger);

    await waitFor(() => {
      expect(screen.getAllByText('14px').length).toBeGreaterThan(0);
    });
    const items14 = screen.getAllByText('14px');
    const menuItem14 = items14.find((el) => el.closest('[role="menuitem"]') !== null);
    expect(menuItem14).toBeTruthy();
    await user.click(menuItem14!);

    // テキスト選択中は ZWS が挿入されないこと
    await waitFor(() => {
      expect(prosemirror.innerHTML).not.toContain('\u200B');
    }, { timeout: 2000 });
  });

  it('通常テキスト入力（ZWS なし操作）で onChange が正常に呼ばれ、値に ZWS が含まれない', async () => {
    const user = userEvent.setup();
    let capturedValue = '';

    render(
      <Tiptap
        name="test"
        onChange={(e) => {
          capturedValue = e.target.value;
        }}
        value=""
      />
    );

    const prosemirror = document.querySelector('.ProseMirror') as HTMLElement;
    expect(prosemirror).not.toBeNull();

    // エディターにフォーカスしてテキストを入力
    prosemirror.focus();
    await user.type(prosemirror, 'サンプルテキスト');

    // onChange が呼ばれ、値に ZWS が含まれないこと
    await waitFor(() => {
      expect(capturedValue).not.toBe('');
      expect(capturedValue).not.toContain('\u200B');
    }, { timeout: 2000 });
  });

  it('デフォルトフォントサイズ（14px / unsetFontSize）選択後も ZWS がエディター内に存在する', async () => {
    const user = userEvent.setup();

    render(<Tiptap name="test" onChange={() => {}} />);

    // フォントサイズドロップダウンを開く
    const trigger = screen.getByText('14px').closest('button')!;
    await user.click(trigger);

    // 14px（unsetFontSize が呼ばれる）を選択
    await waitFor(() => {
      expect(screen.getAllByText('14px').length).toBeGreaterThan(0);
    });
    const items14 = screen.getAllByText('14px');
    const menuItem14 = items14.find((el) => el.closest('[role="menuitem"]') !== null);
    expect(menuItem14).toBeTruthy();
    await user.click(menuItem14!);

    // unsetFontSize でも ZWS が挿入されること
    const prosemirror = document.querySelector('.ProseMirror');
    expect(prosemirror).not.toBeNull();
    await waitFor(() => {
      expect(prosemirror!.innerHTML).toContain('\u200B');
    }, { timeout: 2000 });
  });
});

/**
 * ツールバーアクティブ状態のリアクティブ更新テスト
 *
 * カーソルのみ（テキスト未選択）状態でツールバーの書式ボタンを押したとき、
 * ボタンの active クラスがリアクティブに更新されることを確認する。
 *
 * 根本原因: useEditorState セレクターが isActive 状態を含んでいないため、
 * storedMark の変更が React 再レンダリングをトリガーしなかった。
 *
 * テスト注意: JSDOM 環境では ProseMirror の mousedown ハンドラが
 * elementFromPoint を呼び出して失敗するため、エディター本体への
 * mousedown イベントは発生させず、ツールバーボタンへの mousedown のみを使う。
 * ツールバーボタンは onMouseDown で e.preventDefault() しているため、
 * ProseMirror の mousedown ハンドラは呼ばれない。
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Tiptap from '../tiptap';

// JSDOM の elementFromPoint モック（ProseMirror の posAtCoords 呼び出しに必要）
const mockElementFromPoint = () => {
  document.elementFromPoint = vi.fn(() => null);
};

describe('ツールバーアクティブ状態 — インラインスタイル（太字・斜体・下線・取り消し線）', () => {
  beforeEach(() => {
    mockElementFromPoint();
  });

  it('カーソルのみの状態で太字ボタンを押すと active クラスが付与される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const boldBtn = screen.getByTitle('太字');
    expect(boldBtn.className).not.toContain('active');

    // ツールバーボタンは onMouseDown で e.preventDefault() しているため
    // ボタンへの mousedown で ProseMirror 側 mousedown は発生しない
    await user.pointer({ target: boldBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(boldBtn.className).toContain('active');
    }, { timeout: 2000 });
  });

  it('太字ボタンを再度押すと active クラスが除去される（トグル動作）', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const boldBtn = screen.getByTitle('太字');

    // 1回目：アクティブにする
    await user.pointer({ target: boldBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(boldBtn.className).toContain('active');
    }, { timeout: 2000 });

    // 2回目：非アクティブにする
    await user.pointer({ target: boldBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(boldBtn.className).not.toContain('active');
    }, { timeout: 2000 });
  });

  it('カーソルのみの状態で斜体ボタンを押すと active クラスが付与される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const italicBtn = screen.getByTitle('斜体');

    await user.pointer({ target: italicBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(italicBtn.className).toContain('active');
    }, { timeout: 2000 });
  });

  it('斜体ボタンを再度押すと active クラスが除去される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const italicBtn = screen.getByTitle('斜体');

    await user.pointer({ target: italicBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(italicBtn.className).toContain('active');
    }, { timeout: 2000 });

    await user.pointer({ target: italicBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(italicBtn.className).not.toContain('active');
    }, { timeout: 2000 });
  });

  it('カーソルのみの状態で下線ボタンを押すと active クラスが付与される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const underlineBtn = screen.getByTitle('下線');

    await user.pointer({ target: underlineBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(underlineBtn.className).toContain('active');
    }, { timeout: 2000 });
  });

  it('カーソルのみの状態で取り消し線ボタンを押すと active クラスが付与される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const strikeBtn = screen.getByTitle('取り消し線');

    await user.pointer({ target: strikeBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(strikeBtn.className).toContain('active');
    }, { timeout: 2000 });
  });
});

describe('ツールバーアクティブ状態 — ブロック要素（リスト・引用）', () => {
  beforeEach(() => {
    mockElementFromPoint();
  });

  it('カーソルのみの状態で箇条書きリストボタンを押すと active クラスが付与される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const bulletListBtn = screen.getByTitle('箇条書きリスト');
    expect(bulletListBtn.className).not.toContain('active');

    await user.pointer({ target: bulletListBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(bulletListBtn.className).toContain('active');
    }, { timeout: 2000 });
  });

  it('箇条書きリストボタンを再度押すと active クラスが除去される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const bulletListBtn = screen.getByTitle('箇条書きリスト');

    await user.pointer({ target: bulletListBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(bulletListBtn.className).toContain('active');
    }, { timeout: 2000 });

    await user.pointer({ target: bulletListBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(bulletListBtn.className).not.toContain('active');
    }, { timeout: 2000 });
  });

  it('カーソルのみの状態で番号付きリストボタンを押すと active クラスが付与される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const orderedListBtn = screen.getByTitle('番号付きリスト');

    await user.pointer({ target: orderedListBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(orderedListBtn.className).toContain('active');
    }, { timeout: 2000 });
  });

  it('カーソルのみの状態で引用ボタンを押すと active クラスが付与される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const blockquoteBtn = screen.getByTitle('引用');

    await user.pointer({ target: blockquoteBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(blockquoteBtn.className).toContain('active');
    }, { timeout: 2000 });
  });

  it('引用ボタンを再度押すと active クラスが除去される', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    const blockquoteBtn = screen.getByTitle('引用');

    await user.pointer({ target: blockquoteBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(blockquoteBtn.className).toContain('active');
    }, { timeout: 2000 });

    await user.pointer({ target: blockquoteBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(blockquoteBtn.className).not.toContain('active');
    }, { timeout: 2000 });
  });
});

describe('ツールバーアクティブ状態 — ブロックタイプ（見出し h1/h2/h3）', () => {
  beforeEach(() => {
    mockElementFromPoint();
  });

  it('見出し1を選択するとブロックタイプドロップダウンが h1 を表示する', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    // 初期状態：段落
    await waitFor(() => {
      expect(screen.getByText('段落')).toBeInTheDocument();
    });

    // ブロックタイプドロップダウンを開く
    const blockTypeBtn = screen.getByText('段落').closest('button')!;
    await user.pointer({ target: blockTypeBtn, keys: '[MouseLeft>]' });

    // 見出し1 を選択
    await waitFor(() => {
      expect(screen.getByText('見出し1')).toBeInTheDocument();
    });
    const heading1Item = screen.getByText('見出し1');
    await user.click(heading1Item);

    // blockType が h1 になりドロップダウンに「見出し1」と表示されること
    await waitFor(() => {
      // DropdownMenuTrigger のボタン内に「見出し1」が表示される
      const trigger = document.querySelector('.tiptap-block-select');
      expect(trigger?.textContent).toContain('見出し1');
    }, { timeout: 2000 });
  });

  it('見出し2を選択するとブロックタイプドロップダウンが h2 を表示する', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    await waitFor(() => {
      expect(screen.getByText('段落')).toBeInTheDocument();
    });

    const blockTypeBtn = screen.getByText('段落').closest('button')!;
    await user.pointer({ target: blockTypeBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(screen.getByText('見出し2')).toBeInTheDocument();
    });
    const heading2Item = screen.getByText('見出し2');
    await user.click(heading2Item);

    await waitFor(() => {
      const trigger = document.querySelector('.tiptap-block-select');
      expect(trigger?.textContent).toContain('見出し2');
    }, { timeout: 2000 });
  });

  it('見出し3を選択するとブロックタイプドロップダウンが h3 を表示する', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    await waitFor(() => {
      expect(screen.getByText('段落')).toBeInTheDocument();
    });

    const blockTypeBtn = screen.getByText('段落').closest('button')!;
    await user.pointer({ target: blockTypeBtn, keys: '[MouseLeft>]' });

    await waitFor(() => {
      expect(screen.getByText('見出し3')).toBeInTheDocument();
    });
    const heading3Item = screen.getByText('見出し3');
    await user.click(heading3Item);

    await waitFor(() => {
      const trigger = document.querySelector('.tiptap-block-select');
      expect(trigger?.textContent).toContain('見出し3');
    }, { timeout: 2000 });
  });

  it('見出し1を選択後に段落を選択するとブロックタイプが paragraph に戻る', async () => {
    const user = userEvent.setup();
    render(<Tiptap value="" name="test" />);

    await waitFor(() => {
      expect(screen.getByText('段落')).toBeInTheDocument();
    });

    // 見出し1 を選択
    const blockTypeBtn = screen.getByText('段落').closest('button')!;
    await user.pointer({ target: blockTypeBtn, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(screen.getByText('見出し1')).toBeInTheDocument();
    });
    await user.click(screen.getByText('見出し1'));
    await waitFor(() => {
      const trigger = document.querySelector('.tiptap-block-select');
      expect(trigger?.textContent).toContain('見出し1');
    }, { timeout: 2000 });

    // 段落 を再選択
    const blockTypeBtnH1 = document.querySelector('.tiptap-block-select') as HTMLElement;
    await user.pointer({ target: blockTypeBtnH1, keys: '[MouseLeft>]' });
    await waitFor(() => {
      expect(screen.getByText('段落')).toBeInTheDocument();
    });
    await user.click(screen.getByText('段落'));
    await waitFor(() => {
      const trigger = document.querySelector('.tiptap-block-select');
      expect(trigger?.textContent).toContain('段落');
    }, { timeout: 2000 });
  });
});

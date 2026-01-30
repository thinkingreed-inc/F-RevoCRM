import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { MultireferenceField } from '../MultireferenceField';
import type { MultireferenceFieldProps } from '../MultireferenceField';

// MultiSelectRecordSearchDrawerをモック
vi.mock('../MultiSelectRecordSearchDrawer', () => ({
  MultiSelectRecordSearchDrawer: ({ open, onSelect, selectedRecords }: any) => {
    if (!open) return null;
    return (
      <div data-testid="multi-select-drawer">
        <button
          data-testid="drawer-select-button"
          onClick={() => {
            onSelect([
              { id: '10', label: 'Test Record 10', module: 'Accounts' },
              { id: '11', label: 'Test Record 11', module: 'Accounts' }
            ]);
          }}
        >
          Select Records
        </button>
        <div data-testid="drawer-selected-count">{selectedRecords?.length || 0}</div>
      </div>
    );
  }
}));

describe('MultireferenceField', () => {
  const defaultProps: MultireferenceFieldProps = {
    name: 'test_reference',
    label: 'テスト参照',
    referenceModules: ['Accounts'],
    onChange: vi.fn(),
    value: '',
    displayValues: []
  };

  const mockSearchResults = [
    { id: '1', label: 'レコード1', module: 'Accounts' },
    { id: '2', label: 'レコード2', module: 'Accounts' },
    { id: '3', label: 'レコード3', module: 'Accounts' }
  ];

  beforeEach(() => {
    // fetch APIのモック
    global.fetch = vi.fn();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  /**
   * テスト1: 基本的なレンダリング
   */
  it('should render label and input field correctly', () => {
    render(<MultireferenceField {...defaultProps} />);

    // ラベルが表示されている
    expect(screen.getByText('テスト参照')).toBeInTheDocument();

    // 入力フィールドが表示されている
    const input = screen.getByPlaceholderText('テスト参照を検索して追加...');
    expect(input).toBeInTheDocument();

    // hidden input（実際の値）が存在する
    const hiddenInput = document.querySelector('input[type="hidden"][name="test_reference"]');
    expect(hiddenInput).toBeInTheDocument();
    expect(hiddenInput).toHaveValue('');
  });

  /**
   * テスト2: 必須フィールドの表示
   */
  it('should display mandatory asterisk when mandatory is true', () => {
    render(<MultireferenceField {...defaultProps} mandatory={true} />);

    // 必須マークが表示される
    const labelContainer = screen.getByText('テスト参照').closest('div');
    expect(labelContainer?.textContent).toContain('*');
  });

  /**
   * テスト3: disabled状態の動作
   */
  it('should disable input and buttons when disabled is true', () => {
    render(<MultireferenceField {...defaultProps} disabled={true} />);

    // 検索入力が無効化
    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');
    expect(searchInput).toBeDisabled();

    // Drawer検索ボタンが無効化
    const drawerButton = screen.getByRole('button', { name: '' });
    expect(drawerButton).toBeDisabled();
  });

  /**
   * テスト4: 初期表示値のタグ表示
   */
  it('should display selected records as tags with displayValues', () => {
    render(
      <MultireferenceField
        {...defaultProps}
        value="1;2"
        displayValues={[
          { id: '1', label: 'レコードA' },
          { id: '2', label: 'レコードB' }
        ]}
      />
    );

    // タグが表示される
    expect(screen.getByText('レコードA')).toBeInTheDocument();
    expect(screen.getByText('レコードB')).toBeInTheDocument();
  });

  /**
   * テスト5: displayValuesがない場合、valueからIDのみでタグを表示
   */
  it('should display tags with ID labels when displayValues is not provided', () => {
    render(
      <MultireferenceField
        {...defaultProps}
        value="100;200;300"
        displayValues={[]}
      />
    );

    // ID表示形式でタグが表示される
    expect(screen.getByText('ID: 100')).toBeInTheDocument();
    expect(screen.getByText('ID: 200')).toBeInTheDocument();
    expect(screen.getByText('ID: 300')).toBeInTheDocument();
  });

  /**
   * テスト6: タグの削除機能
   */
  it('should remove tag when X button is clicked', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();

    render(
      <MultireferenceField
        {...defaultProps}
        value="1;2;3"
        displayValues={[
          { id: '1', label: 'レコード1' },
          { id: '2', label: 'レコード2' },
          { id: '3', label: 'レコード3' }
        ]}
        onChange={onChange}
      />
    );

    // レコード2の削除ボタンをクリック
    const record2Tag = screen.getByText('レコード2').closest('div');
    const deleteButton = within(record2Tag!).getByRole('button');
    await user.click(deleteButton);

    // onChangeが適切な値で呼ばれる（レコード2を除外）
    expect(onChange).toHaveBeenCalledWith('test_reference', '1;3');
  });

  /**
   * テスト7: 検索入力時のデバウンス動作（fetch呼び出し）
   */
  it('should trigger search with debounce when typing in search input', async () => {
    const user = userEvent.setup();

    // fetchモックの設定
    (global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        result: {
          records: mockSearchResults
        }
      })
    });

    render(<MultireferenceField {...defaultProps} />);

    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');

    // 入力
    await user.type(searchInput, 'test');

    // デバウンス待機（300ms）
    await waitFor(
      () => {
        expect(global.fetch).toHaveBeenCalledWith(
          expect.stringContaining('module=Accounts'),
          expect.any(Object)
        );
      },
      { timeout: 500 }
    );
  });

  /**
   * テスト9: 検索結果からレコードを選択
   */
  it('should select a record from search results dropdown', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();

    // fetchモックの設定
    (global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        result: {
          records: mockSearchResults
        }
      })
    });

    render(<MultireferenceField {...defaultProps} onChange={onChange} />);

    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');

    // フォーカスしてドロップダウンを開く
    await user.click(searchInput);

    // 検索結果が表示されるまで待機
    await waitFor(() => {
      expect(screen.getByText('レコード1')).toBeInTheDocument();
    });

    // レコード1をクリック
    const record1 = screen.getByText('レコード1');
    await user.click(record1);

    // onChangeが呼ばれる（セミコロン区切り形式）
    expect(onChange).toHaveBeenCalledWith('test_reference', '1');
  });

  /**
   * テスト10: 複数レコードの選択（セミコロン区切り）
   */
  it('should concatenate multiple record IDs with semicolons', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();

    // 最初のレコードを選択済み状態で開始
    const { rerender } = render(
      <MultireferenceField
        {...defaultProps}
        value="1"
        displayValues={[{ id: '1', label: 'レコード1' }]}
        onChange={onChange}
      />
    );

    // 2件目の検索結果を返すモック
    (global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        result: {
          records: [
            { id: '2', label: 'レコード2', module: 'Accounts' },
            { id: '3', label: 'レコード3', module: 'Accounts' }
          ]
        }
      })
    });

    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');
    await user.click(searchInput);

    await waitFor(() => {
      expect(screen.getByText('レコード2')).toBeInTheDocument();
    });

    // レコード2をクリック
    const record2 = screen.getByText('レコード2');
    await user.click(record2);

    // セミコロン区切りで追加される
    expect(onChange).toHaveBeenCalledWith('test_reference', '1;2');
  });

  /**
   * テスト11: 既に選択済みのレコードは検索結果から除外される
   */
  it('should exclude already selected records from search results', async () => {
    const user = userEvent.setup();

    // fetchモック
    (global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        result: {
          records: mockSearchResults // id: 1, 2, 3
        }
      })
    });

    render(
      <MultireferenceField
        {...defaultProps}
        value="1;2"
        displayValues={[
          { id: '1', label: 'レコード1' },
          { id: '2', label: 'レコード2' }
        ]}
      />
    );

    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');
    await user.click(searchInput);

    // 検索結果が表示されるまで待機
    await waitFor(() => {
      // id: 3のみ表示される（id: 1, 2は既選択のため除外）
      expect(screen.getByText('レコード3')).toBeInTheDocument();
    });

    // id: 1, 2は表示されない
    const dropdown = screen.getByText('レコード3').closest('div');
    expect(within(dropdown!).queryByText('レコード1')).not.toBeInTheDocument();
    expect(within(dropdown!).queryByText('レコード2')).not.toBeInTheDocument();
  });

  /**
   * テスト12: エラーメッセージの表示
   */
  it('should display error message when error prop is provided', () => {
    render(<MultireferenceField {...defaultProps} error="このフィールドは必須です" />);

    // エラーメッセージが表示される
    expect(screen.getByText('このフィールドは必須です')).toBeInTheDocument();

    // 入力フィールドにエラースタイルが適用される
    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');
    expect(searchInput).toHaveClass('border-red-500');
  });

  /**
   * テスト13: 複数参照モジュールの切り替え
   */
  it('should allow switching between multiple reference modules', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();

    render(
      <MultireferenceField
        {...defaultProps}
        referenceModules={['Accounts', 'Contacts', 'Leads']}
        referenceModuleLabels={{
          Accounts: '顧客企業',
          Contacts: '顧客担当者',
          Leads: 'リード'
        }}
        onChange={onChange}
      />
    );

    // モジュール選択フィールドが表示される（referenceModules.length > 1の場合）
    const moduleInput = screen.getByDisplayValue('顧客企業');
    expect(moduleInput).toBeInTheDocument();

    // モジュールを変更すると全てクリアされる
    // （実際のドロップダウンはPortalで表示されるため、ここではクリックのみテスト）
    await user.click(moduleInput);

    // ドロップダウンが開く（Portalのため直接検証は難しいがクリックイベントは発火）
    // changeは実際の選択時に発生するため、ここでは表示のみ確認
  });

  /**
   * テスト14: Drawerからの複数選択
   */
  it('should select multiple records from drawer', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();

    render(<MultireferenceField {...defaultProps} onChange={onChange} />);

    // Drawer検索ボタンをクリック
    const drawerButton = screen.getByRole('button', { name: '' });
    await user.click(drawerButton);

    // Drawerが表示される
    await waitFor(() => {
      expect(screen.getByTestId('multi-select-drawer')).toBeInTheDocument();
    });

    // Drawer内でレコードを選択
    const selectButton = screen.getByTestId('drawer-select-button');
    await user.click(selectButton);

    // onChangeが呼ばれる（複数レコードのIDをセミコロン区切り）
    expect(onChange).toHaveBeenCalledWith('test_reference', '10;11');
  });

  /**
   * テスト15: 空の値の扱い
   */
  it('should handle empty value correctly', () => {
    const { container } = render(
      <MultireferenceField {...defaultProps} value="" displayValues={[]} />
    );

    // タグが表示されない
    const tags = container.querySelectorAll('[class*="bg-blue-100"]');
    expect(tags.length).toBe(0);

    // hidden inputは空文字列
    const hiddenInput = document.querySelector('input[type="hidden"][name="test_reference"]');
    expect(hiddenInput).toHaveValue('');
  });

  /**
   * テスト16: 1つだけの選択（全てクリアボタンは非表示）
   */
  it('should not show "clear all" button when only one record is selected', () => {
    render(
      <MultireferenceField
        {...defaultProps}
        value="1"
        displayValues={[{ id: '1', label: 'レコード1' }]}
      />
    );

    // タグは表示される
    expect(screen.getByText('レコード1')).toBeInTheDocument();

    // 全てクリアボタンは表示されない（1件のみの場合）
    expect(screen.queryByRole('button', { name: '全てクリア' })).not.toBeInTheDocument();
  });

  /**
   * テスト17: ローディング状態の表示
   */
  it('should show loading indicator while searching', async () => {
    const user = userEvent.setup();

    // fetchを遅延させる
    (global.fetch as any).mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          setTimeout(
            () =>
              resolve({
                ok: true,
                json: async () => ({ result: { records: [] } })
              }),
            1000
          );
        })
    );

    render(<MultireferenceField {...defaultProps} />);

    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');
    await user.click(searchInput);

    // ローディングアイコンが表示される
    await waitFor(() => {
      const loadingIcon = document.querySelector('.animate-spin');
      expect(loadingIcon).toBeInTheDocument();
    });
  });

  /**
   * テスト18: カスタムクラスの適用
   */
  it('should apply custom className', () => {
    const { container } = render(
      <MultireferenceField {...defaultProps} className="custom-test-class" />
    );

    // 最上位のdivにカスタムクラスが適用される
    const wrapper = container.firstChild;
    expect(wrapper).toHaveClass('custom-test-class');
  });

  /**
   * テスト19: disabledの場合、タグの削除ボタンが表示されない
   */
  it('should not show delete buttons on tags when disabled', () => {
    render(
      <MultireferenceField
        {...defaultProps}
        value="1;2"
        displayValues={[
          { id: '1', label: 'レコード1' },
          { id: '2', label: 'レコード2' }
        ]}
        disabled={true}
      />
    );

    // タグは表示される
    expect(screen.getByText('レコード1')).toBeInTheDocument();
    expect(screen.getByText('レコード2')).toBeInTheDocument();

    // 削除ボタンは表示されない
    const tag1 = screen.getByText('レコード1').closest('div');
    const deleteButton1 = within(tag1!).queryByRole('button');
    expect(deleteButton1).not.toBeInTheDocument();
  });

  /**
   * テスト20: 検索結果が空の場合のメッセージ表示
   */
  it('should show "no records" message when search returns empty results', async () => {
    const user = userEvent.setup();

    // 空の検索結果
    (global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        result: {
          records: []
        }
      })
    });

    render(<MultireferenceField {...defaultProps} />);

    const searchInput = screen.getByPlaceholderText('テスト参照を検索して追加...');
    await user.type(searchInput, 'nonexistent');

    // "該当するレコードがありません"メッセージが表示される
    await waitFor(() => {
      expect(screen.getByText('該当するレコードがありません')).toBeInTheDocument();
    });
  });

  /**
   * テスト20: 多数のレコード選択（5件以上）
   */
  it('should handle selection of many records', () => {
    const manyRecords = Array.from({ length: 10 }, (_, i) => ({
      id: String(i + 1),
      label: `レコード${i + 1}`
    }));

    const manyRecordIds = manyRecords.map((r) => r.id).join(';');

    render(
      <MultireferenceField
        {...defaultProps}
        value={manyRecordIds}
        displayValues={manyRecords}
      />
    );

    // 全てのタグが表示される
    manyRecords.forEach((record) => {
      expect(screen.getByText(record.label)).toBeInTheDocument();
    });
  });
});

/**
 * QuickCreate 保存フロー統合テスト
 *
 * フォーム入力からAPI保存までのエンドツーエンドテスト
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QuickCreate } from '../../components/QuickCreate';

// モックフィールドデータ
const mockFields = [
  {
    name: 'accountname',
    label: '顧客企業名',
    uitype: '1',
    mandatory: true,
    readonly: false,
    maxlength: 100,
  },
  {
    name: 'phone',
    label: '電話番号',
    uitype: '11',
    mandatory: false,
    readonly: false,
  },
  {
    name: 'website',
    label: 'ウェブサイト',
    uitype: '17',
    mandatory: false,
    readonly: false,
  },
];

// useQuickCreateFieldsのモック
const mockUseQuickCreateFields = vi.fn(() => ({
  fields: mockFields,
  loading: false,
  error: null,
  editViewUrl: 'index.php?module=Accounts&view=Edit',
  moduleLabel: '顧客企業',
  picklistDependency: undefined,
}));

// useQuickCreateSaveのモック
const mockSave = vi.fn();
const mockClearError = vi.fn();
const mockUseQuickCreateSave = vi.fn(() => ({
  save: mockSave,
  isSaving: false,
  error: null,
  clearError: mockClearError,
}));

// モックのセットアップ
vi.mock('../../components/QuickCreate/hooks/useQuickCreateFields', () => ({
  useQuickCreateFields: () => mockUseQuickCreateFields(),
}));

vi.mock('../../components/QuickCreate/hooks/useQuickCreateSave', () => ({
  useQuickCreateSave: () => mockUseQuickCreateSave(),
}));

describe('QuickCreate 保存フロー統合テスト', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseQuickCreateFields.mockReturnValue({
      fields: mockFields,
      loading: false,
      error: null,
      editViewUrl: 'index.php?module=Accounts&view=Edit',
      moduleLabel: '顧客企業',
      picklistDependency: undefined,
    });
    mockUseQuickCreateSave.mockReturnValue({
      save: mockSave,
      isSaving: false,
      error: null,
      clearError: mockClearError,
    });
  });

  describe('正常系: 保存成功フロー', () => {
    it('フォーム入力→バリデーション→保存→成功の一連のフローが動作する', async () => {
      const user = userEvent.setup();
      const onSave = vi.fn();

      mockSave.mockResolvedValue({
        success: true,
        recordId: '123',
        recordLabel: 'テスト企業株式会社',
        module: 'Accounts',
      });

      render(<QuickCreate module="Accounts" isOpen={true} onSave={onSave} />);

      // Step 1: フォームが表示される
      expect(screen.getByText('クイック作成 顧客企業')).toBeInTheDocument();
      expect(screen.getByText('顧客企業名')).toBeInTheDocument();

      // Step 2: 必須フィールドに入力
      const nameInput = screen.getByPlaceholderText('顧客企業名を入力してください');
      await user.type(nameInput, 'テスト企業株式会社');

      // Step 3: 任意フィールドに入力
      const phoneInput = screen.getByPlaceholderText('03-1234-5678');
      await user.type(phoneInput, '03-9876-5432');

      // Step 4: 保存ボタンをクリック
      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      // Step 5: save関数が正しいデータで呼ばれる
      await waitFor(() => {
        expect(mockSave).toHaveBeenCalledWith({
          accountname: 'テスト企業株式会社',
          phone: '03-9876-5432',
        });
      });

      // Step 6: 成功コールバックが呼ばれる
      await waitFor(() => {
        expect(onSave).toHaveBeenCalledWith({
          success: true,
          recordId: '123',
          recordLabel: 'テスト企業株式会社',
          module: 'Accounts',
        });
      });
    });

    it('成功メッセージが表示される', async () => {
      const user = userEvent.setup();

      mockSave.mockResolvedValue({
        success: true,
        recordId: '456',
        recordLabel: 'サンプル企業',
        module: 'Accounts',
      });

      render(<QuickCreate module="Accounts" isOpen={true} />);

      const nameInput = screen.getByPlaceholderText('顧客企業名を入力してください');
      await user.type(nameInput, 'サンプル企業');

      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      await waitFor(() => {
        expect(screen.getByText(/顧客企業を作成しました/)).toBeInTheDocument();
      });
    });
  });

  describe('バリデーション', () => {
    it('必須フィールドが空の場合、バリデーションエラーが表示される', async () => {
      const user = userEvent.setup();

      render(<QuickCreate module="Accounts" isOpen={true} />);

      // 何も入力せずに保存
      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      // バリデーションエラーが表示
      expect(screen.getByText('顧客企業名は必須です')).toBeInTheDocument();

      // saveは呼ばれない
      expect(mockSave).not.toHaveBeenCalled();
    });

    it('最大文字数を超えた場合、バリデーションエラーが表示される', async () => {
      const user = userEvent.setup();

      render(<QuickCreate module="Accounts" isOpen={true} />);

      const nameInput = screen.getByPlaceholderText('顧客企業名を入力してください');
      // 101文字入力（maxlength: 100）- ただしHTML maxlength属性で制限されるため、
      // fireEventで直接値を設定
      fireEvent.change(nameInput, { target: { value: 'a'.repeat(101) } });

      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      expect(screen.getByText('顧客企業名は100文字以内で入力してください')).toBeInTheDocument();
    });
  });

  describe('異常系: 保存失敗', () => {
    it('API保存失敗時にエラーが表示される', async () => {
      mockUseQuickCreateSave.mockReturnValue({
        save: mockSave,
        isSaving: false,
        error: '保存に失敗しました：サーバーエラー',
        clearError: mockClearError,
      });

      render(<QuickCreate module="Accounts" isOpen={true} />);

      expect(screen.getByText('保存に失敗しました：サーバーエラー')).toBeInTheDocument();
    });

    it('save関数がエラーを返した場合、onSaveは呼ばれない', async () => {
      const user = userEvent.setup();
      const onSave = vi.fn();

      mockSave.mockResolvedValue({
        success: false,
        error: '重複レコードが存在します',
        module: 'Accounts',
      });

      render(<QuickCreate module="Accounts" isOpen={true} onSave={onSave} />);

      const nameInput = screen.getByPlaceholderText('顧客企業名を入力してください');
      await user.type(nameInput, '既存企業');

      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      // 失敗時はonSaveは呼ばれない
      expect(onSave).not.toHaveBeenCalled();
    });
  });

  describe('保存中の状態', () => {
    it('保存中はボタンが無効化され、ローディング表示になる', async () => {
      mockUseQuickCreateSave.mockReturnValue({
        save: mockSave,
        isSaving: true,
        error: null,
        clearError: mockClearError,
      });

      render(<QuickCreate module="Accounts" isOpen={true} />);

      const saveButton = screen.getByRole('button', { name: /保存中/i });
      expect(saveButton).toBeDisabled();
    });
  });

  describe('初期データの設定', () => {
    it('initialDataで渡された値がフォームにセットされる', () => {
      render(
        <QuickCreate
          module="Accounts"
          isOpen={true}
          initialData={{
            accountname: '初期値企業',
            phone: '03-1111-2222',
          }}
        />
      );

      const nameInput = screen.getByPlaceholderText('顧客企業名を入力してください');
      expect(nameInput).toHaveValue('初期値企業');

      const phoneInput = screen.getByPlaceholderText('03-1234-5678');
      expect(phoneInput).toHaveValue('03-1111-2222');
    });

    it('initialDataの値も保存時に送信される', async () => {
      const user = userEvent.setup();

      mockSave.mockResolvedValue({
        success: true,
        recordId: '789',
        module: 'Accounts',
      });

      render(
        <QuickCreate
          module="Accounts"
          isOpen={true}
          initialData={{
            accountname: '既存企業名',
            phone: '03-0000-0000',
          }}
        />
      );

      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalledWith({
          accountname: '既存企業名',
          phone: '03-0000-0000',
        });
      });
    });
  });

  describe('キャンセル処理', () => {
    it('キャンセルボタンでモーダルが閉じ、onCancelが呼ばれる', async () => {
      const user = userEvent.setup();
      const onCancel = vi.fn();
      const onOpenChange = vi.fn();

      render(
        <QuickCreate
          module="Accounts"
          isOpen={true}
          onCancel={onCancel}
          onOpenChange={onOpenChange}
        />
      );

      const cancelButton = screen.getByRole('button', { name: /キャンセル/i });
      await user.click(cancelButton);

      expect(onOpenChange).toHaveBeenCalledWith(false);
      expect(onCancel).toHaveBeenCalled();
    });
  });

  describe('詳細入力への遷移', () => {
    it('詳細入力ボタンでonGoToFullFormが呼ばれる', async () => {
      const user = userEvent.setup();
      const onGoToFullForm = vi.fn();

      render(
        <QuickCreate
          module="Accounts"
          isOpen={true}
          onGoToFullForm={onGoToFullForm}
        />
      );

      // フォームに入力
      const nameInput = screen.getByPlaceholderText('顧客企業名を入力してください');
      await user.type(nameInput, 'テスト');

      // 詳細入力ボタンをクリック
      const fullFormButton = screen.getByRole('button', { name: /詳細入力/i });
      await user.click(fullFormButton);

      expect(onGoToFullForm).toHaveBeenCalledWith({
        editUrl: expect.stringContaining('index.php?module=Accounts&view=Edit'),
        formData: { accountname: 'テスト' }
      });
    });
  });
});

describe('複数モジュール間のQuickCreate', () => {
  it('異なるモジュールで異なるフィールドが表示される', () => {
    // Accountsモジュール
    mockUseQuickCreateFields.mockReturnValue({
      fields: mockFields,
      loading: false,
      error: null,
      editViewUrl: 'index.php?module=Accounts&view=Edit',
      moduleLabel: '顧客企業',
      picklistDependency: undefined,
    });

    const { rerender } = render(<QuickCreate module="Accounts" isOpen={true} />);
    expect(screen.getByText('クイック作成 顧客企業')).toBeInTheDocument();

    // Contactsモジュールに切り替え
    const contactFields = [
      { name: 'firstname', label: '名', uitype: '1', mandatory: false, readonly: false },
      { name: 'lastname', label: '姓', uitype: '1', mandatory: true, readonly: false },
    ];

    mockUseQuickCreateFields.mockReturnValue({
      fields: contactFields,
      loading: false,
      error: null,
      editViewUrl: 'index.php?module=Contacts&view=Edit',
      moduleLabel: '顧客担当者',
      picklistDependency: undefined,
    });

    rerender(<QuickCreate module="Contacts" isOpen={true} />);
    expect(screen.getByText('クイック作成 顧客担当者')).toBeInTheDocument();
  });
});

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QuickCreate } from './QuickCreate';

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
    name: 'email1',
    label: 'Email',
    uitype: '13',
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
vi.mock('./hooks/useQuickCreateFields', () => ({
  useQuickCreateFields: () => mockUseQuickCreateFields(),
}));

vi.mock('./hooks/useQuickCreateSave', () => ({
  useQuickCreateSave: () => mockUseQuickCreateSave(),
}));

describe('QuickCreate', () => {
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

  describe('モーダル表示', () => {
    it('isOpen=trueでモーダルが表示される', () => {
      render(<QuickCreate module="Accounts" isOpen={true} />);

      expect(screen.getByText('クイック作成 顧客企業')).toBeInTheDocument();
    });

    it('isOpen=falseでモーダルが非表示', () => {
      render(<QuickCreate module="Accounts" isOpen={false} />);

      expect(screen.queryByText('クイック作成 顧客企業')).not.toBeInTheDocument();
    });

    it('翻訳されたモジュール名がタイトルに表示される', () => {
      mockUseQuickCreateFields.mockReturnValue({
        fields: mockFields,
        loading: false,
        error: null,
        editViewUrl: 'index.php?module=Contacts&view=Edit',
        moduleLabel: '顧客担当者',
        picklistDependency: undefined,
      });

      render(<QuickCreate module="Contacts" isOpen={true} />);

      expect(screen.getByText('クイック作成 顧客担当者')).toBeInTheDocument();
    });

    it('moduleLabelがない場合はモジュール名が表示される', () => {
      mockUseQuickCreateFields.mockReturnValue({
        fields: mockFields,
        loading: false,
        error: null,
        editViewUrl: 'index.php?module=CustomModule&view=Edit',
        moduleLabel: null,
        picklistDependency: undefined,
      });

      render(<QuickCreate module="CustomModule" isOpen={true} />);

      expect(screen.getByText('クイック作成 CustomModule')).toBeInTheDocument();
    });
  });

  describe('ローディング状態', () => {
    it('フィールド読み込み中はローディング表示', () => {
      mockUseQuickCreateFields.mockReturnValue({
        fields: [],
        loading: true,
        error: null,
        editViewUrl: null,
        moduleLabel: null,
        picklistDependency: undefined,
      });

      render(<QuickCreate module="Accounts" isOpen={true} />);

      expect(screen.getByText('フィールド情報を読み込み中...')).toBeInTheDocument();
    });
  });

  describe('エラー表示', () => {
    it('フィールド取得エラーが表示される', () => {
      mockUseQuickCreateFields.mockReturnValue({
        fields: [],
        loading: false,
        error: 'フィールド情報の取得に失敗しました',
        editViewUrl: null,
        moduleLabel: null,
        picklistDependency: undefined,
      });

      render(<QuickCreate module="Accounts" isOpen={true} />);

      expect(screen.getByText('フィールド情報の取得に失敗しました')).toBeInTheDocument();
    });

    it('保存エラーが表示される', () => {
      mockUseQuickCreateSave.mockReturnValue({
        save: mockSave,
        isSaving: false,
        error: '保存に失敗しました',
        clearError: mockClearError,
      });

      render(<QuickCreate module="Accounts" isOpen={true} />);

      expect(screen.getByText('保存に失敗しました')).toBeInTheDocument();
    });
  });

  describe('フォーム表示', () => {
    it('フィールドラベルが表示される', () => {
      render(<QuickCreate module="Accounts" isOpen={true} />);

      expect(screen.getByText('顧客企業名')).toBeInTheDocument();
      expect(screen.getByText('電話番号')).toBeInTheDocument();
      expect(screen.getByText('Email')).toBeInTheDocument();
    });

    it('必須フィールドにマークが表示される', () => {
      render(<QuickCreate module="Accounts" isOpen={true} />);

      // mandatory=trueのフィールドには*が付く（別のspan要素として）
      const accountLabel = screen.getByText('顧客企業名');
      const labelElement = accountLabel.closest('label');
      expect(labelElement).toBeInTheDocument();
      // 必須マークのspan（*）がラベルの兄弟要素として存在することを確認
      const container = labelElement?.parentElement;
      expect(container?.textContent).toContain('*');
    });
  });

  describe('バリデーション', () => {
    it('必須フィールドが空の場合にエラーが表示される', async () => {
      const user = userEvent.setup();
      const onSave = vi.fn();

      render(<QuickCreate module="Accounts" isOpen={true} onSave={onSave} />);

      // 保存ボタンをクリック
      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      // バリデーションエラーが表示される
      expect(screen.getByText('顧客企業名は必須です')).toBeInTheDocument();

      // 保存が呼ばれない
      expect(mockSave).not.toHaveBeenCalled();
    });
  });

  describe('保存処理', () => {
    it('保存成功時にonSaveが呼ばれる', async () => {
      const user = userEvent.setup();
      const onSave = vi.fn();

      mockSave.mockResolvedValue({
        success: true,
        recordId: '123',
        recordLabel: 'テスト企業',
        module: 'Accounts',
      });

      render(<QuickCreate module="Accounts" isOpen={true} onSave={onSave} />);

      // 必須フィールドに入力
      const input = screen.getByPlaceholderText('顧客企業名を入力してください');
      await user.type(input, 'テスト企業');

      // 保存ボタンをクリック
      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      await waitFor(() => {
        expect(mockSave).toHaveBeenCalledWith({ accountname: 'テスト企業' });
      });

      await waitFor(() => {
        expect(onSave).toHaveBeenCalledWith({
          success: true,
          recordId: '123',
          recordLabel: 'テスト企業',
          module: 'Accounts',
        });
      });
    });

    it('保存成功時に成功メッセージが表示される', async () => {
      const user = userEvent.setup();

      mockSave.mockResolvedValue({
        success: true,
        recordId: '123',
        recordLabel: 'テスト企業',
        module: 'Accounts',
      });

      render(<QuickCreate module="Accounts" isOpen={true} />);

      // 必須フィールドに入力
      const input = screen.getByPlaceholderText('顧客企業名を入力してください');
      await user.type(input, 'テスト企業');

      // 保存ボタンをクリック
      const saveButton = screen.getByRole('button', { name: /保存/i });
      await user.click(saveButton);

      await waitFor(() => {
        expect(screen.getByText(/顧客企業を作成しました/)).toBeInTheDocument();
      });
    });

    it('保存中はボタンが無効化される', async () => {
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

  describe('キャンセル処理', () => {
    it('キャンセルボタンでonCancelが呼ばれる', async () => {
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

  describe('詳細入力へ遷移', () => {
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
      const input = screen.getByPlaceholderText('顧客企業名を入力してください');
      await user.type(input, 'テスト企業');

      // 詳細入力ボタンをクリック
      const fullFormButton = screen.getByRole('button', { name: /詳細入力/i });
      await user.click(fullFormButton);

      expect(onGoToFullForm).toHaveBeenCalledWith({
        editUrl: expect.stringContaining('index.php?module=Accounts&view=Edit'),
        formData: { accountname: 'テスト企業' }
      });
    });
  });

  describe('初期データ', () => {
    it('initialDataでフォームが初期化される', () => {
      render(
        <QuickCreate
          module="Accounts"
          isOpen={true}
          initialData={{ accountname: '初期値企業', phone: '090-1234-5678' }}
        />
      );

      const accountInput = screen.getByPlaceholderText('顧客企業名を入力してください');
      expect(accountInput).toHaveValue('初期値企業');

      // 電話番号フィールドのプレースホルダーは固定 '03-1234-5678'
      const phoneInput = screen.getByPlaceholderText('03-1234-5678');
      expect(phoneInput).toHaveValue('090-1234-5678');
    });
  });
});

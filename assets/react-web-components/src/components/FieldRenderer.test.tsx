import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { FieldRenderer, isUITypeSupported } from './FieldRenderer';
import { UI_TYPES, FieldDefinition } from '../types/field';

// テスト用のフィールド定義を作成するヘルパー
const createField = (overrides: Partial<FieldDefinition> = {}): FieldDefinition => ({
  name: 'test_field',
  label: 'Test Field',
  uitype: UI_TYPES.STRING,
  mandatory: false,
  readonly: false,
  ...overrides,
});

describe('FieldRenderer', () => {
  describe('文字列フィールド (UIType 1, 2, 106)', () => {
    it('文字列入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.STRING, label: 'テスト文字列' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText('テスト文字列')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('テスト文字列を入力してください')).toBeInTheDocument();
    });

    it('入力値の変更が正しく伝播される', async () => {
      const field = createField({ uitype: UI_TYPES.STRING });
      const onChange = vi.fn();
      const user = userEvent.setup();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = screen.getByRole('textbox');
      await user.type(input, 'テスト');

      expect(onChange).toHaveBeenCalled();
    });

    it('必須フィールドにアスタリスクが表示される', () => {
      const field = createField({ uitype: UI_TYPES.STRING, mandatory: true });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const label = screen.getByText('Test Field');
      // ラベルはspan要素として描画される
      const labelElement = label.closest('span');
      expect(labelElement).toBeInTheDocument();
      // 必須マークのspan（*）が兄弟要素として存在することを確認
      const container = labelElement?.parentElement;
      expect(container?.textContent).toContain('*');
    });

    it('読み取り専用フィールドが無効化される', () => {
      const field = createField({ uitype: UI_TYPES.STRING, readonly: true });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="test" onChange={onChange} />);

      expect(screen.getByRole('textbox')).toBeDisabled();
    });
  });

  describe('テキストエリア (UIType 19, 20, 21)', () => {
    it('テキストエリアが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.TEXTAREA, label: 'Description' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByRole('textbox')).toBeInTheDocument();
    });
  });

  describe('数値フィールド (UIType 7)', () => {
    it('数値入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.NUMBER, label: '数量' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = screen.getByRole('spinbutton');
      expect(input).toHaveAttribute('type', 'number');
    });
  });

  describe('小数フィールド (UIType 9)', () => {
    it('小数入力フィールドにstep属性がある', () => {
      const field = createField({ uitype: UI_TYPES.DECIMAL, label: '金額' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = screen.getByRole('spinbutton');
      expect(input).toHaveAttribute('step', '0.01');
    });
  });

  describe('真偽値フィールド (UIType 56)', () => {
    it('チェックボックスが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.BOOLEAN, label: '有効' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByRole('checkbox')).toBeInTheDocument();
    });
  });

  describe('日付フィールド (UIType 5, 23)', () => {
    it('日付入力フィールドが正しく表示される (UIType 5)', () => {
      const field = createField({ uitype: UI_TYPES.DATE, label: '日付', name: 'test_date' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_test_date');
      expect(input).toHaveAttribute('type', 'date');
    });

    it('日付入力フィールドが正しく表示される (UIType 23)', () => {
      const field = createField({ uitype: UI_TYPES.DATE_23, label: '完了予定日', name: 'due_date' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_due_date');
      expect(input).toHaveAttribute('type', 'date');
    });
  });

  describe('日時フィールド (UIType 6)', () => {
    it('日時入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.DATETIME_CALENDAR, label: '日時', name: 'datetime_field' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_datetime_field');
      expect(input).toHaveAttribute('type', 'datetime-local');
    });
  });

  describe('時刻フィールド (UIType 14)', () => {
    it('時刻入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.TIME, label: '時刻', name: 'time_field' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_time_field');
      expect(input).toHaveAttribute('type', 'time');
    });
  });

  describe('Emailフィールド (UIType 13)', () => {
    it('Email入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.EMAIL, label: 'Email', name: 'email_field' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_email_field');
      expect(input).toHaveAttribute('type', 'email');
    });
  });

  describe('電話番号フィールド (UIType 11)', () => {
    it('電話番号入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.PHONE, label: '電話', name: 'phone_field' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_phone_field');
      expect(input).toHaveAttribute('type', 'tel');
    });
  });

  describe('URLフィールド (UIType 17)', () => {
    it('URL入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.URL, label: 'ウェブサイト', name: 'website' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_website');
      expect(input).toHaveAttribute('type', 'url');
    });
  });

  describe('通貨フィールド (UIType 71)', () => {
    it('通貨記号が表示される', () => {
      const field = createField({ uitype: UI_TYPES.CURRENCY, label: '金額' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText('¥')).toBeInTheDocument();
    });
  });

  describe('パーセンテージフィールド (UIType 72)', () => {
    it('パーセント記号が表示される', () => {
      const field = createField({ uitype: UI_TYPES.PERCENTAGE, label: '割合' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText('%')).toBeInTheDocument();
    });
  });

  describe('パスワードフィールド (UIType 99)', () => {
    it('パスワード入力フィールドが正しく表示される', () => {
      const field = createField({ uitype: UI_TYPES.PASSWORD, label: 'パスワード', name: 'password_field' });
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_password_field');
      expect(input).toHaveAttribute('type', 'password');
    });
  });

  describe('ピックリストフィールド (UIType 15, 16)', () => {
    it('ピックリストが正しく表示される', () => {
      const field = createField({
        uitype: UI_TYPES.PICKLIST,
        label: 'ステータス',
        picklistValues: [
          { value: 'active', label: 'アクティブ' },
          { value: 'inactive', label: '非アクティブ' },
        ],
      });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText('ステータス')).toBeInTheDocument();
    });
  });

  describe('エラー表示', () => {
    it('エラーメッセージが表示される', () => {
      const field = createField({ uitype: UI_TYPES.STRING });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} error="入力が必要です" />);

      expect(screen.getByText('入力が必要です')).toBeInTheDocument();
    });
  });

  describe('無効化状態', () => {
    it('disabledプロパティでフィールドが無効化される', () => {
      const field = createField({ uitype: UI_TYPES.STRING });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} disabled={true} />);

      expect(screen.getByRole('textbox')).toBeDisabled();
    });
  });

  describe('フィールド情報エラー', () => {
    it('フィールドがnullの場合エラー表示', () => {
      const onChange = vi.fn();

      render(<FieldRenderer field={null as any} value="" onChange={onChange} />);

      expect(screen.getByText(/エラー/)).toBeInTheDocument();
    });

    it('uitypeがない場合警告表示', () => {
      const field = createField({ uitype: '' });
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText(/警告/)).toBeInTheDocument();
    });
  });

  describe('isUITypeSupported', () => {
    it('サポートされているUITypeはtrueを返す', () => {
      expect(isUITypeSupported(UI_TYPES.STRING)).toBe(true);
      expect(isUITypeSupported(UI_TYPES.NUMBER)).toBe(true);
      expect(isUITypeSupported(UI_TYPES.BOOLEAN)).toBe(true);
      expect(isUITypeSupported(UI_TYPES.DATE)).toBe(true);
      expect(isUITypeSupported(UI_TYPES.PICKLIST)).toBe(true);
      expect(isUITypeSupported(UI_TYPES.REFERENCE)).toBe(true);
    });

    it('サポートされていないUITypeはfalseを返す', () => {
      expect(isUITypeSupported('999')).toBe(false);
      expect(isUITypeSupported('unknown')).toBe(false);
    });
  });
});

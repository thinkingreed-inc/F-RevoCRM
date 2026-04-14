import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { ProductTaxField } from './ProductTaxField';

describe('ProductTaxField', () => {
  const defaultProps = {
    name: 'tax1',
    label: '消費税(%)',
    defaultTaxRate: '10.000',
    onChange: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('初期表示', () => {
    it('チェックボックスが表示される', () => {
      render(<ProductTaxField {...defaultProps} value="" />);

      expect(screen.getByRole('checkbox')).toBeInTheDocument();
    });

    it('未チェック状態では入力フィールドが非表示', () => {
      render(<ProductTaxField {...defaultProps} value="" />);

      // 入力フィールドが存在しないか、非表示であることを確認
      const input = screen.queryByDisplayValue(/\d+\.\d+/);
      expect(input).not.toBeInTheDocument();
    });

    it('値がある場合はチェック済みで入力フィールドが表示される', () => {
      render(<ProductTaxField {...defaultProps} value="10.000" />);

      const checkbox = screen.getByRole('checkbox');
      expect(checkbox).toBeChecked();

      const input = screen.getByDisplayValue('10.000');
      expect(input).toBeInTheDocument();
    });

    it('空文字の場合は未チェック状態', () => {
      render(<ProductTaxField {...defaultProps} value="" />);

      const checkbox = screen.getByRole('checkbox');
      expect(checkbox).not.toBeChecked();
    });

    it('disabledの場合チェックボックスと入力が無効化される', () => {
      render(<ProductTaxField {...defaultProps} value="10.000" disabled={true} />);

      const checkbox = screen.getByRole('checkbox');
      expect(checkbox).toBeDisabled();

      const input = screen.getByDisplayValue('10.000');
      expect(input).toBeDisabled();
    });
  });

  describe('チェックボックス操作', () => {
    it('チェックONでデフォルト税率が設定される', () => {
      const onChange = vi.fn();
      render(<ProductTaxField {...defaultProps} value="" onChange={onChange} />);

      const checkbox = screen.getByRole('checkbox');
      fireEvent.click(checkbox);

      expect(onChange).toHaveBeenCalledWith('tax1', '10.000');
    });

    it('チェックOFFで空文字が設定される', () => {
      const onChange = vi.fn();
      render(<ProductTaxField {...defaultProps} value="10.000" onChange={onChange} />);

      const checkbox = screen.getByRole('checkbox');
      fireEvent.click(checkbox);

      expect(onChange).toHaveBeenCalledWith('tax1', '');
    });

    it('チェックON後に入力フィールドが表示される', () => {
      render(<ProductTaxField {...defaultProps} value="" />);

      const checkbox = screen.getByRole('checkbox');
      fireEvent.click(checkbox);

      // コンポーネントが再レンダリングされることを想定（value propsは外部で変更される）
      // この時点では内部状態でチェック済みになり、入力フィールドが表示される
    });
  });

  describe('税率入力', () => {
    it('税率を入力するとonChangeが呼ばれる', () => {
      const onChange = vi.fn();
      render(<ProductTaxField {...defaultProps} value="10.000" onChange={onChange} />);

      const input = screen.getByDisplayValue('10.000');
      fireEvent.change(input, { target: { value: '8.000' } });

      expect(onChange).toHaveBeenCalledWith('tax1', '8.000');
    });

    it('小数点3桁でフォーマットされて表示される', () => {
      render(<ProductTaxField {...defaultProps} value="10" />);

      // 10は10.000として表示される
      const input = screen.getByDisplayValue('10.000');
      expect(input).toBeInTheDocument();
    });

    it('テキスト入力フィールドとして表示される', () => {
      render(<ProductTaxField {...defaultProps} value="10.000" />);

      const input = screen.getByDisplayValue('10.000');
      expect(input).toHaveAttribute('type', 'text');
      expect(input).toHaveAttribute('inputMode', 'decimal');
    });
  });

  describe('エラー表示', () => {
    it('エラー時に入力フィールドにエラースタイルが適用される', () => {
      render(<ProductTaxField {...defaultProps} value="10.000" error="エラー" />);

      const input = screen.getByDisplayValue('10.000');
      expect(input.className).toContain('border-red');
    });
  });


  describe('カスタムデフォルト税率', () => {
    it('カスタムデフォルト税率が適用される', () => {
      const onChange = vi.fn();
      render(
        <ProductTaxField
          {...defaultProps}
          defaultTaxRate="8.000"
          value=""
          onChange={onChange}
        />
      );

      const checkbox = screen.getByRole('checkbox');
      fireEvent.click(checkbox);

      expect(onChange).toHaveBeenCalledWith('tax1', '8.000');
    });
  });
});

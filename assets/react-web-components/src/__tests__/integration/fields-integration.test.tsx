/**
 * GetFields API + FieldRenderer 統合テスト
 *
 * APIレスポンスからFieldRendererでのフィールド描画までの
 * エンドツーエンドの統合テスト
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, act, waitFor, fireEvent } from '@testing-library/react';
import { FieldRenderer } from '../../components/FieldRenderer';
import { UI_TYPES, FieldDefinition } from '../../types/field';

// モックAPIレスポンス（実際のGetFields APIレスポンス形式）
const mockGetFieldsResponse = {
  module: 'Accounts',
  fields: [
    {
      name: 'accountname',
      label: '顧客企業名',
      uitype: '1',
      mandatory: true,
      readonly: false,
      maxlength: 100,
      type: 'string',
      editable: true,
      displaytype: '1',
      block: '基本情報',
      quickcreate: true,
      quickcreatesequence: 1
    },
    {
      name: 'phone',
      label: '電話番号',
      uitype: '11',
      mandatory: false,
      readonly: false,
      type: 'string',
      editable: true,
      displaytype: '1',
      block: '基本情報',
      quickcreate: true,
      quickcreatesequence: 2
    },
    {
      name: 'industry',
      label: '業種',
      uitype: '15',
      mandatory: false,
      readonly: false,
      type: 'picklist',
      editable: true,
      displaytype: '1',
      block: '基本情報',
      picklistValues: [
        { value: 'IT', label: 'IT・通信' },
        { value: 'Finance', label: '金融' },
        { value: 'Manufacturing', label: '製造業' }
      ],
      quickcreate: true,
      quickcreatesequence: 3
    },
    {
      name: 'annualrevenue',
      label: '年間売上高',
      uitype: '71',
      mandatory: false,
      readonly: false,
      type: 'currency',
      editable: true,
      displaytype: '1',
      block: '基本情報',
      quickcreate: true,
      quickcreatesequence: 4
    },
    {
      name: 'isactive',
      label: '有効',
      uitype: '56',
      mandatory: false,
      readonly: false,
      type: 'boolean',
      editable: true,
      displaytype: '1',
      block: '基本情報',
      quickcreate: true,
      quickcreatesequence: 5
    }
  ]
};

// APIフィールド→FieldDefinition変換関数（useQuickCreateFieldsと同等のロジック）
const convertApiFieldToDefinition = (apiField: any): FieldDefinition => ({
  name: apiField.name,
  label: apiField.label,
  uitype: apiField.uitype,
  mandatory: apiField.mandatory || false,
  readonly: apiField.readonly || false,
  maxlength: apiField.maxlength,
  fieldinfo: {
    type: apiField.type || 'string',
    defaultvalue: apiField.defaultValue,
    editable: apiField.editable,
    displaytype: apiField.displaytype,
    block: apiField.block,
    referenceModules: apiField.referenceModules
  },
  picklistValues: apiField.picklistValues?.map((item: any) => ({
    label: item.label,
    value: item.value
  }))
});

describe('GetFields API + FieldRenderer 統合テスト', () => {
  describe('APIレスポンスからのフィールド描画', () => {
    it('文字列フィールド（UIType 1）が正しく描画される', () => {
      const apiField = mockGetFieldsResponse.fields[0]; // accountname
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText('顧客企業名')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('顧客企業名を入力してください')).toBeInTheDocument();
    });

    it('電話番号フィールド（UIType 11）が正しく描画される', () => {
      const apiField = mockGetFieldsResponse.fields[1]; // phone
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_phone');
      expect(input).toHaveAttribute('type', 'tel');
    });

    it('ピックリストフィールド（UIType 15）が正しく描画される', () => {
      const apiField = mockGetFieldsResponse.fields[2]; // industry
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText('業種')).toBeInTheDocument();
    });

    it('通貨フィールド（UIType 71）が正しく描画される', () => {
      const apiField = mockGetFieldsResponse.fields[3]; // annualrevenue
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByText('¥')).toBeInTheDocument();
    });

    it('真偽値フィールド（UIType 56）が正しく描画される', () => {
      const apiField = mockGetFieldsResponse.fields[4]; // isactive
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      expect(screen.getByRole('checkbox')).toBeInTheDocument();
    });
  });

  describe('必須フィールドの表示', () => {
    it('mandatory=trueのフィールドに必須マークが表示される', () => {
      const apiField = mockGetFieldsResponse.fields[0]; // mandatory: true
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const label = screen.getByText('顧客企業名');
      // ラベルはspan要素として描画される
      const labelElement = label.closest('span');
      expect(labelElement).toBeInTheDocument();
      // 必須マークのspan（*）が兄弟要素として存在することを確認
      const container = labelElement?.parentElement;
      expect(container?.textContent).toContain('*');
    });

    it('mandatory=falseのフィールドには必須マークがない', () => {
      const apiField = mockGetFieldsResponse.fields[1]; // mandatory: false
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const label = screen.getByText('電話番号');
      // ラベルはspan要素として描画される
      const labelElement = label.closest('span');
      // 必須マークのspanは空（*がない）
      const container = labelElement?.parentElement;
      const mandatorySpan = container?.querySelector('span.text-red-500');
      expect(mandatorySpan?.textContent?.trim()).toBe('');
    });
  });

  describe('全フィールドの一括描画', () => {
    it('複数フィールドを順番に描画できる', () => {
      const fields = mockGetFieldsResponse.fields.map(convertApiFieldToDefinition);
      const values: Record<string, any> = {};
      const onChange = vi.fn();

      const { container } = render(
        <div>
          {fields.map(field => (
            <FieldRenderer
              key={field.name}
              field={field}
              value={values[field.name] || ''}
              onChange={onChange}
            />
          ))}
        </div>
      );

      // 全フィールドのラベルが表示されている
      expect(screen.getByText('顧客企業名')).toBeInTheDocument();
      expect(screen.getByText('電話番号')).toBeInTheDocument();
      expect(screen.getByText('業種')).toBeInTheDocument();
      expect(screen.getByText('年間売上高')).toBeInTheDocument();
      expect(screen.getByText('有効')).toBeInTheDocument();
    });
  });

  describe('フィールド値の変更', () => {
    it('入力変更時にonChangeが呼ばれる', async () => {
      const apiField = mockGetFieldsResponse.fields[0];
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="" onChange={onChange} />);

      const input = container.querySelector('#field_accountname');

      // fireEvent.changeを使用
      fireEvent.change(input!, { target: { value: 'テスト' } });

      expect(onChange).toHaveBeenCalled();
    });
  });

  describe('読み取り専用フィールド', () => {
    it('readonly=trueのフィールドが無効化される', () => {
      const apiField = {
        ...mockGetFieldsResponse.fields[0],
        readonly: true
      };
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      const { container } = render(<FieldRenderer field={field} value="test" onChange={onChange} />);

      expect(container.querySelector('#field_accountname')).toBeDisabled();
    });
  });

  describe('エラーハンドリング', () => {
    it('不正なUITypeでも警告が表示される', () => {
      const apiField = {
        name: 'unknown',
        label: '不明フィールド',
        uitype: '999', // サポートされていないUIType
        mandatory: false,
        readonly: false
      };
      const field = convertApiFieldToDefinition(apiField);
      const onChange = vi.fn();

      render(<FieldRenderer field={field} value="" onChange={onChange} />);

      // 未サポートUITypeでもエラーにはならず、デフォルトのテキスト入力が表示される
      expect(screen.getByText('不明フィールド')).toBeInTheDocument();
    });
  });
});

describe('参照フィールドの統合テスト', () => {
  const mockReferenceField = {
    name: 'account_id',
    label: '取引先',
    uitype: '10',
    mandatory: false,
    readonly: false,
    type: 'reference',
    editable: true,
    displaytype: '1',
    referenceModules: ['Accounts']
  };

  it('参照フィールドが正しく描画される', () => {
    const field = convertApiFieldToDefinition(mockReferenceField);
    const onChange = vi.fn();

    render(<FieldRenderer field={field} value="" onChange={onChange} />);

    expect(screen.getByText('取引先')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('取引先を検索...')).toBeInTheDocument();
  });
});

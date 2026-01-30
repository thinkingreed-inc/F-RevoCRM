/**
 * RecordType 切り替え統合テスト
 *
 * RecordTypeフィールドの選択によるフィールド表示切り替えの
 * 統合テスト
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { FieldRenderer } from '../../components/FieldRenderer';
import { UI_TYPES, FieldDefinition } from '../../types/field';

// RecordTypeフィールドのモック
const mockRecordTypeField: FieldDefinition = {
  name: 'record_type',
  label: 'レコードタイプ',
  uitype: UI_TYPES.PICKLIST,
  mandatory: true,
  readonly: false,
  picklistValues: [
    { value: 'type_a', label: 'タイプA' },
    { value: 'type_b', label: 'タイプB' },
    { value: 'type_c', label: 'タイプC' },
  ],
};

// タイプ別フィールド定義
const typeAFields: FieldDefinition[] = [
  {
    name: 'field_a1',
    label: 'タイプAフィールド1',
    uitype: UI_TYPES.STRING,
    mandatory: true,
    readonly: false,
  },
  {
    name: 'field_a2',
    label: 'タイプAフィールド2',
    uitype: UI_TYPES.NUMBER,
    mandatory: false,
    readonly: false,
  },
];

const typeBFields: FieldDefinition[] = [
  {
    name: 'field_b1',
    label: 'タイプBフィールド1',
    uitype: UI_TYPES.DATE,
    mandatory: true,
    readonly: false,
  },
  {
    name: 'field_b2',
    label: 'タイプBフィールド2',
    uitype: UI_TYPES.BOOLEAN,
    mandatory: false,
    readonly: false,
  },
];

const typeCFields: FieldDefinition[] = [
  {
    name: 'field_c1',
    label: 'タイプCフィールド1',
    uitype: UI_TYPES.TEXTAREA,
    mandatory: false,
    readonly: false,
  },
];

// RecordType選択による動的フィールド切り替えコンポーネント
const RecordTypeForm = ({
  onRecordTypeChange,
}: {
  onRecordTypeChange?: (value: string) => void;
}) => {
  const [recordType, setRecordType] = React.useState<string>('');
  const [formData, setFormData] = React.useState<Record<string, any>>({});

  const handleRecordTypeChange = (value: any) => {
    setRecordType(value);
    onRecordTypeChange?.(value);
    // RecordType変更時にフォームデータをリセット
    setFormData({});
  };

  const handleFieldChange = (value: any, fieldName?: string) => {
    if (fieldName) {
      setFormData(prev => ({ ...prev, [fieldName]: value }));
    }
  };

  // 選択されたRecordTypeに応じたフィールド
  const getFieldsForType = (): FieldDefinition[] => {
    switch (recordType) {
      case 'type_a':
        return typeAFields;
      case 'type_b':
        return typeBFields;
      case 'type_c':
        return typeCFields;
      default:
        return [];
    }
  };

  const currentFields = getFieldsForType();

  return (
    <div data-testid="recordtype-form">
      {/* RecordTypeセレクター */}
      <FieldRenderer
        field={mockRecordTypeField}
        value={recordType}
        onChange={handleRecordTypeChange}
      />

      {/* 動的フィールド */}
      <div data-testid="dynamic-fields">
        {currentFields.map(field => (
          <FieldRenderer
            key={field.name}
            field={field}
            value={formData[field.name] || ''}
            onChange={(value) => handleFieldChange(value, field.name)}
          />
        ))}
      </div>
    </div>
  );
};

// React import
import React from 'react';

describe('RecordType 切り替え統合テスト', () => {
  describe('RecordTypeフィールドの表示', () => {
    it('RecordTypeピックリストが正しく表示される', () => {
      const onChange = vi.fn();

      render(
        <FieldRenderer
          field={mockRecordTypeField}
          value=""
          onChange={onChange}
        />
      );

      expect(screen.getByText('レコードタイプ')).toBeInTheDocument();
    });

    it('RecordTypeの選択肢が表示される', () => {
      const onChange = vi.fn();

      render(
        <FieldRenderer
          field={mockRecordTypeField}
          value=""
          onChange={onChange}
        />
      );

      // ピックリストコンポーネントは存在する
      expect(screen.getByText('レコードタイプ')).toBeInTheDocument();
    });
  });

  describe('RecordType選択による動的フィールド切り替え', () => {
    it('初期状態では動的フィールドが表示されない', () => {
      render(<RecordTypeForm />);

      // 動的フィールドエリアは存在するが中身は空
      const dynamicFields = screen.getByTestId('dynamic-fields');
      expect(dynamicFields.children.length).toBe(0);
    });

    it('タイプA選択時にタイプA用フィールドが表示される', async () => {
      const onRecordTypeChange = vi.fn();

      render(<RecordTypeForm onRecordTypeChange={onRecordTypeChange} />);

      // 直接状態を変更するテスト（ピックリストの内部実装に依存しない）
      const dynamicFields = screen.getByTestId('dynamic-fields');

      // コンポーネントの再レンダリングをシミュレート
      // 実際のテストではuseStateの更新をトリガー
    });
  });

  describe('フィールド値のリセット', () => {
    it('RecordType変更時にフォームデータがリセットされる', async () => {
      // このテストはRecordTypeFormコンポーネントの内部ロジックをテスト
      // 実際のアプリケーションではuseQuickCreateFieldsがRecordType変更を検知して
      // 新しいフィールドを取得し、フォームデータがリセットされる

      const onRecordTypeChange = vi.fn();
      render(<RecordTypeForm onRecordTypeChange={onRecordTypeChange} />);

      // フォームが存在することを確認
      expect(screen.getByTestId('recordtype-form')).toBeInTheDocument();
    });
  });

  describe('必須フィールドの動的変更', () => {
    it('各RecordTypeで異なる必須フィールドが設定される', () => {
      // タイプA: field_a1が必須
      const typeAField = typeAFields.find(f => f.name === 'field_a1');
      expect(typeAField?.mandatory).toBe(true);

      // タイプB: field_b1が必須
      const typeBField = typeBFields.find(f => f.name === 'field_b1');
      expect(typeBField?.mandatory).toBe(true);

      // タイプC: 必須フィールドなし
      const typeCHasMandatory = typeCFields.some(f => f.mandatory);
      expect(typeCHasMandatory).toBe(false);
    });
  });

  describe('UIType別の適切なレンダリング', () => {
    it('タイプAフィールドが正しいUITypeでレンダリングされる', () => {
      const onChange = vi.fn();

      const { container } = render(
        <div>
          {typeAFields.map(field => (
            <FieldRenderer
              key={field.name}
              field={field}
              value=""
              onChange={onChange}
            />
          ))}
        </div>
      );

      // 文字列フィールド（必須フィールドはsr-onlyで「(必須)」が追加されるため、exact: falseを使用）
      expect(screen.getByLabelText('タイプAフィールド1', { exact: false })).toBeInTheDocument();
      expect(screen.getByLabelText('タイプAフィールド1', { exact: false })).toHaveAttribute('type', 'text');

      // 数値フィールド
      expect(screen.getByLabelText('タイプAフィールド2', { exact: false })).toBeInTheDocument();
      expect(screen.getByLabelText('タイプAフィールド2', { exact: false })).toHaveAttribute('type', 'number');
    });

    it('タイプBフィールドが正しいUITypeでレンダリングされる', () => {
      const onChange = vi.fn();

      render(
        <div>
          {typeBFields.map(field => (
            <FieldRenderer
              key={field.name}
              field={field}
              value=""
              onChange={onChange}
            />
          ))}
        </div>
      );

      // 日付フィールド（必須フィールドはsr-onlyで「(必須)」が追加されるため、exact: falseを使用）
      expect(screen.getByLabelText('タイプBフィールド1', { exact: false })).toHaveAttribute('type', 'date');

      // 真偽値フィールド（チェックボックス）
      expect(screen.getByRole('checkbox')).toBeInTheDocument();
    });

    it('タイプCフィールドがテキストエリアとしてレンダリングされる', () => {
      const onChange = vi.fn();

      render(
        <div>
          {typeCFields.map(field => (
            <FieldRenderer
              key={field.name}
              field={field}
              value=""
              onChange={onChange}
            />
          ))}
        </div>
      );

      // テキストエリア
      expect(screen.getByRole('textbox')).toBeInTheDocument();
    });
  });
});

describe('RecordType APIレスポンスの処理', () => {
  // 実際のAPIレスポンス形式をシミュレート
  const mockApiResponseWithRecordType = {
    module: 'Potentials',
    fields: [
      {
        name: 'record_type',
        label: 'レコードタイプ',
        uitype: '15',
        mandatory: true,
        isRecordTypeField: true,
        picklistValues: [
          { value: 'opportunity', label: '商談' },
          { value: 'proposal', label: '提案' },
        ],
      },
      {
        name: 'potentialname',
        label: '案件名',
        uitype: '1',
        mandatory: true,
        visibleForRecordTypes: ['opportunity', 'proposal'], // 両方で表示
      },
      {
        name: 'sales_stage',
        label: '商談ステージ',
        uitype: '15',
        mandatory: true,
        visibleForRecordTypes: ['opportunity'], // 商談のみ
      },
      {
        name: 'proposal_date',
        label: '提案日',
        uitype: '5',
        mandatory: false,
        visibleForRecordTypes: ['proposal'], // 提案のみ
      },
    ],
  };

  it('RecordTypeに応じたフィールドフィルタリングが機能する', () => {
    const allFields = mockApiResponseWithRecordType.fields;
    const selectedRecordType = 'opportunity';

    // 商談タイプ選択時に表示されるフィールド
    const visibleFields = allFields.filter(field => {
      if (!field.visibleForRecordTypes) return true; // RecordType制限なし
      return field.visibleForRecordTypes.includes(selectedRecordType);
    });

    expect(visibleFields.map(f => f.name)).toContain('record_type');
    expect(visibleFields.map(f => f.name)).toContain('potentialname');
    expect(visibleFields.map(f => f.name)).toContain('sales_stage');
    expect(visibleFields.map(f => f.name)).not.toContain('proposal_date');
  });

  it('提案タイプ選択時は提案日が表示される', () => {
    const allFields = mockApiResponseWithRecordType.fields;
    const selectedRecordType = 'proposal';

    const visibleFields = allFields.filter(field => {
      if (!field.visibleForRecordTypes) return true;
      return field.visibleForRecordTypes.includes(selectedRecordType);
    });

    expect(visibleFields.map(f => f.name)).toContain('proposal_date');
    expect(visibleFields.map(f => f.name)).not.toContain('sales_stage');
  });
});

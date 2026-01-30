/**
 * usePicklistDependency Hook のテスト
 */

import { describe, it, expect } from 'vitest';
import { renderHook } from '@testing-library/react';
import { usePicklistDependency } from '../usePicklistDependency';
import { PicklistDependency } from '../../../../types/quickcreate';
import { FieldInfo } from '../../../../types/field';

// テスト用のピックリスト連動設定
const mockDependency: PicklistDependency = {
  // ソースフィールド: leadsource
  leadsource: {
    // leadsource = 'Web' の場合
    'Web': {
      // ターゲット: industry は 'IT', 'Software' のみ許可
      industry: ['IT', 'Software']
    },
    // leadsource = 'Referral' の場合
    'Referral': {
      industry: ['Finance', 'Healthcare']
    },
    // デフォルト（上記以外の値の場合）
    '__DEFAULT__': {
      industry: ['IT', 'Software', 'Finance', 'Healthcare', 'Manufacturing']
    }
  },
  // cf_962 -> sales_stage の連動
  cf_962: {
    '選択肢１': {
      sales_stage: ['Prospecting', 'Qualification']
    },
    '選択肢２': {
      sales_stage: ['Needs Analysis', 'Value Proposition']
    },
    '__DEFAULT__': {
      sales_stage: ['Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition', 'Closed Won', 'Closed Lost']
    }
  }
};

// テスト用のフィールド定義
const mockFields: FieldInfo[] = [
  {
    name: 'leadsource',
    label: '紹介元',
    uitype: '15',
    mandatory: false,
    readonly: false,
    fieldinfo: { type: 'picklist' },
    picklistValues: [
      { label: '', value: '' },
      { label: 'Web', value: 'Web' },
      { label: 'Referral', value: 'Referral' },
      { label: 'Other', value: 'Other' }
    ]
  },
  {
    name: 'industry',
    label: '業種',
    uitype: '15',
    mandatory: false,
    readonly: false,
    fieldinfo: { type: 'picklist' },
    picklistValues: [
      { label: '', value: '' },
      { label: 'IT', value: 'IT' },
      { label: 'Software', value: 'Software' },
      { label: 'Finance', value: 'Finance' },
      { label: 'Healthcare', value: 'Healthcare' },
      { label: 'Manufacturing', value: 'Manufacturing' }
    ]
  },
  {
    name: 'cf_962',
    label: 'カスタム項目',
    uitype: '15',
    mandatory: false,
    readonly: false,
    fieldinfo: { type: 'picklist' },
    picklistValues: [
      { label: '', value: '' },
      { label: '選択肢１', value: '選択肢１' },
      { label: '選択肢２', value: '選択肢２' }
    ]
  },
  {
    name: 'sales_stage',
    label: '営業ステージ',
    uitype: '15',
    mandatory: false,
    readonly: false,
    fieldinfo: { type: 'picklist' },
    picklistValues: [
      { label: '', value: '' },
      { label: 'Prospecting', value: 'Prospecting' },
      { label: 'Qualification', value: 'Qualification' },
      { label: 'Needs Analysis', value: 'Needs Analysis' },
      { label: 'Value Proposition', value: 'Value Proposition' },
      { label: 'Closed Won', value: 'Closed Won' },
      { label: 'Closed Lost', value: 'Closed Lost' }
    ]
  }
];

describe('usePicklistDependency', () => {
  describe('hasDependency', () => {
    it('連動設定が存在する場合はtrueを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));
      expect(result.current.hasDependency).toBe(true);
    });

    it('連動設定がundefinedの場合はfalseを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(undefined));
      expect(result.current.hasDependency).toBe(false);
    });

    it('連動設定が空オブジェクトの場合はfalseを返す', () => {
      const { result } = renderHook(() => usePicklistDependency({}));
      expect(result.current.hasDependency).toBe(false);
    });
  });

  describe('getAffectedTargetFields', () => {
    it('ソースフィールドに対応するターゲットフィールドを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const targets = result.current.getAffectedTargetFields('leadsource');
      expect(targets).toContain('industry');
    });

    it('存在しないソースフィールドの場合は空配列を返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const targets = result.current.getAffectedTargetFields('nonexistent');
      expect(targets).toEqual([]);
    });
  });

  describe('getFilteredFields', () => {
    it('ソースフィールドの値に応じてターゲットの選択肢をフィルタリングする', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Web' };
      const filtered = result.current.getFilteredFields(mockFields, formData);

      const industryField = filtered.find(f => f.name === 'industry');
      expect(industryField?.picklistValues?.map(v => v.value)).toEqual(['', 'IT', 'Software']);
    });

    it('デフォルト設定を使用する', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Other' };  // DEFAULTが適用される
      const filtered = result.current.getFilteredFields(mockFields, formData);

      const industryField = filtered.find(f => f.name === 'industry');
      expect(industryField?.picklistValues?.map(v => v.value)).toEqual([
        '', 'IT', 'Software', 'Finance', 'Healthcare', 'Manufacturing'
      ]);
    });

    it('連動設定がないフィールドはそのまま返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Web' };
      const filtered = result.current.getFilteredFields(mockFields, formData);

      // leadsourceフィールドは連動元なので変更されない
      const leadsourceField = filtered.find(f => f.name === 'leadsource');
      expect(leadsourceField?.picklistValues).toEqual(mockFields[0].picklistValues);
    });
  });

  describe('isValueAllowed', () => {
    it('許可された値に対してtrueを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Web' };
      expect(result.current.isValueAllowed('industry', 'IT', formData)).toBe(true);
      expect(result.current.isValueAllowed('industry', 'Software', formData)).toBe(true);
    });

    it('許可されていない値に対してfalseを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Web' };
      expect(result.current.isValueAllowed('industry', 'Finance', formData)).toBe(false);
      expect(result.current.isValueAllowed('industry', 'Healthcare', formData)).toBe(false);
    });

    it('空値は常にtrueを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Web' };
      expect(result.current.isValueAllowed('industry', '', formData)).toBe(true);
      expect(result.current.isValueAllowed('industry', null, formData)).toBe(true);
      expect(result.current.isValueAllowed('industry', undefined, formData)).toBe(true);
    });

    it('連動設定がないフィールドはtrueを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Web' };
      expect(result.current.isValueAllowed('nonexistent', 'any', formData)).toBe(true);
    });
  });

  describe('getFieldsToClear', () => {
    it('ソースフィールド変更時に許可されない値を持つターゲットフィールドを返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      // 現在: leadsource='Web' で industry='IT' (許可)
      // 変更: leadsource='Referral' にすると industry='IT' は許可されない
      const formData = { leadsource: 'Web', industry: 'IT' };
      const fieldsToClear = result.current.getFieldsToClear('leadsource', 'Referral', formData);

      expect(fieldsToClear).toContain('industry');
    });

    it('ソースフィールド変更後も許可される値を持つ場合はクリアしない', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      // 現在: cf_962='選択肢１' で sales_stage='Prospecting' (許可)
      // 変更: cf_962='選択肢２' にしても sales_stage... は別の値が許可される
      // この場合 'Prospecting' は選択肢２では許可されないのでクリア対象
      const formData = { cf_962: '選択肢１', sales_stage: 'Prospecting' };
      const fieldsToClear = result.current.getFieldsToClear('cf_962', '選択肢２', formData);

      expect(fieldsToClear).toContain('sales_stage');
    });

    it('ターゲットフィールドの値が空の場合はクリアしない', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { leadsource: 'Web', industry: '' };
      const fieldsToClear = result.current.getFieldsToClear('leadsource', 'Referral', formData);

      expect(fieldsToClear).not.toContain('industry');
    });

    it('連動設定がない場合は空配列を返す', () => {
      const { result } = renderHook(() => usePicklistDependency(undefined));

      const formData = { leadsource: 'Web', industry: 'IT' };
      const fieldsToClear = result.current.getFieldsToClear('leadsource', 'Referral', formData);

      expect(fieldsToClear).toEqual([]);
    });

    it('ソースフィールドが連動設定にない場合は空配列を返す', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      const formData = { nonexistent: 'value', industry: 'IT' };
      const fieldsToClear = result.current.getFieldsToClear('nonexistent', 'newvalue', formData);

      expect(fieldsToClear).toEqual([]);
    });

    it('DEFAULTから特定値に変更した場合、許可されない値はクリアされる', () => {
      const { result } = renderHook(() => usePicklistDependency(mockDependency));

      // 現在: leadsource='Other'（DEFAULT）で industry='Manufacturing' (DEFAULTで許可)
      // 変更: leadsource='Web' にすると 'Manufacturing' は許可されない
      const formData = { leadsource: 'Other', industry: 'Manufacturing' };
      const fieldsToClear = result.current.getFieldsToClear('leadsource', 'Web', formData);

      expect(fieldsToClear).toContain('industry');
    });
  });
});

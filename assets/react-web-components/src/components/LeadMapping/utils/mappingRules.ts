/**
 * リードマッピング設定画面のルール（React に依存しない純粋な関数）
 *
 * 選べる項目と型の制約は旧設定画面（LeadMapping.js）と同じ:
 * 昇格先の項目はリードの項目と同じ型のものだけ。ロック行（editable=0）が使う昇格先の項目は
 * 他の行では選べない。
 */
import type { ConvertModule } from "../../../types/leadConvert";
import type {
  InputFieldOption,
  LeadMappingInfo,
  MappingFieldOption,
  MappingRow,
  MappingRowData,
} from "../../../types/leadMapping";

export const TARGET_MODULES: readonly ConvertModule[] = [
  "Accounts",
  "Contacts",
  "Potentials",
];

export function toRows(mapping: MappingRowData[]): MappingRow[] {
  return mapping.map((row) => ({
    ...row,
    key: `row-${row.id}`,
    deleted: false,
  }));
}

export function leadFieldOf(
  info: LeadMappingInfo,
  id: number | null,
): MappingFieldOption | undefined {
  return info.leadFields.find((field) => field.id === id);
}

/** 行の昇格先として選べる項目 */
export function targetOptions(
  info: LeadMappingInfo,
  row: MappingRow,
  module: ConvertModule,
): MappingFieldOption[] {
  const leadField = leadFieldOf(info, row.lead);
  if (!leadField) {
    return [];
  }
  return info.modules[module].mappableFields.filter(
    (field) =>
      field.type === leadField.type &&
      (!info.restrictedFieldIds.includes(field.id) || field.id === row[module]),
  );
}

/** リードの項目を変える。型が合わなくなった昇格先は外す */
export function changeLeadField(
  info: LeadMappingInfo,
  row: MappingRow,
  leadId: number | null,
): MappingRow {
  const next: MappingRow = { ...row, lead: leadId };
  for (const module of TARGET_MODULES) {
    if (
      next[module] !== null &&
      !targetOptions(info, next, module).some(
        (field) => field.id === next[module],
      )
    ) {
      next[module] = null;
    }
  }
  return next;
}

/** ConvertSettings mode=saveMapping に送る行（ロック行は送らない） */
export function toSaveRows(
  rows: MappingRow[],
): Array<Record<string, number | boolean>> {
  const result: Array<Record<string, number | boolean>> = [];
  for (const row of rows) {
    if (
      !row.editable ||
      (row.id === null && row.deleted) ||
      row.lead === null
    ) {
      continue;
    }
    const saveRow: Record<string, number | boolean> = {};
    if (row.id !== null) {
      saveRow.id = row.id;
    }
    saveRow.lead = row.lead;
    for (const module of TARGET_MODULES) {
      saveRow[module] = row[module] ?? 0;
    }
    if (row.deleted) {
      saveRow.deleted = true;
    }
    result.push(saveRow);
  }
  return result;
}

export function moveItem<T>(items: T[], index: number, offset: number): T[] {
  const target = index + offset;
  if (target < 0 || target >= items.length) {
    return items;
  }
  const next = [...items];
  [next[index], next[target]] = [next[target], next[index]];
  return next;
}

/** 昇格画面に実際に出る項目の並び（設定に無い必須項目は先頭に出る） */
export function displayedLayout(
  layout: string[],
  inputFields: InputFieldOption[],
): string[] {
  const available = new Set(inputFields.map((field) => field.name));
  const configured = layout.filter((name) => available.has(name));
  const mandatory = inputFields
    .filter((field) => field.mandatory && !configured.includes(field.name))
    .map((field) => field.name);
  return [...mandatory, ...configured];
}

export function addRuleField(
  rules: string[][],
  index: number,
  fieldName: string,
): string[][] {
  return rules.map((rule, i) =>
    i === index && !rule.includes(fieldName) ? [...rule, fieldName] : rule,
  );
}

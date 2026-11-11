import { describe, it, expect } from "vitest";
import {
  toRows,
  targetOptions,
  changeLeadField,
  toSaveRows,
  moveItem,
  displayedLayout,
  addRuleField,
} from "./mappingRules";
import type { LeadMappingInfo } from "../../../types/leadMapping";

const leadFields = [
  { id: 1, name: "company", label: "会社名", type: "string" },
  { id: 2, name: "email", label: "メール", type: "email" },
  { id: 3, name: "annualrevenue", label: "年間売上", type: "currency" },
];

const accountFields = [
  { id: 11, name: "accountname", label: "顧客企業名", type: "string" },
  { id: 12, name: "email1", label: "メール", type: "email" },
  { id: 13, name: "annual_revenue", label: "年間売上", type: "currency" },
  { id: 14, name: "website", label: "Web", type: "string" },
];

function info(): LeadMappingInfo {
  const module = (label: string) => ({
    label,
    mappableFields: [],
    inputFields: [],
    matchableFields: [],
    layout: [],
    defaults: {},
    matchRules: [],
  });
  return {
    mapping: [
      {
        id: 100,
        editable: false,
        lead: 1,
        Accounts: 11,
        Contacts: null,
        Potentials: null,
      },
      {
        id: 101,
        editable: true,
        lead: 2,
        Accounts: 12,
        Contacts: null,
        Potentials: null,
      },
    ],
    leadFields,
    restrictedFieldIds: [11],
    modules: {
      Accounts: { ...module("顧客企業"), mappableFields: accountFields },
      Contacts: module("顧客担当者"),
      Potentials: module("案件"),
    },
  };
}

describe("mappingRules", () => {
  it("リードの項目と型が同じ項目だけを選べる（ロック行が使う項目は除く）", () => {
    const rows = toRows(info().mapping);
    const options = targetOptions(info(), rows[1], "Accounts");
    expect(options.map((o) => o.id)).toEqual([12]);
    const stringRow = { ...rows[1], lead: 1 };
    expect(
      targetOptions(info(), stringRow, "Accounts").map((o) => o.id),
    ).toEqual([14]);
  });

  it("ロック行は今の値を選択肢に残す", () => {
    const rows = toRows(info().mapping);
    expect(
      targetOptions(info(), rows[0], "Accounts").map((o) => o.id),
    ).toContain(11);
  });

  it("リードの項目を変えると型の合わない昇格先は外す", () => {
    const rows = toRows(info().mapping);
    const changed = changeLeadField(info(), rows[1], 3);
    expect(changed.lead).toBe(3);
    expect(changed.Accounts).toBeNull();
  });

  it("保存では削除した既存行に deleted を付け、削除した新しい行は送らない", () => {
    const rows = toRows(info().mapping);
    rows[1] = { ...rows[1], deleted: true };
    rows.push({
      id: null,
      key: "new-1",
      editable: true,
      lead: 3,
      Accounts: 13,
      Contacts: null,
      Potentials: null,
      deleted: false,
    });
    rows.push({
      id: null,
      key: "new-2",
      editable: true,
      lead: 2,
      Accounts: null,
      Contacts: null,
      Potentials: null,
      deleted: true,
    });
    expect(toSaveRows(rows)).toEqual([
      {
        id: 101,
        lead: 2,
        Accounts: 12,
        Contacts: 0,
        Potentials: 0,
        deleted: true,
      },
      { lead: 3, Accounts: 13, Contacts: 0, Potentials: 0 },
    ]);
  });

  it("並び替え", () => {
    expect(moveItem(["a", "b", "c"], 2, -1)).toEqual(["a", "c", "b"]);
    expect(moveItem(["a", "b", "c"], 0, -1)).toEqual(["a", "b", "c"]);
  });

  it("表示項目: 設定に無い必須項目を先頭に足す", () => {
    const inputFields = [
      { name: "lastname", label: "姓", mandatory: true },
      { name: "email", label: "メール", mandatory: false },
      { name: "phone", label: "電話", mandatory: false },
    ];
    expect(displayedLayout(["email"], inputFields)).toEqual([
      "lastname",
      "email",
    ]);
    expect(displayedLayout(["email", "lastname"], inputFields)).toEqual([
      "email",
      "lastname",
    ]);
  });

  it("判定条件に同じ項目を二重に足さない", () => {
    expect(addRuleField([["email"]], 0, "email")).toEqual([["email"]]);
    expect(addRuleField([["lastname"]], 0, "firstname")).toEqual([
      ["lastname", "firstname"],
    ]);
  });
});

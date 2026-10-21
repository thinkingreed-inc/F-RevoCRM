import { describe, it, expect } from "vitest";
import {
  initialSections,
  changeMode,
  forcedBy,
  validateSections,
  buildPayload,
  applyServerErrors,
  defaultTransfer,
} from "./convertRules";
import type {
  ConvertLeadInfo,
  ConvertModuleInfo,
  ConvertSections,
} from "../../../types/leadConvert";
import type { FieldInfo } from "../../../types/field";

const t = (key: string, ...args: (string | number)[]) =>
  [key, ...args].join("|");

function moduleInfo(overrides: Partial<ConvertModuleInfo>): ConvertModuleInfo {
  return {
    available: true,
    label: "",
    fields: [],
    values: {},
    referenceLabels: {},
    match: null,
    ...overrides,
  };
}

function info(overrides: Partial<ConvertLeadInfo> = {}): ConvertLeadInfo {
  return {
    record: { id: 10, label: "山田 太郎" },
    assignedUserId: "1",
    transferOptions: [
      { module: "Contacts", label: "顧客担当者" },
      { module: "Accounts", label: "顧客企業" },
    ],
    constraints: {
      contactAccountMandatory: false,
      potentialAccountMandatory: false,
      potentialContactMandatory: false,
    },
    modules: {
      Accounts: moduleInfo({
        label: "顧客企業",
        fields: ["accountname"],
        values: { accountname: "サンプル株式会社" },
      }),
      Contacts: moduleInfo({
        label: "顧客担当者",
        fields: ["lastname", "email"],
        values: { lastname: "山田", email: "taro@example.com" },
      }),
      Potentials: moduleInfo({
        label: "案件",
        fields: ["potentialname"],
        values: {},
      }),
    },
    ...overrides,
  };
}

function field(name: string, extra: Partial<FieldInfo> = {}): FieldInfo {
  return {
    name,
    label: name,
    uitype: "1",
    mandatory: false,
    readonly: false,
    fieldinfo: { type: "string" },
    ...extra,
  };
}

describe("initialSections", () => {
  it("判定に当たらなければ企業と担当者は新規作成、案件は作らない", () => {
    const sections = initialSections(info());
    expect(sections.Accounts.mode).toBe("create");
    expect(sections.Contacts.mode).toBe("create");
    expect(sections.Potentials.mode).toBe("none");
    expect(sections.Contacts.values.email).toBe("taro@example.com");
  });

  it("判定に当たった企業を既存として最初からセットする", () => {
    const data = info();
    data.modules.Accounts.match = {
      rule: ["accountname"],
      ruleLabel: "顧客企業名",
      total: 2,
      records: [
        {
          id: 5,
          label: "サンプル株式会社",
          accountId: null,
          accountLabel: null,
        },
        {
          id: 6,
          label: "サンプル株式会社",
          accountId: null,
          accountLabel: null,
        },
      ],
    };
    const sections = initialSections(data);
    expect(sections.Accounts.mode).toBe("existing");
    expect(sections.Accounts.existingId).toBe("5");
    expect(sections.Accounts.existingLabel).toBe("サンプル株式会社");
  });

  it("会社名が空なら企業は作らない", () => {
    const data = info();
    data.modules.Accounts.values = { accountname: "" };
    expect(initialSections(data).Accounts.mode).toBe("none");
  });

  it("担当者の顧客企業名が必須なら会社名が空でも企業を作る", () => {
    const data = info({
      constraints: {
        contactAccountMandatory: true,
        potentialAccountMandatory: false,
        potentialContactMandatory: false,
      },
    });
    data.modules.Accounts.values = { accountname: "" };
    expect(initialSections(data).Accounts.mode).toBe("create");
  });

  it("参照項目の初期表示名を _display に入れる", () => {
    const data = info();
    data.modules.Potentials.values = { campaignid: "9" };
    data.modules.Potentials.referenceLabels = {
      campaignid: "春のキャンペーン",
    };
    expect(initialSections(data).Potentials.values.campaignid_display).toBe(
      "春のキャンペーン",
    );
  });

  it("作成権限のないモジュールは作らない", () => {
    const data = info();
    data.modules.Contacts.available = false;
    expect(initialSections(data).Contacts.mode).toBe("none");
  });
});

describe("changeMode / forcedBy", () => {
  it("案件の顧客企業名が必須なら、案件を作ると企業も作る", () => {
    const data = info({
      constraints: {
        contactAccountMandatory: false,
        potentialAccountMandatory: true,
        potentialContactMandatory: false,
      },
    });
    data.modules.Accounts.values = { accountname: "" };
    let sections = initialSections(data);
    expect(sections.Accounts.mode).toBe("none");
    sections = changeMode(sections, "Potentials", "create", data);
    expect(sections.Accounts.mode).toBe("create");
    expect(forcedBy(sections, data.constraints).Accounts).toBe("Potentials");
  });

  it("強制されているモジュールは作成しないにできない", () => {
    const data = info({
      constraints: {
        contactAccountMandatory: true,
        potentialAccountMandatory: false,
        potentialContactMandatory: false,
      },
    });
    const sections = changeMode(
      initialSections(data),
      "Accounts",
      "none",
      data,
    );
    expect(sections.Accounts.mode).toBe("create");
  });

  it("強制で作るときに判定結果があれば既存に紐づける", () => {
    const data = info({
      constraints: {
        contactAccountMandatory: false,
        potentialAccountMandatory: false,
        potentialContactMandatory: true,
      },
    });
    data.modules.Contacts.match = {
      rule: ["email"],
      ruleLabel: "メール",
      total: 1,
      records: [{ id: 7, label: "山田", accountId: null, accountLabel: null }],
    };
    let sections = initialSections(data);
    sections = changeMode(sections, "Contacts", "none", data);
    expect(sections.Contacts.mode).toBe("none");
    sections = changeMode(sections, "Potentials", "create", data);
    expect(sections.Contacts.mode).toBe("existing");
    expect(sections.Contacts.existingId).toBe("7");
  });
});

describe("validateSections", () => {
  const fields = {
    Accounts: [field("accountname", { mandatory: true })],
    Contacts: [
      field("lastname", { mandatory: true }),
      field("email", { uitype: "13" }),
    ],
    Potentials: [field("potentialname", { mandatory: true })],
  };

  it("企業も担当者も無ければエラー", () => {
    const sections = initialSections(info());
    sections.Accounts.mode = "none";
    sections.Contacts.mode = "none";
    const result = validateSections(sections, fields, info(), t);
    expect(result.generalErrors).toEqual(["LBL_CONVERT_REQUIRE_ONE"]);
  });

  it("既存に紐づけるのに未選択ならエラー", () => {
    const sections = initialSections(info());
    sections.Accounts.mode = "existing";
    const result = validateSections(sections, fields, info(), t);
    expect(result.fieldErrors.Accounts.__existing).toBe(
      "LBL_CONVERT_EXISTING_REQUIRED|顧客企業",
    );
  });

  it("新規作成の必須と型を検証する", () => {
    const sections = initialSections(info());
    sections.Contacts.values = { lastname: " ", email: "bad@" };
    const result = validateSections(sections, fields, info(), t);
    expect(result.fieldErrors.Contacts.lastname).toBe(
      "LBL_FIELD_REQUIRED|lastname",
    );
    expect(result.fieldErrors.Contacts.email).toBe(
      "JS_PLEASE_ENTER_VALID_EMAIL_ADDRESS",
    );
  });

  it("作成しないセクションは検証しない", () => {
    const sections = initialSections(info());
    sections.Potentials.values = { potentialname: "" };
    const result = validateSections(sections, fields, info(), t);
    expect(result.fieldErrors.Potentials).toEqual({});
  });

  it("カスタムバリデーションが返されていれば使う", () => {
    const custom = {
      ...fields,
      Contacts: [
        field("lastname", {
          mandatory: true,
          customValidations: [
            {
              name: "noDigits",
              body: "return !/[0-9]/.test(value);",
              message: "数字は使えません",
            },
          ],
        }),
      ],
    };
    const sections = initialSections(info());
    sections.Contacts.values = { lastname: "山田1" };
    const result = validateSections(sections, custom, info(), t);
    expect(result.fieldErrors.Contacts.lastname).toBe("数字は使えません");
  });
});

describe("buildPayload", () => {
  it("表示中の項目だけを送り、既存は ID だけを送る", () => {
    const data = info();
    const sections: ConvertSections = initialSections(data);
    sections.Accounts = {
      ...sections.Accounts,
      mode: "existing",
      existingId: "5",
    };
    sections.Contacts.values = {
      lastname: "山田",
      email: "taro@example.com",
      phone: "000",
    };
    sections.Contacts.extraFields = ["phone"];
    sections.Potentials.values = { potentialname: "x" };

    const payload = buildPayload(sections, data, "1", "Contacts");

    expect(payload.modules.Accounts).toEqual({
      mode: "existing",
      existingId: 5,
      values: {},
    });
    expect(payload.modules.Contacts.values).toEqual({
      lastname: "山田",
      email: "taro@example.com",
      phone: "000",
    });
    expect(payload.modules.Potentials).toEqual({ mode: "none", values: {} });
  });
});

describe("applyServerErrors", () => {
  it("非表示項目のエラーは項目を追加表示し、編集できない項目は全体エラーにする", () => {
    const sections = initialSections(info());
    const fields = {
      Accounts: [],
      Contacts: [field("lastname"), field("email"), field("phone")],
      Potentials: [],
    };
    const result = applyServerErrors(
      sections,
      [
        {
          module: "Contacts",
          field: "phone",
          label: "電話",
          message: "電話の形式が正しくありません",
          hidden: true,
          editable: true,
          value: "03-xxxx",
        },
        {
          module: "Contacts",
          field: "secret",
          label: "秘密",
          message: "秘密は必須です（編集権限がない）",
          hidden: true,
          editable: false,
        },
        {
          module: "",
          field: "",
          label: "",
          message: "重複が見つかりました",
          hidden: false,
          editable: true,
        },
      ],
      fields,
    );
    expect(result.sections.Contacts.extraFields).toEqual(["phone"]);
    expect(result.sections.Contacts.values.phone).toBe("03-xxxx");
    expect(result.fieldErrors.Contacts.phone).toBe(
      "電話の形式が正しくありません",
    );
    expect(result.generalErrors).toEqual([
      "秘密は必須です（編集権限がない）",
      "重複が見つかりました",
    ]);
  });
});

describe("defaultTransfer", () => {
  it("担当者を作るなら担当者、作らないなら企業", () => {
    const sections = initialSections(info());
    expect(defaultTransfer(sections)).toBe("Contacts");
    sections.Contacts.mode = "none";
    expect(defaultTransfer(sections)).toBe("Accounts");
  });
});

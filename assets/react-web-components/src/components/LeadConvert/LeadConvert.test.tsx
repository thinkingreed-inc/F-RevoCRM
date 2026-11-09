import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { LeadConvert } from "./LeadConvert";
import type {
  ConvertLeadInfo,
  ConvertSaveResult,
} from "../../types/leadConvert";

const baseInfo = (): ConvertLeadInfo => ({
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
    Accounts: {
      available: true,
      label: "顧客企業",
      fields: ["accountname"],
      values: { accountname: "サンプル株式会社" },
      referenceLabels: {},
      match: {
        rule: ["accountname"],
        ruleLabel: "顧客企業名",
        total: 1,
        records: [
          {
            id: 5,
            label: "サンプル株式会社",
            accountId: null,
            accountLabel: null,
          },
        ],
      },
    },
    Contacts: {
      available: true,
      label: "顧客担当者",
      fields: ["lastname"],
      values: { lastname: "山田" },
      referenceLabels: {},
      match: null,
    },
    Potentials: {
      available: true,
      label: "案件",
      fields: ["potentialname"],
      values: {},
      referenceLabels: {},
      match: null,
    },
  },
});

const fieldsFor: Record<string, unknown[]> = {
  Accounts: [
    {
      name: "accountname",
      label: "顧客企業名",
      uitype: "2",
      type: "string",
      mandatory: true,
      readonly: false,
      editable: true,
      displaytype: "1",
      block: "",
      quickcreate: true,
      quickcreatesequence: 1,
    },
  ],
  Contacts: [
    {
      name: "lastname",
      label: "姓",
      uitype: "2",
      type: "string",
      mandatory: true,
      readonly: false,
      editable: true,
      displaytype: "1",
      block: "",
      quickcreate: true,
      quickcreatesequence: 1,
    },
    {
      name: "email",
      label: "メールアドレス",
      uitype: "13",
      type: "email",
      mandatory: false,
      readonly: false,
      editable: true,
      displaytype: "1",
      block: "",
      quickcreate: true,
      quickcreatesequence: 2,
    },
  ],
  Potentials: [
    {
      name: "potentialname",
      label: "案件名",
      uitype: "2",
      type: "string",
      mandatory: true,
      readonly: false,
      editable: true,
      displaytype: "1",
      block: "",
      quickcreate: true,
      quickcreatesequence: 1,
    },
  ],
  Leads: [],
};

let info: ConvertLeadInfo;
let saveResult: ConvertSaveResult;
let savedBodies: URLSearchParams[];

function jsonResponse(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

beforeEach(() => {
  info = baseInfo();
  saveResult = { converted: false, errors: [] };
  savedBodies = [];
  vi.stubGlobal(
    "fetch",
    vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      const url = String(input);
      if (init?.method === "POST") {
        const body = new URLSearchParams(String(init.body));
        savedBodies.push(body);
        return jsonResponse(saveResult);
      }
      const params = new URLSearchParams(url.split("?")[1] ?? "");
      const api = params.get("api");
      if (api === "GetTranslations") {
        return jsonResponse({ success: false, error: { message: "no" } }, 500);
      }
      if (api === "ConvertLeadInfo" && params.get("mode") === "summary") {
        return jsonResponse({
          id: 5,
          module: "Accounts",
          label: "サンプル株式会社",
          accountId: null,
          accountLabel: null,
          fields: [{ label: "電話", value: "03-0000-0000" }],
        });
      }
      if (api === "ConvertLeadInfo") {
        return jsonResponse(info);
      }
      if (api === "GetFields") {
        const module = params.get("module") ?? "";
        return jsonResponse({
          module,
          totalFields: 0,
          fields: fieldsFor[module] ?? [],
          timestamp: "",
        });
      }
      return jsonResponse({});
    }),
  );
});

afterEach(() => {
  vi.unstubAllGlobals();
});

async function renderConvert(onConverted = vi.fn()) {
  render(<LeadConvert recordId={10} isOpen onConverted={onConverted} />);
  await screen.findByTestId("lead-convert-section-Accounts");
  return onConverted;
}

describe("LeadConvert", () => {
  it("判定に当たった企業は警告を出し、既存として最初からセットする", async () => {
    await renderConvert();

    const section = screen.getByTestId("lead-convert-section-Accounts");
    expect(
      within(section).getByTestId("lead-convert-match-Accounts"),
    ).toBeInTheDocument();
    const existing = within(section).getByRole("radio", {
      name: "LBL_CONVERT_MODE_EXISTING",
    });
    expect(existing).toBeChecked();
    expect(
      await within(section).findByTestId("lead-convert-summary-Accounts"),
    ).toHaveTextContent("03-0000-0000");
  });

  it("新規作成に切り替えると入力欄が出て、昇格では新規作成として送る", async () => {
    const user = userEvent.setup();
    await renderConvert();
    const section = screen.getByTestId("lead-convert-section-Accounts");

    await user.click(
      within(section).getByRole("radio", { name: "LBL_CONVERT_MODE_CREATE" }),
    );
    expect(
      within(section).queryByTestId("lead-convert-match-Accounts"),
    ).not.toBeInTheDocument();
    expect(
      within(section).getByDisplayValue("サンプル株式会社"),
    ).toBeInTheDocument();

    await user.click(screen.getByTestId("lead-convert-submit"));
    await waitFor(() => expect(savedBodies).toHaveLength(1));
    const payload = JSON.parse(savedBodies[0].get("data") ?? "{}");
    expect(payload.modules.Accounts).toEqual({
      mode: "create",
      values: { accountname: "サンプル株式会社" },
    });
    expect(payload.modules.Contacts.mode).toBe("create");
    expect(payload.transferTo).toBe("Contacts");
  });

  it("既存企業に紐づけたまま昇格すると既存IDを送る", async () => {
    const user = userEvent.setup();
    await renderConvert();

    await user.click(screen.getByTestId("lead-convert-submit"));
    await waitFor(() => expect(savedBodies).toHaveLength(1));
    const payload = JSON.parse(savedBodies[0].get("data") ?? "{}");
    expect(payload.modules.Accounts).toEqual({
      mode: "existing",
      existingId: 5,
      values: {},
    });
  });

  it("画面に出していない項目のエラーは項目を追加表示してその場で直せる", async () => {
    const user = userEvent.setup();
    saveResult = {
      converted: false,
      errors: [
        {
          module: "Contacts",
          field: "email",
          label: "メールアドレス",
          message: "メールアドレスの形式が正しくありません",
          hidden: true,
          editable: true,
        },
      ],
    };
    await renderConvert();

    await user.click(screen.getByTestId("lead-convert-submit"));

    const section = screen.getByTestId("lead-convert-section-Contacts");
    expect(
      await within(section).findByText(
        "メールアドレスの形式が正しくありません",
      ),
    ).toBeInTheDocument();
    expect(
      within(section).getByText("LBL_CONVERT_ADDED_FIELD"),
    ).toBeInTheDocument();
    expect(screen.getByTestId("lead-convert-errors")).toHaveTextContent(
      "LBL_CONVERT_FIX_FIELDS",
    );
  });

  it("編集できない項目や重複のエラーは全体のエラーとして出す", async () => {
    const user = userEvent.setup();
    saveResult = {
      converted: false,
      errors: [
        {
          module: "Contacts",
          field: "secret",
          label: "秘密",
          message: "秘密は必須です（編集権限がないため修正できません）",
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
    };
    await renderConvert();

    await user.click(screen.getByTestId("lead-convert-submit"));

    const alert = await screen.findByTestId("lead-convert-errors");
    expect(alert).toHaveTextContent(
      "秘密は必須です（編集権限がないため修正できません）",
    );
    expect(alert).toHaveTextContent("重複が見つかりました");
  });

  it("必須が空なら送信せずに画面でエラーを出す", async () => {
    const user = userEvent.setup();
    await renderConvert();
    const section = screen.getByTestId("lead-convert-section-Contacts");

    await user.clear(within(section).getByDisplayValue("山田"));
    await user.click(screen.getByTestId("lead-convert-submit"));

    expect(
      await within(section).findByText("姓は必須です"),
    ).toBeInTheDocument();
    expect(savedBodies).toHaveLength(0);
  });

  it("昇格に成功したら converted を通知する", async () => {
    const user = userEvent.setup();
    saveResult = {
      converted: true,
      errors: [],
      redirectUrl: "index.php?module=Accounts&view=Detail&record=5",
      entityIds: { Accounts: 5 },
    };
    const onConverted = await renderConvert();

    await user.click(screen.getByTestId("lead-convert-submit"));

    await waitFor(() =>
      expect(onConverted).toHaveBeenCalledWith({
        redirectUrl: "index.php?module=Accounts&view=Detail&record=5",
        entityIds: { Accounts: 5 },
      }),
    );
  });
});

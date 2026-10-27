import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { LeadMappingSettings } from "./LeadMappingSettings";
import type { LeadMappingInfo } from "../../types/leadMapping";

const moduleSettings = (label: string) => ({
  label,
  mappableFields: [] as LeadMappingInfo["leadFields"],
  inputFields: [] as LeadMappingInfo["modules"]["Accounts"]["inputFields"],
  matchableFields:
    [] as LeadMappingInfo["modules"]["Accounts"]["matchableFields"],
  layout: [] as string[],
  defaults: {} as Record<string, string>,
  matchRules: [] as string[][],
});

function settingsInfo(): LeadMappingInfo {
  return {
    mapping: [
      {
        id: 1,
        editable: false,
        lead: 10,
        Accounts: 20,
        Contacts: null,
        Potentials: null,
      },
      {
        id: 2,
        editable: true,
        lead: 11,
        Accounts: null,
        Contacts: 31,
        Potentials: null,
      },
    ],
    leadFields: [
      { id: 10, name: "company", label: "会社名", type: "string" },
      { id: 11, name: "email", label: "メールアドレス", type: "email" },
    ],
    restrictedFieldIds: [20],
    modules: {
      Accounts: {
        ...moduleSettings("顧客企業"),
        mappableFields: [
          { id: 20, name: "accountname", label: "顧客企業名", type: "string" },
          { id: 21, name: "email1", label: "メール", type: "email" },
        ],
        inputFields: [
          { name: "accountname", label: "顧客企業名", mandatory: true },
          { name: "industry", label: "業種", mandatory: false },
          { name: "phone", label: "電話", mandatory: false },
        ],
        matchableFields: [
          { name: "accountname", label: "顧客企業名", mandatory: true },
          { name: "phone", label: "電話", mandatory: false },
        ],
        layout: ["accountname", "industry"],
        matchRules: [["accountname"]],
      },
      Contacts: {
        ...moduleSettings("顧客担当者"),
        mappableFields: [
          { id: 31, name: "email", label: "メール", type: "email" },
        ],
      },
      Potentials: moduleSettings("案件"),
    },
  };
}

let posted: URLSearchParams[];

beforeEach(() => {
  posted = [];
  vi.stubGlobal(
    "fetch",
    vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      if (init?.method === "POST") {
        const body = new URLSearchParams(String(init.body));
        posted.push(body);
        return new Response(JSON.stringify({ saved: true, errors: [] }));
      }
      const params = new URLSearchParams(String(input).split("?")[1] ?? "");
      if (params.get("api") === "GetTranslations") {
        return new Response("{}", { status: 500 });
      }
      return new Response(JSON.stringify(settingsInfo()));
    }),
  );
});

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("LeadMappingSettings", () => {
  it("ロック行は変更も削除もできない", async () => {
    render(<LeadMappingSettings />);
    const rows = await screen.findAllByTestId("lead-mapping-row");
    const locked = rows.find((row) => row.dataset.locked === "true");
    expect(locked).toBeDefined();
    for (const select of within(locked as HTMLElement).getAllByRole(
      "combobox",
    )) {
      expect(select).toBeDisabled();
    }
    expect(
      within(locked as HTMLElement).queryByRole("button"),
    ).not.toBeInTheDocument();
  });

  it("昇格先はリードの項目と同じ型の項目だけ選べ、保存ではロック行を送らない", async () => {
    const user = userEvent.setup();
    render(<LeadMappingSettings />);
    const rows = await screen.findAllByTestId("lead-mapping-row");
    const editable = rows.find(
      (row) => row.dataset.locked === "false",
    ) as HTMLElement;
    const accountSelect = within(editable).getAllByRole("combobox")[1];
    const options = within(accountSelect)
      .getAllByRole("option")
      .map((o) => o.textContent);
    expect(options).toEqual(["LBL_CONVERT_SETTINGS_NONE", "メール"]);

    await user.selectOptions(accountSelect, "21");
    await user.click(screen.getByTestId("lead-mapping-save-mapping"));

    await waitFor(() => expect(posted).toHaveLength(1));
    expect(posted[0].get("mode")).toBe("saveMapping");
    expect(JSON.parse(posted[0].get("mapping") ?? "[]")).toEqual([
      { id: 2, lead: 11, Accounts: 21, Contacts: 31, Potentials: 0 },
    ]);
  });

  it("必須項目は昇格画面に出す項目から外せない", async () => {
    const user = userEvent.setup();
    render(<LeadMappingSettings />);
    await user.click(
      await screen.findByRole("tab", {
        name: "LBL_CONVERT_SETTINGS_TAB_LAYOUT",
      }),
    );

    const displayed = screen.getByTestId("lead-mapping-layout-displayed");
    const mandatory = within(displayed)
      .getByText("顧客企業名")
      .closest("li") as HTMLElement;
    expect(
      within(mandatory).getByRole("button", {
        name: /LBL_CONVERT_SETTINGS_REMOVE/,
      }),
    ).toBeDisabled();

    await user.click(
      screen.getByRole("button", { name: "LBL_CONVERT_SETTINGS_ADD 電話" }),
    );
    await user.click(screen.getByTestId("lead-mapping-save-layout"));
    await waitFor(() => expect(posted).toHaveLength(1));
    expect(posted[0].get("target")).toBe("Accounts");
    expect(JSON.parse(posted[0].get("fields") ?? "[]")).toEqual([
      "accountname",
      "industry",
      "phone",
    ]);
  });

  it("判定条件を追加して保存する", async () => {
    const user = userEvent.setup();
    render(<LeadMappingSettings />);
    await user.click(
      await screen.findByRole("tab", {
        name: "LBL_CONVERT_SETTINGS_TAB_MATCH",
      }),
    );

    await user.click(screen.getByTestId("lead-mapping-add-rule"));
    const rules = screen.getAllByTestId("lead-mapping-rule");
    await user.selectOptions(within(rules[1]).getByRole("combobox"), "phone");
    await user.click(screen.getByTestId("lead-mapping-save-match"));

    await waitFor(() => expect(posted).toHaveLength(1));
    expect(JSON.parse(posted[0].get("rules") ?? "[]")).toEqual([
      ["accountname"],
      ["phone"],
    ]);
  });
});

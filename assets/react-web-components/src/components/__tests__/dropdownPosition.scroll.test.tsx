import { describe, it, expect, vi, beforeAll, afterAll } from "vitest";
import { render, fireEvent, waitFor } from "@testing-library/react";
import type { ReactElement } from "react";
import { OwnerField } from "../OwnerField";
import { PicklistField } from "../PicklistField";
import { MultiPicklistField } from "../MultiPicklistField";
import { CurrencyListField } from "../CurrencyListField";
import { ReferenceField } from "../ReferenceField";
import { MultireferenceField } from "../MultireferenceField";
import { TimeComboBox } from "../ui/time-combobox";
import { CalendarForm } from "../QuickCreate/CalendarForm";

// CalendarForm は TranslationProvider 必須のため、キーをそのまま返す翻訳に差し替える
vi.mock("../../hooks/useTranslation", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../../hooks/useTranslation")>()),
  useTranslation: () => ({ t: (key: string) => key }),
}));

/**
 * 候補一覧は body 直下に position: fixed（画面基準）で描くため、
 * 入力欄の getBoundingClientRect()（画面基準）をそのまま使う必要がある。
 * ページをスクロールしていても、候補一覧は入力欄の真下に出ること。
 */

const RECT_BOTTOM = 30;
const RECT_LEFT = 10;
const SCROLL_Y = 200;
const SCROLL_X = 50;

beforeAll(() => {
  vi.spyOn(Element.prototype, "getBoundingClientRect").mockReturnValue({
    top: 0,
    left: RECT_LEFT,
    bottom: RECT_BOTTOM,
    right: RECT_LEFT + 200,
    width: 200,
    height: RECT_BOTTOM,
    x: RECT_LEFT,
    y: 0,
    toJSON: () => ({}),
  } as DOMRect);
  vi.stubGlobal(
    "fetch",
    vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true, result: [], records: [] }),
    }),
  );
  Object.defineProperty(window, "scrollY", {
    configurable: true,
    value: SCROLL_Y,
  });
  Object.defineProperty(window, "scrollX", {
    configurable: true,
    value: SCROLL_X,
  });
});

afterAll(() => {
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  Object.defineProperty(window, "scrollY", { configurable: true, value: 0 });
  Object.defineProperty(window, "scrollX", { configurable: true, value: 0 });
});

const noop = () => {};

type Case = {
  name: string;
  element: ReactElement;
  // 候補一覧を開く操作の対象（container 内の要素）
  trigger: (container: HTMLElement) => HTMLElement;
  open: "focus" | "click";
  // 入力欄と候補一覧の間に意図して空けている余白（px）
  gap?: number;
};

const textInputs = (container: HTMLElement) =>
  Array.from(
    container.querySelectorAll<HTMLInputElement>('input:not([type="hidden"])'),
  );

const cases: Case[] = [
  {
    name: "担当（OwnerField）",
    element: (
      <OwnerField
        name="assigned_user_id"
        label="担当"
        onChange={noop}
        fieldinfo={{ picklistvalues: { LBL_USERS: { "1": "テスト管理者" } } }}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "focus",
  },
  {
    name: "選択リスト（PicklistField）",
    element: (
      <PicklistField
        name="industry"
        label="業種"
        onChange={noop}
        options={[{ value: "テスト業種", label: "テスト業種" }]}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "focus",
  },
  {
    name: "複数選択リスト（MultiPicklistField）",
    element: (
      <MultiPicklistField
        name="cf_test_multi"
        label="テスト複数選択"
        onChange={noop}
        options={[{ value: "テストA", label: "テストA" }]}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "focus",
  },
  {
    name: "通貨（CurrencyListField）",
    element: (
      <CurrencyListField
        name="currency_id"
        label="通貨"
        onChange={noop}
        currencyList={{ "1": "テスト円" }}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "focus",
  },
  {
    name: "関連・検索候補（ReferenceField）",
    element: (
      <ReferenceField
        name="account_id"
        label="顧客企業"
        referenceModules={["Accounts"]}
        onChange={noop}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "focus",
  },
  {
    name: "関連・モジュール選択（ReferenceField）",
    element: (
      <ReferenceField
        name="parent_id"
        label="関連先"
        referenceModules={["Accounts", "Contacts"]}
        onChange={noop}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "click",
  },
  {
    name: "複数関連・検索候補（MultireferenceField）",
    element: (
      <MultireferenceField
        name="cf_test_multiref"
        label="テスト複数関連"
        referenceModules={["Accounts"]}
        onChange={noop}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "focus",
  },
  {
    name: "複数関連・モジュール選択（MultireferenceField）",
    element: (
      <MultireferenceField
        name="cf_test_multiref"
        label="テスト複数関連"
        referenceModules={["Accounts", "Contacts"]}
        onChange={noop}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "click",
  },
  {
    name: "時刻（TimeComboBox）",
    element: (
      <TimeComboBox
        value=""
        onChange={noop}
        timeOptions={[{ value: "09:00", label: "09:00" }]}
      />
    ),
    trigger: (c) => textInputs(c)[0],
    open: "focus",
    gap: 4,
  },
  {
    name: "参加者（CalendarForm）",
    element: (
      <CalendarForm
        activeTab="Events"
        onTabChange={noop}
        formData={{}}
        onFieldChange={noop}
        currentFields={[]}
        isSaving={false}
        successMessage={null}
        validationErrors={{}}
        availableUsers={[{ id: "1", name: "テスト参加者" }]}
        timeOptions={[]}
        parseDateTimeValue={() => ({ date: "", time: "" })}
        combineDateTimeValue={() => ""}
        parseReminderValue={() => ({ days: 0, hours: 0, minutes: 0 })}
        combineReminderValue={() => 0}
      />
    ),
    trigger: (c) =>
      c.querySelector<HTMLInputElement>(
        'input[placeholder="LBL_SEARCH_USERS_PLACEHOLDER"]',
      )!,
    open: "focus",
  },
];

describe("ページをスクロールした状態で候補一覧を開く", () => {
  it.each(cases)(
    "$name: 入力欄の真下に出る",
    async ({ element, trigger, open, gap = 0 }) => {
      const { container, unmount } = render(element);
      const target = trigger(container);
      if (open === "focus") {
        fireEvent.focus(target);
      } else {
        fireEvent.click(target);
      }

      const dropdown = await waitFor(() => {
        const el =
          document.body.querySelector<HTMLElement>(":scope > div.fixed");
        expect(el).not.toBeNull();
        return el!;
      });

      expect(dropdown.style.top).toBe(`${RECT_BOTTOM + gap}px`);
      expect(dropdown.style.left).toBe(`${RECT_LEFT}px`);
      unmount();
    },
  );
});

import { describe, it, expect, vi, beforeAll } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { OwnerField } from "../OwnerField";

// jsdom は要素の大きさを持たない。ドロップダウンは入力欄の位置から
// 描画位置を決めるため、実寸を返すようにしておく
beforeAll(() => {
  Element.prototype.getBoundingClientRect = function () {
    return {
      top: 0,
      left: 0,
      bottom: 30,
      right: 200,
      width: 200,
      height: 30,
      x: 0,
      y: 0,
      toJSON: () => ({}),
    } as DOMRect;
  };
});

// TranslationProvider の外では t() がキーをそのまま返すため、キー名で渡す
const FIELDINFO = {
  picklistvalues: {
    LBL_USERS: { "1": "管理者", "5": "佐藤", "6": "鈴木" },
    LBL_GROUPS: { "3": "営業部" },
  },
};

function openWithValue(value: string) {
  render(
    <OwnerField
      name="assigned_user_id"
      label="担当"
      value={value}
      onChange={vi.fn()}
      fieldinfo={FIELDINFO}
    />,
  );
  const input = screen.getByRole("textbox");
  fireEvent.focus(input);
  return input;
}

function optionTextsWithClass(className: string): (string | null)[] {
  return ["管理者", "佐藤", "鈴木", "営業部"]
    .map((name) => screen.getByText(name))
    .filter((el) => el.className.includes(className))
    .map((el) => el.textContent);
}

describe("OwnerField - キー操作でのハイライト", () => {
  it("選択済みで矢印キーを動かしても、選択中の値はハイライトされたまま残らない", () => {
    const input = openWithValue("6");
    fireEvent.keyDown(input, { key: "ArrowDown" });

    expect(optionTextsWithClass("bg-blue-100")).toEqual(["佐藤"]);
  });

  it("選択中の値は太字で分かる", () => {
    openWithValue("6");

    expect(optionTextsWithClass("font-semibold")).toEqual(["鈴木"]);
  });

  it("グループを選択済みでも、背景色は移動先だけで選択中のグループは太字", () => {
    const input = openWithValue("3");
    fireEvent.keyDown(input, { key: "ArrowDown" });

    expect(optionTextsWithClass("bg-blue-100")).toEqual(["佐藤"]);
    expect(optionTextsWithClass("font-semibold")).toEqual(["営業部"]);
  });
});

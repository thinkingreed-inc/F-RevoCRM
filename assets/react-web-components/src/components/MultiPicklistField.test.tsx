import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { MultiPicklistField } from "./MultiPicklistField";

const OPTIONS = [
  { value: "A", label: "選択肢A" },
  { value: "B", label: "選択肢B" },
  { value: "C", label: "選択肢C" },
];

describe("MultiPicklistField - checkboxモード (#1677)", () => {
  it("auto かつ選択肢数が閾値以下のときチェックボックス群を描画する", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value=""
        onChange={vi.fn()}
        options={OPTIONS}
        threshold={100}
      />,
    );
    // 各選択肢がチェックボックスとして出る
    expect(screen.getAllByRole("checkbox")).toHaveLength(3);
    // チェックボックスモードでは検索テキストボックスは出さない
    expect(screen.queryByRole("textbox")).not.toBeInTheDocument();
  });

  it("現在値に応じて checked 状態が反映される", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value="A |##| C"
        onChange={vi.fn()}
        options={OPTIONS}
      />,
    );
    const a = screen.getByRole("checkbox", { name: /選択肢A/ });
    const b = screen.getByRole("checkbox", { name: /選択肢B/ });
    const c = screen.getByRole("checkbox", { name: /選択肢C/ });
    expect(a).toBeChecked();
    expect(b).not.toBeChecked();
    expect(c).toBeChecked();
  });

  it("未選択のチェックボックスをクリックすると値が追加される", () => {
    const onChange = vi.fn();
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value="A"
        onChange={onChange}
        options={OPTIONS}
      />,
    );
    fireEvent.click(screen.getByRole("checkbox", { name: /選択肢C/ }));
    expect(onChange).toHaveBeenCalledWith("cf_x", "A |##| C");
  });

  it("選択済みのチェックボックスをクリックすると値が除外される", () => {
    const onChange = vi.fn();
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value="A |##| B"
        onChange={onChange}
        options={OPTIONS}
      />,
    );
    fireEvent.click(screen.getByRole("checkbox", { name: /選択肢A/ }));
    expect(onChange).toHaveBeenCalledWith("cf_x", "B");
  });

  it("disabled のときチェックボックスが操作不可になる", () => {
    const onChange = vi.fn();
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value=""
        onChange={onChange}
        options={OPTIONS}
        disabled
      />,
    );
    const a = screen.getByRole("checkbox", { name: /選択肢A/ });
    expect(a).toBeDisabled();
    fireEvent.click(a);
    expect(onChange).not.toHaveBeenCalled();
  });
});

describe("MultiPicklistField - 閾値による自動切替 (#1677)", () => {
  it("選択肢数が閾値を超えると従来のドロップダウン(検索入力)になる", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value=""
        onChange={vi.fn()}
        options={OPTIONS}
        threshold={2}
      />,
    );
    // ドロップダウンモードでは検索テキストボックスが出る／チェックボックスは出ない
    expect(screen.getByRole("textbox")).toBeInTheDocument();
    expect(screen.queryByRole("checkbox")).not.toBeInTheDocument();
  });

  it("renderMode=checkbox を明示すると件数に関わらずチェックボックス", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value=""
        onChange={vi.fn()}
        options={OPTIONS}
        threshold={1}
        renderMode="checkbox"
      />,
    );
    expect(screen.getAllByRole("checkbox")).toHaveLength(3);
  });

  it("placeholder を渡すとドロップダウンの検索入力に反映される", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value=""
        onChange={vi.fn()}
        options={OPTIONS}
        threshold={1}
        renderMode="dropdown"
        placeholder="オプションの選択"
      />,
    );
    expect(screen.getByRole("textbox")).toHaveAttribute(
      "placeholder",
      "オプションの選択",
    );
  });

  it("renderMode=dropdown を明示すると件数に関わらずドロップダウン", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value=""
        onChange={vi.fn()}
        options={OPTIONS}
        threshold={100}
        renderMode="dropdown"
      />,
    );
    expect(screen.getByRole("textbox")).toBeInTheDocument();
  });
});

describe("MultiPicklistField - 隠しinput制御 (#1677)", () => {
  it("renderHiddenInput=false のとき name付き hidden input を出さない", () => {
    const { container } = render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value="A"
        onChange={vi.fn()}
        options={OPTIONS}
        renderHiddenInput={false}
      />,
    );
    expect(
      container.querySelector('input[type="hidden"][name="cf_x"]'),
    ).toBeNull();
  });

  it("既定(renderHiddenInput省略)では hidden input を出す(QuickCreate後方互換)", () => {
    const { container } = render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value="A"
        onChange={vi.fn()}
        options={OPTIONS}
      />,
    );
    const hidden = container.querySelector(
      'input[type="hidden"][name="cf_x"]',
    ) as HTMLInputElement | null;
    expect(hidden).not.toBeNull();
    expect(hidden?.value).toBe("A");
  });

  it("色(color)指定時にチェックボックス行へ色スウォッチを表示する", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="テスト複数選択"
        value=""
        onChange={vi.fn()}
        options={[{ value: "A", label: "選択肢A", color: "#ff0000" }]}
      />,
    );
    const swatch = screen.getByTestId("mpf-color-A");
    expect(swatch).toBeInTheDocument();
  });
});

describe("MultiPicklistField - id/testid の安全化・空白値 (#1677 M3)", () => {
  const SP = [
    { value: "North America", label: "北米" },
    { value: "South America", label: "南米" },
  ];

  it("値に空白を含んでも checkbox id に空白が入らない(有効なHTML id)", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="地域"
        value=""
        onChange={vi.fn()}
        options={SP}
      />,
    );
    const cb = screen.getByRole("checkbox", { name: /北米/ });
    expect(cb.id).not.toMatch(/\s/);
  });

  it("空白を含む値の色スウォッチ data-testid が正規化される", () => {
    render(
      <MultiPicklistField
        name="cf_x"
        label="地域"
        value=""
        onChange={vi.fn()}
        options={[{ value: "North America", label: "北米", color: "#ff0000" }]}
      />,
    );
    expect(screen.getByTestId("mpf-color-North_America")).toBeInTheDocument();
  });

  it("空白を含む値を選択しても onChange は生の値を返す(往復不変)", () => {
    const onChange = vi.fn();
    render(
      <MultiPicklistField
        name="cf_x"
        label="地域"
        value=""
        onChange={onChange}
        options={SP}
      />,
    );
    fireEvent.click(screen.getByRole("checkbox", { name: /北米/ }));
    expect(onChange).toHaveBeenCalledWith("cf_x", "North America");
  });
});

import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { PicklistFieldView } from "../PicklistFieldView";
import type { PicklistWebComponentOption } from "@/types/picklistField";

const options: PicklistWebComponentOption[] = [
  { value: "open", label: "対応中", color: "#ff0000" },
  { value: "pending", label: "保留", color: null },
  { value: "done", label: "完了", color: "#00ff00" },
];

const baseProps = {
  fieldName: "status",
  options,
  columns: 1,
  mandatory: false,
  disabled: false,
};

describe("PicklistFieldView - checkbox（複数）", () => {
  it("選択追加で並び順を保った配列を返す", () => {
    const onChange = vi.fn();
    render(
      <PicklistFieldView
        {...baseProps}
        selected={["done"]}
        multiple
        effectiveStyle="checkbox"
        onChange={onChange}
      />,
    );
    // 「対応中」をチェック → options 順（open, done）で返る
    fireEvent.click(screen.getByText("対応中"));
    expect(onChange).toHaveBeenCalledWith(["open", "done"]);
  });

  it("選択解除で配列から除外", () => {
    const onChange = vi.fn();
    render(
      <PicklistFieldView
        {...baseProps}
        selected={["open", "done"]}
        multiple
        effectiveStyle="checkbox"
        onChange={onChange}
      />,
    );
    fireEvent.click(screen.getByText("完了"));
    expect(onChange).toHaveBeenCalledWith(["open"]);
  });

  it("checkbox が描画される", () => {
    render(
      <PicklistFieldView
        {...baseProps}
        selected={[]}
        multiple
        effectiveStyle="checkbox"
        onChange={vi.fn()}
      />,
    );
    const inputs = screen.getAllByRole("checkbox");
    expect(inputs).toHaveLength(3);
  });

  it("色付き選択肢に背景色が反映される", () => {
    render(
      <PicklistFieldView
        {...baseProps}
        selected={[]}
        multiple
        effectiveStyle="checkbox"
        onChange={vi.fn()}
      />,
    );
    const chip = screen.getByText("対応中");
    expect(chip).toHaveStyle({ backgroundColor: "#ff0000" });
  });
});

describe("PicklistFieldView - radio（単一）", () => {
  it("ラジオ選択は単一要素配列を返す（排他）", () => {
    const onChange = vi.fn();
    render(
      <PicklistFieldView
        {...baseProps}
        selected={["open"]}
        multiple={false}
        effectiveStyle="radio"
        onChange={onChange}
      />,
    );
    fireEvent.click(screen.getByText("完了"));
    expect(onChange).toHaveBeenCalledWith(["done"]);
  });

  it("radio が描画される", () => {
    render(
      <PicklistFieldView
        {...baseProps}
        selected={["open"]}
        multiple={false}
        effectiveStyle="radio"
        onChange={vi.fn()}
      />,
    );
    expect(screen.getAllByRole("radio")).toHaveLength(3);
  });

  it("必須でなければクリアボタンで空配列にできる", () => {
    const onChange = vi.fn();
    render(
      <PicklistFieldView
        {...baseProps}
        selected={["open"]}
        multiple={false}
        effectiveStyle="radio"
        mandatory={false}
        onChange={onChange}
      />,
    );
    fireEvent.click(screen.getByText("選択をクリア"));
    expect(onChange).toHaveBeenCalledWith([]);
  });

  it("必須ならクリアボタンを出さない", () => {
    render(
      <PicklistFieldView
        {...baseProps}
        selected={["open"]}
        multiple={false}
        effectiveStyle="radio"
        mandatory
        onChange={vi.fn()}
      />,
    );
    expect(screen.queryByText("選択をクリア")).toBeNull();
  });
});

describe("PicklistFieldView - 列数", () => {
  it("2列指定で sm:grid-cols-2 クラスが付く", () => {
    const { container } = render(
      <PicklistFieldView
        {...baseProps}
        selected={[]}
        multiple
        effectiveStyle="checkbox"
        columns={2}
        onChange={vi.fn()}
      />,
    );
    expect(container.querySelector(".sm\\:grid-cols-2")).not.toBeNull();
  });
});

describe("PicklistFieldView - dropdown 保険", () => {
  it("effectiveStyle=dropdown は select を描画", () => {
    render(
      <PicklistFieldView
        {...baseProps}
        selected={[]}
        multiple
        effectiveStyle="dropdown"
        onChange={vi.fn()}
      />,
    );
    expect(document.querySelector("select.fr-wc-select")).not.toBeNull();
  });
});

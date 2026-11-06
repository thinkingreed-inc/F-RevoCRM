import { describe, it, expect, vi, afterEach } from "vitest";
import {
  act,
  cleanup,
  fireEvent,
  render,
  screen,
} from "@testing-library/react";
import { YearMonthField } from "../YearMonthField";

const labels = {
  LBL_YEARMONTH_PLACEHOLDER: "年月を選択",
  LBL_YEARMONTH_THIS_MONTH: "今月",
  LBL_YEARMONTH_CLEAR: "クリア",
  LBL_YEARMONTH_PREV_YEAR: "前の年",
  LBL_YEARMONTH_NEXT_YEAR: "次の年",
  LBL_YEARMONTH_YEAR_FORMAT: "{Y}年",
  LBL_YEARMONTH_MONTH_3: "3月",
  LBL_YEARMONTH_MONTH_4: "4月",
};

afterEach(async () => {
  // Radix の Popover が残した処理を、描画の後始末の後に流し切る
  cleanup();
  await act(async () => {});
});

describe("YearMonthField", () => {
  it("詳細では値を文字で出す", () => {
    render(
      <YearMonthField
        mode="detail"
        value="2026-10-01"
        displayFormat="{Y}年{M}月"
      />,
    );
    expect(screen.getByText("2026年10月")).toBeTruthy();
  });

  it("未入力ならプレースホルダを出す", () => {
    render(<YearMonthField mode="edit" value="" labels={labels} />);
    expect(screen.getByText("年月を選択")).toBeTruthy();
  });

  it("月を選ぶと月初日で onChange を呼ぶ", async () => {
    const onChange = vi.fn();
    render(
      <YearMonthField
        mode="edit"
        value="2026-10-01"
        labels={labels}
        onChange={onChange}
      />,
    );
    fireEvent.click(screen.getByRole("button", { name: /2026年10月/ }));
    expect(await screen.findByTestId("yearmonth-year")).toBeTruthy();
    fireEvent.click(screen.getByText("3月"));
    expect(onChange).toHaveBeenCalledWith("2026-03-01");
  });

  it("年を切り替えて選べる", async () => {
    const onChange = vi.fn();
    render(
      <YearMonthField
        mode="edit"
        value="2026-10-01"
        labels={labels}
        onChange={onChange}
      />,
    );
    fireEvent.click(screen.getByRole("button", { name: /2026年10月/ }));
    await screen.findByTestId("yearmonth-year");
    fireEvent.click(screen.getByRole("button", { name: "前の年" }));
    expect(screen.getByTestId("yearmonth-year").textContent).toBe("2025年");
    fireEvent.click(screen.getByText("4月"));
    expect(onChange).toHaveBeenCalledWith("2025-04-01");
  });

  it("任意項目はクリアで空文字にできる", () => {
    const onChange = vi.fn();
    render(
      <YearMonthField
        mode="edit"
        value="2026-10-01"
        labels={labels}
        onChange={onChange}
      />,
    );
    fireEvent.click(screen.getByRole("button", { name: "クリア" }));
    expect(onChange).toHaveBeenCalledWith("");
  });

  it("必須項目にはクリアを出さない", async () => {
    render(
      <YearMonthField
        mode="edit"
        value="2026-10-01"
        labels={labels}
        mandatory
      />,
    );
    expect(screen.queryByRole("button", { name: "クリア" })).toBeNull();
    fireEvent.click(screen.getByRole("button", { name: /2026年10月/ }));
    await screen.findByTestId("yearmonth-year");
    expect(screen.queryByText("クリア")).toBeNull();
  });

  it("読み取り専用なら開けない", () => {
    render(<YearMonthField mode="edit" value="" labels={labels} disabled />);
    expect((screen.getByRole("button") as HTMLButtonElement).disabled).toBe(
      true,
    );
  });
});

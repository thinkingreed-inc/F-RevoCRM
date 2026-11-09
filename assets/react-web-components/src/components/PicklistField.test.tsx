import { describe, it, expect, vi } from "vitest";
import { render, screen, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { PicklistField } from "./PicklistField";

const options = [
  { value: "a", label: "選択肢A" },
  { value: "b", label: "選択肢B" },
];

const sleep = (ms: number) =>
  act(() => new Promise<void>((resolve) => setTimeout(resolve, ms)));

describe("PicklistField 候補のハイライト", () => {
  it("未選択で開いた直後は、どの候補も選択中の色になっていない", async () => {
    const user = userEvent.setup();
    render(
      <PicklistField
        name="f"
        label="項目"
        options={options}
        onChange={vi.fn()}
      />,
    );

    await user.click(screen.getByRole("textbox"));

    expect(await screen.findByText("選択肢A")).not.toHaveClass("bg-blue-100");
    expect(screen.getByText("選択肢B")).not.toHaveClass("bg-blue-100");
  });

  it("マウスを乗せた候補にハイライトが移る", async () => {
    const user = userEvent.setup();
    render(
      <PicklistField
        name="f"
        label="項目"
        options={options}
        onChange={vi.fn()}
      />,
    );

    await user.click(screen.getByRole("textbox"));
    await user.hover(await screen.findByText("選択肢B"));

    expect(screen.getByText("選択肢B")).toHaveClass("bg-blue-100");
    expect(screen.getByText("選択肢A")).not.toHaveClass("bg-blue-100");
  });

  it("選択済みの値で開くと、その候補がハイライトされる", async () => {
    const user = userEvent.setup();
    render(
      <PicklistField
        name="f"
        label="項目"
        value="b"
        options={options}
        onChange={vi.fn()}
      />,
    );

    await user.click(screen.getByRole("textbox"));

    expect(await screen.findByText("選択肢B")).toHaveClass("bg-blue-100");
    expect(screen.getByText("選択肢A")).not.toHaveClass("bg-blue-100");
  });
});

describe("PicklistField 候補のマウス選択", () => {
  it("素早いクリックで選択できる", async () => {
    const onChange = vi.fn();
    const user = userEvent.setup();
    render(
      <PicklistField
        name="f"
        label="項目"
        options={options}
        onChange={onChange}
      />,
    );

    await user.click(screen.getByRole("textbox"));
    await user.click(await screen.findByText("選択肢B"));

    expect(onChange).toHaveBeenCalledWith("f", "b");
  });

  it("マウスボタンを押し込んだまま少し待ってから離しても選択できる", async () => {
    const onChange = vi.fn();
    const user = userEvent.setup();
    render(
      <PicklistField
        name="f"
        label="項目"
        options={options}
        onChange={onChange}
      />,
    );

    await user.click(screen.getByRole("textbox"));
    const item = await screen.findByText("選択肢B");

    // 押下（入力欄が blur する）→ 300ms 押しっぱなし → 離す
    await user.pointer({ keys: "[MouseLeft>]", target: item });
    await sleep(300);
    await user.pointer({ keys: "[/MouseLeft]", target: item });

    expect(onChange).toHaveBeenCalledWith("f", "b");
  });
});

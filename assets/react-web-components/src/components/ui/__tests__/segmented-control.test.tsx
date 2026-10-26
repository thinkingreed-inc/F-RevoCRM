import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { SegmentedControl } from "../segmented-control";

const options = [
  { value: "create", label: "新しく作成する" },
  { value: "existing", label: "既存のレコードに紐づける" },
  { value: "none", label: "作成しない", disabled: true },
] as const;

describe("SegmentedControl", () => {
  it("選択中の項目を aria-checked で示し、別の項目を押すと onChange を呼ぶ", async () => {
    const onChange = vi.fn();
    render(
      <SegmentedControl
        ariaLabel="顧客企業"
        options={options}
        value="create"
        onChange={onChange}
      />,
    );

    expect(screen.getByRole("radio", { name: "新しく作成する" })).toBeChecked();
    expect(
      screen.getByRole("radio", { name: "既存のレコードに紐づける" }),
    ).not.toBeChecked();

    await userEvent.click(
      screen.getByRole("radio", { name: "既存のレコードに紐づける" }),
    );
    expect(onChange).toHaveBeenCalledWith("existing");
  });

  it("無効な項目は押せない", async () => {
    const onChange = vi.fn();
    render(
      <SegmentedControl
        ariaLabel="顧客企業"
        options={options}
        value="create"
        onChange={onChange}
      />,
    );

    const none = screen.getByRole("radio", { name: "作成しない" });
    expect(none).toBeDisabled();
    await userEvent.click(none);
    expect(onChange).not.toHaveBeenCalled();
  });
});

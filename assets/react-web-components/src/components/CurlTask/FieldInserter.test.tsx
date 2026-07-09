import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { FieldInserter } from "./FieldInserter";

describe("FieldInserter", () => {
  it("calls onInsert with $name when a field is chosen", async () => {
    const onInsert = vi.fn();
    render(
      <FieldInserter
        fields={[{ name: "subject", label: "件名" }]}
        onInsert={onInsert}
      />,
    );
    await userEvent.click(screen.getByRole("button", { name: /フィールド挿入/ }));
    await userEvent.click(await screen.findByText("件名"));
    expect(onInsert).toHaveBeenCalledWith("$subject");
  });

  it("is disabled when there are no fields", () => {
    render(<FieldInserter fields={[]} onInsert={() => {}} />);
    expect(screen.getByRole("button", { name: /フィールド挿入/ })).toBeDisabled();
  });
});

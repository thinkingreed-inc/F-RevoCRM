import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { PresetSelector } from "./PresetSelector";
import { applyPreset } from "./presets";

describe("PresetSelector", () => {
  it("applies immediately when no existing content", async () => {
    const onApply = vi.fn();
    render(<PresetSelector hasExistingContent={false} onApply={onApply} />);
    await userEvent.click(screen.getByRole("button", { name: /slack/i }));
    expect(onApply).toHaveBeenCalledWith(applyPreset("slack"));
  });

  it("asks for confirmation when content exists", async () => {
    const onApply = vi.fn();
    render(<PresetSelector hasExistingContent onApply={onApply} />);
    await userEvent.click(screen.getByRole("button", { name: /teams/i }));
    expect(onApply).not.toHaveBeenCalled();
    await userEvent.click(screen.getByRole("button", { name: /^OK$/ }));
    expect(onApply).toHaveBeenCalledWith(applyPreset("teams"));
  });
});

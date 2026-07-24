import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { JsonTemplateEditor } from "./JsonTemplateEditor";

describe("JsonTemplateEditor", () => {
  it("format button pretty-prints current value", async () => {
    const onChange = vi.fn();
    render(
      <JsonTemplateEditor
        value='{"a":1}'
        onChange={onChange}
        fields={[]}
        validate
      />,
    );
    await userEvent.click(screen.getByRole("button", { name: /整形|format/i }));
    expect(onChange).toHaveBeenCalledWith('{\n  "a": 1\n}');
  });

  it("shows invalid indicator for broken JSON", () => {
    render(
      <JsonTemplateEditor value="{" onChange={() => {}} fields={[]} validate />,
    );
    expect(screen.getByText(/不正|invalid/i)).toBeInTheDocument();
  });
});

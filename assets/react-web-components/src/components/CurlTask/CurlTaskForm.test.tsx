import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { CurlTaskForm } from "./CurlTaskForm";

describe("CurlTaskForm", () => {
  it("renders named inputs mirroring state for serializeFormData", () => {
    const { container } = render(
      <CurlTaskForm
        url="https://e.com"
        method="POST"
        headers=""
        body='{"a":1}'
        timeout="30"
        fieldsJson={[]}
      />,
    );
    expect(container.querySelector('input[name="url"]')).toHaveValue(
      "https://e.com",
    );
    expect(container.querySelector('input[name="timeout"]')).toHaveValue(30);
    expect(container.querySelector('select[name="method"]')).toHaveValue(
      "POST",
    );
    const bodyInput = container.querySelector(
      '[name="body"]',
    ) as HTMLInputElement;
    expect(bodyInput).not.toBeNull();
    expect(bodyInput.value).toBe('{"a":1}');
  });

  it("stringifies object body (createWebComponent auto-parse guard)", () => {
    const { container } = render(
      // @ts-expect-error object allowed per interface
      <CurlTaskForm body={{ a: 1 }} fieldsJson={[]} />,
    );
    const bodyInput = container.querySelector(
      '[name="body"]',
    ) as HTMLInputElement;
    expect(JSON.parse(bodyInput.value)).toEqual({ a: 1 });
  });

  it("renders labels from labelsJson (i18n)", () => {
    render(
      <CurlTaskForm
        body=""
        fieldsJson={[]}
        labelsJson='{"url":"Request URL","testSend":"Test Send","format":"Format"}'
      />,
    );
    expect(screen.getByText("Request URL")).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: /Test Send/ }),
    ).toBeInTheDocument();
    // 未指定キーは日本語デフォルトにフォールバック
    expect(screen.getByText("HTTPメソッド")).toBeInTheDocument();
  });

  it("parses fieldsJson passed as string", () => {
    const { container } = render(
      <CurlTaskForm
        body=""
        fieldsJson='[{"name":"subject","label":"件名"}]'
      />,
    );
    // フィールド挿入ボタンが有効（fields>0）であること
    const buttons = Array.from(container.querySelectorAll("button")).filter(
      (b) => /フィールド挿入/.test(b.textContent || ""),
    );
    expect(buttons.length).toBeGreaterThan(0);
    expect(buttons.every((b) => !b.hasAttribute("disabled"))).toBe(true);
  });
});

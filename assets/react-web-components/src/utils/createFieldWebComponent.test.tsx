import { describe, it, expect, vi, afterEach } from "vitest";
import { waitFor } from "@testing-library/react";
import {
  createFieldWebComponent,
  parseFieldProps,
  updateFieldInput,
  type FieldWebComponentRenderProps,
} from "./createFieldWebComponent";
import type { WebComponentFieldProps } from "@/types/webComponentField";

const baseProps: WebComponentFieldProps = {
  mode: "edit",
  module: "Accounts",
  fieldName: "cf_1",
  fieldLabel: "ファイル",
  recordId: 0,
  mandatory: false,
  readonly: false,
  inputId: "input_cf_1",
};

describe("parseFieldProps", () => {
  it("JSON 文字列・オブジェクトのどちらも受け取る", () => {
    expect(parseFieldProps(JSON.stringify(baseProps))).toEqual(baseProps);
    expect(parseFieldProps(baseProps)).toEqual(baseProps);
  });

  it("壊れた値は null にする", () => {
    expect(parseFieldProps("{broken")).toBeNull();
    expect(parseFieldProps("")).toBeNull();
    expect(parseFieldProps(undefined)).toBeNull();
    expect(parseFieldProps("123")).toBeNull();
  });
});

describe("updateFieldInput", () => {
  afterEach(() => {
    document.body.innerHTML = "";
  });

  it("hidden 入力を書き換えて change を発火する", () => {
    document.body.innerHTML =
      '<form><input type="hidden" id="input_cf_1" name="cf_1" value="" /></form>';
    const input = document.getElementById("input_cf_1") as HTMLInputElement;
    const onChange = vi.fn();
    document.querySelector("form")!.addEventListener("change", onChange);

    updateFieldInput("input_cf_1", "45");

    expect(input.value).toBe("45");
    expect(onChange).toHaveBeenCalledTimes(1);
  });

  it("入力が無ければ何もしない", () => {
    expect(() => updateFieldInput("missing", "1")).not.toThrow();
    expect(() => updateFieldInput(undefined, "1")).not.toThrow();
  });
});

describe("createFieldWebComponent", () => {
  it("props 属性を渡し、setValue で hidden 入力を書き換える", async () => {
    const Probe = ({
      props,
      setValue,
    }: FieldWebComponentRenderProps<WebComponentFieldProps>) => (
      <button type="button" data-testid="probe" onClick={() => setValue("88")}>
        {props.fieldName}:{props.mode}
      </button>
    );
    createFieldWebComponent(Probe, "fr-field-probe");

    document.body.innerHTML =
      '<input type="hidden" id="input_cf_1" value="" />' +
      `<fr-field-probe props='${JSON.stringify(baseProps)}'></fr-field-probe>`;

    const button = await waitFor(() => {
      const found = document.querySelector('[data-testid="probe"]');
      expect(found).not.toBeNull();
      return found as HTMLButtonElement;
    });
    expect(button.textContent).toBe("cf_1:edit");

    button.click();
    expect(
      (document.getElementById("input_cf_1") as HTMLInputElement).value,
    ).toBe("88");
  });
});

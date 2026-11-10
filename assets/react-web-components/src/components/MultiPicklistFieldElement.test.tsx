import { describe, it, expect, afterEach } from "vitest";
import { fireEvent, waitFor, within } from "@testing-library/react";
import { registerMultiPicklistFieldElement } from "./MultiPicklistFieldElement";

registerMultiPicklistFieldElement();

interface Opt {
  value: string;
  label: string;
  selected?: boolean;
  color?: string;
}

/**
 * <multi-picklist-field><select multiple>...</select></multi-picklist-field> を
 * DOMで組み立てて body に接続（connectedCallback 発火）する。
 */
function mountField(
  options: Opt[],
  cfg: {
    mandatory?: boolean;
    threshold?: number;
    disabled?: boolean;
    placeholder?: string;
    label?: string;
  } = {},
) {
  const el = document.createElement("multi-picklist-field");
  if (cfg.threshold != null)
    el.setAttribute("threshold", String(cfg.threshold));
  if (cfg.placeholder != null) el.setAttribute("placeholder", cfg.placeholder);
  if (cfg.label != null) el.setAttribute("label", cfg.label);
  const select = document.createElement("select");
  select.multiple = true;
  select.name = "cf_x[]";
  if (cfg.mandatory) select.setAttribute("data-rule-required", "true");
  if (cfg.disabled) select.disabled = true;
  for (const o of options) {
    const opt = document.createElement("option");
    opt.value = o.value;
    opt.textContent = o.label;
    if (o.selected) opt.selected = true;
    if (o.color) opt.dataset.color = o.color;
    select.appendChild(opt);
  }
  el.appendChild(select);
  document.body.appendChild(el);
  return { el, select };
}

const OPTS: Opt[] = [
  { value: "A", label: "選択肢A" },
  { value: "B", label: "選択肢B" },
  { value: "C", label: "選択肢C" },
];

afterEach(() => {
  document.body.replaceChildren();
});

describe("MultiPicklistFieldElement (#1677 カスタム要素)", () => {
  it("子selectを読み取りチェックボックスを描画する(閾値以下)", async () => {
    const { el } = mountField(OPTS, { threshold: 100 });
    await waitFor(() =>
      expect(within(el).getAllByRole("checkbox")).toHaveLength(3),
    );
  });

  it("選択済みoptionがcheckedに反映される", async () => {
    const { el } = mountField(
      [
        { value: "A", label: "選択肢A", selected: true },
        { value: "B", label: "選択肢B" },
        { value: "C", label: "選択肢C", selected: true },
      ],
      { threshold: 100 },
    );
    await waitFor(() =>
      expect(
        within(el).getByRole("checkbox", { name: /選択肢A/ }),
      ).toBeChecked(),
    );
    expect(
      within(el).getByRole("checkbox", { name: /選択肢B/ }),
    ).not.toBeChecked();
    expect(within(el).getByRole("checkbox", { name: /選択肢C/ })).toBeChecked();
  });

  it("チェックで native select に値が同期され change が発火する", async () => {
    const { el, select } = mountField(OPTS, { threshold: 100 });
    let changeFired = 0;
    select.addEventListener("change", () => {
      changeFired++;
    });
    const cbA = await within(el).findByRole("checkbox", { name: /選択肢A/ });
    fireEvent.click(cbA);
    expect(changeFired).toBeGreaterThan(0);
    // native select は選択値(A)のみを保持する
    expect(Array.from(select.selectedOptions).map((o) => o.value)).toEqual([
      "A",
    ]);
    expect(select.options).toHaveLength(1);
  });

  it("全解除で native select は 0 件になる(必須検証クラッシュ回避の肝)", async () => {
    const { el, select } = mountField(
      [{ value: "A", label: "選択肢A", selected: true }],
      { mandatory: true, threshold: 100 },
    );
    // 初期化時点で選択値のみ(=A 1件)
    await waitFor(() => expect(select.options.length).toBe(1));
    const cbA = within(el).getByRole("checkbox", { name: /選択肢A/ });
    fireEvent.click(cbA); // 解除
    expect(select.options).toHaveLength(0);
    expect(Array.from(select.selectedOptions)).toHaveLength(0);
  });

  it("選択肢が閾値超のときはドロップダウン(検索入力)になる", async () => {
    const many: Opt[] = Array.from({ length: 5 }, (_, i) => ({
      value: "V" + i,
      label: "値" + i,
    }));
    const { el } = mountField(many, { threshold: 2 });
    await waitFor(() =>
      expect(within(el).getByRole("textbox")).toBeInTheDocument(),
    );
    expect(within(el).queryByRole("checkbox")).not.toBeInTheDocument();
  });

  it("native select は視覚的に隠されるが :hidden(type=hidden/display:none)にはしない", async () => {
    const { el, select } = mountField(OPTS, { threshold: 100 });
    await waitFor(() =>
      expect(within(el).getAllByRole("checkbox").length).toBe(3),
    );
    expect(select.style.opacity).toBe("0");
    expect(select.style.position).toBe("absolute");
    expect(select.style.display).not.toBe("none");
    expect(select.type).not.toBe("hidden");
  });

  it("option の data-color を読み取り色スウォッチを表示する (#1677 M5)", async () => {
    const { el } = mountField(
      [{ value: "A", label: "選択肢A", color: "#ff0000" }],
      { threshold: 100 },
    );
    await waitFor(() =>
      expect(within(el).getByTestId("mpf-color-A")).toBeInTheDocument(),
    );
  });

  it("placeholder 属性がドロップダウンの検索入力に反映される (#1677 M5)", async () => {
    const many: Opt[] = Array.from({ length: 5 }, (_, i) => ({
      value: "V" + i,
      label: "値" + i,
    }));
    const { el } = mountField(many, {
      threshold: 2,
      placeholder: "オプションの選択",
    });
    await waitFor(() =>
      expect(within(el).getByRole("textbox")).toHaveAttribute(
        "placeholder",
        "オプションの選択",
      ),
    );
  });

  it("label 属性が checkbox グループの aria-label になる (#1677 M5)", async () => {
    const { el } = mountField(OPTS, { threshold: 100, label: "地域" });
    await waitFor(() =>
      expect(within(el).getByRole("group")).toHaveAttribute(
        "aria-label",
        "地域",
      ),
    );
  });

  it("readonly(disabled) select のときチェックボックスが操作不可になる (#1677 M5)", async () => {
    const { el, select } = mountField(OPTS, {
      threshold: 100,
      disabled: true,
    });
    const cbA = await within(el).findByRole("checkbox", { name: /選択肢A/ });
    expect(cbA).toBeDisabled();
    fireEvent.click(cbA);
    // disabled なので変化せず、native select は選択値のみ(0件)のまま
    expect(Array.from(select.selectedOptions)).toHaveLength(0);
  });

  it("空白を含む値でも生の値のまま native select に同期される (#1677 M5)", async () => {
    const { el, select } = mountField(
      [{ value: "North America", label: "北米" }],
      { threshold: 100 },
    );
    const cb = await within(el).findByRole("checkbox", { name: /北米/ });
    fireEvent.click(cb);
    expect(Array.from(select.selectedOptions).map((o) => o.value)).toEqual([
      "North America",
    ]);
  });
});

import React, { useState } from "react";
import { createRoot, type Root } from "react-dom/client";
import { MultiPicklistField } from "./MultiPicklistField";

/**
 * <multi-picklist-field>（#1677）
 *
 * 標準Edit画面のネイティブ `<select multiple>`（uitype33）をラップし、
 * 選択肢が閾値以下ならチェックボックス、超える場合は従来ドロップダウンで描画する
 * カスタム要素。
 *
 * 設計意図:
 * - フォーム送信・必須バリデーションは **ネイティブselectに委ねる**（新機構を作らない）。
 * - selectは視覚的に隠すが `:hidden` にしない（jQuery Validateの `ignore:":hidden"` を避け、
 *   必須検証を生かすため）。display:none / visibility:hidden / type=hidden は使わない。
 * - UI操作時はselectの option.selected を更新し `change` を発火して既存ロジックを再評価させる。
 *
 * 使用例（Smarty）:
 *   <multi-picklist-field threshold="100">
 *     <select multiple name="cf_x[]" data-rule-required ...>...</select>
 *   </multi-picklist-field>
 */

interface ParsedOption {
  value: string;
  label: string;
  color?: string;
}

/**
 * React側ビュー: 値stateを保持し、変更をネイティブselectへコミットする。
 */
const MultiPicklistFieldElementView: React.FC<{
  name: string;
  label: string;
  options: ParsedOption[];
  initialValue: string;
  mandatory: boolean;
  disabled: boolean;
  threshold: number;
  placeholder?: string;
  onCommit: (values: string[]) => void;
}> = ({
  name,
  label,
  options,
  initialValue,
  mandatory,
  disabled,
  threshold,
  placeholder,
  onCommit,
}) => {
  const [value, setValue] = useState<string>(initialValue);

  return (
    <MultiPicklistField
      name={name}
      label={label}
      value={value}
      options={options}
      mandatory={mandatory}
      disabled={disabled}
      threshold={threshold}
      placeholder={placeholder}
      renderMode="auto"
      renderHiddenInput={false}
      showLabel={false}
      onChange={(_name, newValue) => {
        setValue(newValue);
        onCommit(newValue ? newValue.split(" |##| ") : []);
      }}
    />
  );
};

const VALUE_SEPARATOR = " |##| ";

export class MultiPicklistFieldElement extends HTMLElement {
  private root: Root | null = null;
  private container: HTMLDivElement | null = null;
  private select: HTMLSelectElement | null = null;

  connectedCallback() {
    // 多重初期化防止（カスタム要素は再接続でconnectedCallbackが複数回走り得る）
    if (this.container) return;

    const select = this.querySelector<HTMLSelectElement>("select");
    if (!select) {
      // selectが無ければ何もしない（素の状態を壊さない）
      return;
    }
    this.select = select;

    // name は "cf_x[]" 形式なので [] を除去（MultiPicklistField/表示用途）
    const name = select.name.replace(/\[\]$/, "");
    const label =
      this.getAttribute("label") ?? select.getAttribute("data-label") ?? "";
    const mandatory =
      select.getAttribute("data-rule-required") === "true" || select.required;
    const disabled = select.disabled;
    const thresholdAttr = this.getAttribute("threshold");
    const threshold =
      thresholdAttr !== null && !Number.isNaN(Number(thresholdAttr))
        ? Number(thresholdAttr)
        : 100;
    // ドロップダウン時のプレースホルダー。標準Edit画面では既存の単一ピックリストに
    // 合わせた文言(オプションの選択)を属性で受け取る。
    const placeholder = this.getAttribute("placeholder") || undefined;

    const options: ParsedOption[] = Array.from(select.options).map((opt) => ({
      value: opt.value,
      label: (opt.textContent ?? "").trim(),
      color: opt.dataset.color || undefined,
    }));

    const initialSelected = Array.from(select.selectedOptions).map(
      (opt) => opt.value,
    );
    const initialValue = initialSelected.join(VALUE_SEPARATOR);

    // ネイティブselectは視覚的に隠す（ただし :hidden にしない＝必須検証を生かす）
    this.hideNativeSelect(select);
    // ネイティブselectは「選択済みの値のみ」を保持する形へ初期化（下記 setSelectValues 参照）
    this.setSelectValues(initialSelected, false);

    this.container = document.createElement("div");
    this.container.className =
      "multi-picklist-field-wrapper web-components-wrapper";
    this.appendChild(this.container);

    this.root = createRoot(this.container);
    this.root.render(
      <React.StrictMode>
        <MultiPicklistFieldElementView
          name={name}
          label={label}
          options={options}
          initialValue={initialValue}
          mandatory={mandatory}
          disabled={disabled}
          threshold={threshold}
          placeholder={placeholder}
          onCommit={(values) => this.commitToSelect(values)}
        />
      </React.StrictMode>,
    );
  }

  disconnectedCallback() {
    if (this.root) {
      this.root.unmount();
      this.root = null;
    }
    // 生成した container を DOM から除去（disconnect→reconnect 時に空 container が残置
    // され再 init で二重追加されるのを防ぐ）
    if (this.container) {
      this.container.remove();
      this.container = null;
    }
  }

  /**
   * 選択値をネイティブselectへ反映し change を発火（既存バリデーション/依存ロジックを再評価）
   */
  private commitToSelect(values: string[]) {
    this.setSelectValues(values, true);
  }

  /**
   * ネイティブselectを「選択済みの値のみ」を保持する形に再構築する。
   *
   * 理由: 既存の validation.js errorPlacement は editViewPageDiv 配下の要素で
   * `positionContainer = element[0]` とする。element が raw <select> の場合
   * `element[0].length` が option 数となり、qtip が jQuery オブジェクトと誤認して
   * `container.eq is not a function` でクラッシュし必須検証のブロックが壊れる
   * （select2付き従来項目は select2コンテナへ再ターゲットされ回避していた）。
   * 選択値のみを保持すれば未選択時に select.length===0 となり該当分岐が skip され、
   * クラッシュせずに必須ブロックが働く。送信値(name="field[]")も選択値のみで正しい。
   */
  private setSelectValues(values: string[], dispatch: boolean) {
    const select = this.select;
    if (!select) return;
    select.replaceChildren(); // 既存optionを全消去（innerHTML未使用）
    for (const v of values) {
      const opt = document.createElement("option");
      opt.value = v;
      opt.textContent = v;
      opt.selected = true;
      select.appendChild(opt);
    }
    if (dispatch) {
      select.dispatchEvent(new Event("change", { bubbles: true }));
    }
  }

  /**
   * selectを視覚的に隠す。`:hidden`（display:none/visibility:hidden/サイズ0）にはしない。
   * jQuery Validate の `ignore:":hidden"` 対象外にして必須検証を維持するため、
   * 1px・opacity:0・absolute で画面外に逃がす（sr-only相当）。
   */
  private hideNativeSelect(select: HTMLSelectElement) {
    // select2初期化を避けるためクラス除去（念のため）
    select.classList.remove("select2");
    select.setAttribute("tabindex", "-1");
    select.setAttribute("aria-hidden", "true");
    const s = select.style;
    s.position = "absolute";
    s.width = "1px";
    s.height = "1px";
    s.padding = "0";
    s.margin = "-1px";
    s.overflow = "hidden";
    s.clip = "rect(0, 0, 0, 0)";
    s.whiteSpace = "nowrap";
    s.border = "0";
    s.opacity = "0";
    s.pointerEvents = "none";
  }
}

/**
 * カスタム要素を登録（既存の createWebComponent は light-DOMの子select参照が必要なため
 * 本要素だけは専用クラスで登録する）
 */
export function registerMultiPicklistFieldElement(
  tagName = "multi-picklist-field",
) {
  if (!customElements.get(tagName)) {
    customElements.define(tagName, MultiPicklistFieldElement);
  }
}

export default MultiPicklistFieldElement;

import React from "react";
import { createWebComponent } from "./createWebComponent";
import type { WebComponentFieldProps } from "@/types/webComponentField";

/**
 * UIType 用の Web コンポーネントに渡すもの
 */
export interface FieldWebComponentRenderProps<
  P extends WebComponentFieldProps,
> {
  /** Smarty から受け取った props */
  props: P;
  /**
   * 送信する値を書き換える
   * props.inputId の hidden 入力に値を入れ、change イベントを発火する
   */
  setValue: (value: string) => void;
}

/**
 * hidden 入力の値を書き換え、vtiger 側（jQuery の検証・変更検知）にも伝える
 */
export function updateFieldInput(inputId: string | undefined, value: string) {
  if (!inputId) return;
  const input = document.getElementById(inputId);
  if (!(input instanceof HTMLInputElement)) return;
  input.value = value;
  input.dispatchEvent(new Event("change", { bubbles: true }));
  const jq = (
    window as { jQuery?: (el: Element) => { trigger: (name: string) => void } }
  ).jQuery;
  if (jq) {
    jq(input).trigger("change");
  }
}

/**
 * props 属性（JSON）を解釈する。壊れていれば null
 */
export function parseFieldProps<P extends WebComponentFieldProps>(
  raw: unknown,
): P | null {
  if (raw && typeof raw === "object") {
    return raw as P;
  }
  if (typeof raw === "string" && raw !== "") {
    try {
      const parsed: unknown = JSON.parse(raw);
      return parsed && typeof parsed === "object" ? (parsed as P) : null;
    } catch {
      return null;
    }
  }
  return null;
}

/**
 * UIType を表示する React コンポーネントを Web コンポーネントとして登録する
 *
 * Smarty は <tag props='{...}'> の形で出力する（props は UIType クラスの
 * getWebComponentProps() を JSON にしたもの）。UIType ごとに Web コンポーネントを
 * 作るときは、この関数で登録すれば props の受け取りと値の書き戻しを共通化できる。
 *
 * @param Component props と setValue を受け取るコンポーネント
 * @param tagName カスタム要素名（例: fr-field-fileupload）
 */
export function createFieldWebComponent<P extends WebComponentFieldProps>(
  Component: React.ComponentType<FieldWebComponentRenderProps<P>>,
  tagName: string,
) {
  const Adapter: React.FC<{ props?: unknown }> = ({ props }) => {
    const parsed = parseFieldProps<P>(props);
    if (!parsed) return null;
    return (
      <Component
        props={parsed}
        setValue={(value) => updateFieldInput(parsed.inputId, value)}
      />
    );
  };
  createWebComponent(Adapter, tagName, ["props"]);
}

import { ParameterType } from "./types";

/**
 * シークレットを設定できない型か
 * boolean は値が 2 択しかなくマスクしても秘匿にならない。
 * font は値がフォント名で秘匿する情報ではなく、一覧から選ぶ。
 * サーバー（Save API）も同じ型にはシークレットを受け付けない。
 */
export const secretUnavailable = (type: ParameterType): boolean =>
  type === "boolean" || type === "font";

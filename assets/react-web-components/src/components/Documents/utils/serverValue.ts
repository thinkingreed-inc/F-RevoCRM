/**
 * 送信用に項目値を整える
 *
 * 入力欄の形式とサーバー（DBカラム）が期待する形式が違うものを揃える。
 */

/** 日時項目（uitype 6 / 70）。入力欄は datetime-local */
const DATETIME_UITYPES = ["6", "70"];

/**
 * datetime-local は "YYYY-MM-DDTHH:MM" を返すが、この形式は DATETIME
 * カラムに入らず値が消えてしまうため "YYYY-MM-DD HH:MM:SS" に直す。
 *
 * 日付（uitype 5）は "YYYY-MM-DD" のまま DATE カラムに入るので変換しない。
 *
 * @param uitype 項目の uitype
 * @param value 入力欄の値
 */
export function toServerValue(
  uitype: string | undefined,
  value: string,
): string {
  if (!value || !uitype || !DATETIME_UITYPES.includes(uitype)) return value;
  const match = value.match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})(:(\d{2}))?$/);
  if (!match) return value;
  return `${match[1]} ${match[2]}:${match[4] ?? "00"}`;
}

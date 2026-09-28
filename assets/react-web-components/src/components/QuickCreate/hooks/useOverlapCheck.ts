import { useState, useCallback } from "react";
import {
  OverlapCheckFormData,
  OverlapCheckResponse,
  UseOverlapCheckResult,
} from "../../../types/overlap";

/**
 * 重複チェックの問い合わせ先。
 * 活動・ToDo いずれの保存でも Calendar モジュールのアクションを使う。
 */
const OVERLAP_CHECK_MODULE = "Calendar";
const OVERLAP_CHECK_ACTION = "FetchOverlapEventsBeforeSave";

/**
 * 重複チェックの対象となるモジュール。
 * ToDo（Calendar）とメールは判定対象外のため活動のみを見る。
 * modules/Calendar/actions/FetchOverlapEventsBeforeSave.php の絞り込みと揃えている。
 */
const OVERLAP_CHECK_TARGET_MODULE = "Events";

/**
 * サーバへ送る繰り返し関連のキー。
 * 値が入っているものだけを送る。
 */
const RECURRING_KEYS = [
  "recurringEditMode",
  "repeat_frequency",
  "recurringtype",
  "calendar_repeat_limit_date",
  "repeatmonth_type",
  "repeatMonth",
  "repeatMonth_date",
  "repeatmonth_daytype",
  "repeatMonth_day",
] as const;

/** 曜日指定のキー */
const WEEK_DAY_KEYS = [
  "sun_flag",
  "mon_flag",
  "tue_flag",
  "wed_flag",
  "thu_flag",
  "fri_flag",
  "sat_flag",
] as const;

/**
 * "YYYY-MM-DDTHH:mm" 形式を日付と時刻へ分割する。
 * 日付のみの場合は時刻を undefined で返す。
 */
function splitDateTime(value: unknown): {
  date?: string;
  time?: string;
} {
  if (typeof value !== "string" || value === "") {
    return {};
  }
  if (value.includes("T")) {
    const [date, time] = value.split("T");
    return { date, time };
  }
  return { date: value };
}

/** 送信対象の値かどうか（空値は送らない） */
function isSendableValue(value: unknown): boolean {
  return value !== undefined && value !== null && value !== "";
}

/**
 * 活動の期間重複チェックを行うHook
 *
 * 旧UI（Calendar_Edit_Js.showOverlapEventConfirmationBeforeSave）と同じ
 * アクションを呼び出し、同じ確認メッセージを利用する。
 *
 * @param module チェック対象のモジュール名（Events のときのみ問い合わせる）
 */
export function useOverlapCheck(module: string): UseOverlapCheckResult {
  const [isChecking, setIsChecking] = useState<boolean>(false);

  /**
   * CSRFトークンを取得
   */
  const getCsrfToken = useCallback((): {
    name: string;
    value: string;
  } | null => {
    const csrfName = (window as any).csrfMagicName;
    const csrfToken = (window as any).csrfMagicToken;

    if (csrfName && csrfToken) {
      return { name: csrfName, value: csrfToken };
    }
    return null;
  }, []);

  const checkOverlap = useCallback(
    async (formData: OverlapCheckFormData): Promise<string | null> => {
      if (module !== OVERLAP_CHECK_TARGET_MODULE) {
        return null;
      }

      // 開始日時が無ければ判定できない
      const start = splitDateTime(formData.date_start);
      if (!start.date) {
        return null;
      }
      const end = splitDateTime(formData.due_date);

      const csrf = getCsrfToken();
      if (!csrf) {
        throw new Error(
          "CSRFトークンが取得できませんでした。ページをリロードしてください。",
        );
      }

      setIsChecking(true);
      try {
        const urlParams = new URLSearchParams({
          module: OVERLAP_CHECK_MODULE,
          action: OVERLAP_CHECK_ACTION,
        });

        const bodyParams = new URLSearchParams();
        bodyParams.append(csrf.name, csrf.value);

        // QuickCreate のフォームは編集中のレコードIDを `record` で保持する。
        // これを送らないとサーバ側で編集中の活動・参加者の活動・繰り返し系列が
        // 除外されず、自分自身が重複相手として並んでしまう。
        const recordIdValue = formData.record ?? formData.record_id;
        if (isSendableValue(recordIdValue)) {
          bodyParams.append("record_id", String(recordIdValue));
        }
        if (isSendableValue(formData.assigned_user_id)) {
          bodyParams.append(
            "assigned_user_id",
            String(formData.assigned_user_id),
          );
        }

        bodyParams.append("date_start", start.date);
        const timeStart = start.time ?? formData.time_start;
        if (isSendableValue(timeStart)) {
          bodyParams.append("time_start", String(timeStart));
        }
        if (end.date) {
          bodyParams.append("due_date", end.date);
        }
        const timeEnd = end.time ?? formData.time_end;
        if (isSendableValue(timeEnd)) {
          bodyParams.append("time_end", String(timeEnd));
        }

        // 終日は PHP 側が "on" を期待する。
        // Calendar.js からの起動時は文字列で渡ることがあるため表記の揺れを吸収する
        const allDay = formData.is_allday;
        if (
          allDay === true ||
          allDay === "on" ||
          allDay === "true" ||
          allDay === "1"
        ) {
          bodyParams.append("is_allday", "on");
        }

        if (Array.isArray(formData.selectedusers)) {
          formData.selectedusers.forEach((userId) => {
            if (isSendableValue(userId)) {
              bodyParams.append("selectedusers[]", String(userId));
            }
          });
        }

        RECURRING_KEYS.forEach((key) => {
          const value = formData[key];
          if (isSendableValue(value)) {
            bodyParams.append(key, String(value));
          }
        });

        WEEK_DAY_KEYS.forEach((key) => {
          if (formData[key]) {
            bodyParams.append("recurring_weekdays[]", key);
          }
        });

        const response = await fetch(`?${urlParams.toString()}`, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: bodyParams.toString(),
        });

        if (!response.ok) {
          throw new Error("重複チェックに失敗しました");
        }

        const data: OverlapCheckResponse = await response.json();

        if (!data.success) {
          throw new Error(data.error?.message || "重複チェックに失敗しました");
        }

        const message = data.result?.message;
        return message ? message : null;
      } finally {
        setIsChecking(false);
      }
    },
    [module, getCsrfToken],
  );

  return { checkOverlap, isChecking };
}

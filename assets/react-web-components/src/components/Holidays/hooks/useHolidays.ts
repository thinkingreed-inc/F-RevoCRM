import { useCallback, useEffect, useState } from "react";
import type {
  GenerateResult,
  HolidayFormValues,
  ImportResult,
  HolidayInfo,
  HolidayListResult,
  HolidayRecord,
  SettingsResult,
} from "../types/holidays";

/**
 * 休祝日マスタのデータ取得・更新
 *
 * サーバー側は Settings:Holidays の HolidayAPI（JSON）。
 * 設定機能のためシステム管理者のみ利用できる。
 */

interface ApiResponse<T> {
  success?: boolean;
  result?: T;
  error?: { message?: string; code?: string };
}

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) {
    return { name: csrfName, value: csrfToken };
  }
  return null;
}

/** HolidayAPI を呼び出す */
async function callApi<T>(
  mode: string,
  params: Record<string, string> = {},
): Promise<T> {
  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "Holidays");
  body.append("parent", "Settings");
  body.append("api", "HolidayAPI");
  body.append("mode", mode);
  for (const [key, value] of Object.entries(params)) {
    body.append(key, value);
  }

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: body.toString(),
  });
  const text = await response.text();
  let data: ApiResponse<T>;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error("Invalid response");
  }
  if (data.success === false || data.error) {
    throw new Error(
      data.error?.message || data.error?.code || "Request failed",
    );
  }
  return (data.result ?? data) as T;
}

/** CSVファイルをアップロードして取り込む */
async function callImportApi(
  file: File | null,
  year?: number,
): Promise<ImportResult> {
  const csrf = getCsrfToken();
  const body = new FormData();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "Holidays");
  body.append("parent", "Settings");
  body.append("api", "HolidayAPI");
  body.append("mode", file ? "import" : "import_url");
  if (year) body.append("year", String(year));
  if (file) body.append("csv", file, file.name);

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { Accept: "application/json" },
    body,
  });
  const text = await response.text();
  let data: ApiResponse<ImportResult>;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error("Invalid response");
  }
  if (data.success === false || data.error) {
    throw new Error(
      data.error?.message || data.error?.code || "Request failed",
    );
  }
  return (data.result ?? data) as ImportResult;
}

interface UseHolidaysResult {
  info: HolidayInfo | null;
  records: HolidayRecord[];
  availableYears: number[];
  year: number;
  isLoading: boolean;
  isSaving: boolean;
  error: string | null;
  setYear: (year: number) => void;
  setError: (message: string | null) => void;
  reload: () => Promise<void>;
  save: (values: HolidayFormValues) => Promise<void>;
  remove: (holidayId: number) => Promise<void>;
  generate: (year: number) => Promise<GenerateResult>;
  /** 内閣府公表データを取り込む（file を省略するとURLから取得） */
  importOfficial: (file?: File | null, year?: number) => Promise<ImportResult>;
  /** 週休の曜日を保存する */
  saveWeeklyHolidays: (weekdays: number[]) => Promise<number[]>;
}

/**
 * 休祝日マスタのデータを扱うフック
 *
 * @param initialYear 初期表示年
 */
export function useHolidays(initialYear?: number): UseHolidaysResult {
  const [info, setInfo] = useState<HolidayInfo | null>(null);
  const [records, setRecords] = useState<HolidayRecord[]>([]);
  const [availableYears, setAvailableYears] = useState<number[]>([]);
  const [year, setYear] = useState<number>(
    initialYear && initialYear > 0 ? initialYear : new Date().getFullYear(),
  );
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadList = useCallback(async (targetYear: number) => {
    const result = await callApi<HolidayListResult>("list", {
      year: String(targetYear),
    });
    setRecords(result.records || []);
    setAvailableYears(result.available_years || []);
  }, []);

  const reload = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      await loadList(year);
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setIsLoading(false);
    }
  }, [loadList, year]);

  // 初期表示（選択肢・ラベル・一覧）
  useEffect(() => {
    let cancelled = false;
    (async () => {
      setIsLoading(true);
      setError(null);
      try {
        const loadedInfo = await callApi<HolidayInfo>("info");
        if (cancelled) return;
        setInfo(loadedInfo);
        setAvailableYears(loadedInfo.available_years || []);
        await loadList(year);
      } catch (e) {
        if (!cancelled) setError(e instanceof Error ? e.message : String(e));
      } finally {
        if (!cancelled) setIsLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
    // 初回のみ実行する（年の変更は下の useEffect で扱う）
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // 年を切り替えたら一覧を読み直す
  useEffect(() => {
    if (info === null) return;
    reload();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [year]);

  const save = useCallback(
    async (values: HolidayFormValues) => {
      setIsSaving(true);
      setError(null);
      try {
        const params: Record<string, string> = {
          holiday_date: values.holiday_date,
          holiday_name: values.holiday_name,
          day_type: values.day_type,
          holiday_type: values.holiday_type,
          description: values.description,
        };
        if (values.holidayid) {
          params.record = String(values.holidayid);
        }
        await callApi("save", params);
        await loadList(Number(values.holiday_date.substring(0, 4)));
        setYear(Number(values.holiday_date.substring(0, 4)));
      } finally {
        setIsSaving(false);
      }
    },
    [loadList],
  );

  const remove = useCallback(
    async (holidayId: number) => {
      setIsSaving(true);
      setError(null);
      try {
        await callApi("delete", { record: String(holidayId) });
        await loadList(year);
      } finally {
        setIsSaving(false);
      }
    },
    [loadList, year],
  );

  const generate = useCallback(
    async (targetYear: number) => {
      setIsSaving(true);
      setError(null);
      try {
        const result = await callApi<GenerateResult>("generate", {
          year: String(targetYear),
        });
        await loadList(targetYear);
        return result;
      } finally {
        setIsSaving(false);
      }
    },
    [loadList],
  );

  const importOfficial = useCallback(
    async (file?: File | null, targetYear?: number) => {
      setIsSaving(true);
      setError(null);
      try {
        const result = await callImportApi(file ?? null, targetYear);
        await loadList(year);
        return result;
      } finally {
        setIsSaving(false);
      }
    },
    [loadList, year],
  );

  const saveWeeklyHolidays = useCallback(async (weekdays: number[]) => {
    setIsSaving(true);
    setError(null);
    try {
      const result = await callApi<SettingsResult>("save_settings", {
        weekly_holidays: weekdays.join(","),
      });
      const saved = result.weekly_holidays || [];
      // 一覧の曜日表示（週休の色分け）に反映する
      setInfo((current) =>
        current ? { ...current, weekly_holidays: saved } : current,
      );
      return saved;
    } finally {
      setIsSaving(false);
    }
  }, []);

  return {
    info,
    records,
    availableYears,
    year,
    isLoading,
    isSaving,
    error,
    setYear,
    setError,
    reload,
    save,
    remove,
    generate,
    importOfficial,
    saveWeeklyHolidays,
  };
}

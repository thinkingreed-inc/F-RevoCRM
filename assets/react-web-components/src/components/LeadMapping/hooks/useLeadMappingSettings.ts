import { useCallback, useEffect, useState } from "react";
import type {
  LeadMappingInfo,
  SettingsSaveResult,
} from "../../../types/leadMapping";
import { getJson, postJson } from "../../LeadConvert/api";

const BASE_PARAMS = {
  module: "Leads",
  parent: "Settings",
  api: "ConvertSettings",
};

export type SaveMode =
  "saveMapping" | "saveLayout" | "saveDefaults" | "saveMatchRules";

/**
 * リードマッピング設定の取得と保存（Settings_Leads_ConvertSettings_Api）
 */
export function useLeadMappingSettings() {
  const [info, setInfo] = useState<LeadMappingInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const reload = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setInfo(await getJson<LeadMappingInfo>({ ...BASE_PARAMS, mode: "info" }));
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void reload();
  }, [reload]);

  const save = useCallback(
    async (
      mode: SaveMode,
      params: Record<string, string>,
    ): Promise<SettingsSaveResult> => {
      const result = await postJson<SettingsSaveResult>({
        ...BASE_PARAMS,
        mode,
        ...params,
      });
      if (result.saved) {
        await reload();
      }
      return result;
    },
    [reload],
  );

  return { info, loading, error, reload, save };
}

import { useCallback, useEffect, useState } from "react";
import type { FieldInfo } from "../../../types/field";
import type {
  AttachableModule,
  ConvertLeadInfo,
  ConvertRecordSummary,
  ConvertRequestPayload,
  ConvertSaveResult,
} from "../../../types/leadConvert";
import { fetchEditFields, getJson, postJson } from "../api";
import type { FieldsByModule } from "../utils/convertRules";

interface UseLeadConvertResult {
  info: ConvertLeadInfo | null;
  fieldsByModule: FieldsByModule;
  /** 担当（assigned user）の項目。リードの GetFields から取る */
  ownerField: FieldInfo | null;
  loading: boolean;
  error: string | null;
  fetchSummary: (
    module: AttachableModule,
    id: string,
  ) => Promise<ConvertRecordSummary | null>;
  submit: (payload: ConvertRequestPayload) => Promise<ConvertSaveResult>;
}

const EMPTY_FIELDS: FieldsByModule = {
  Accounts: [],
  Contacts: [],
  Potentials: [],
};

/**
 * 昇格画面のデータ取得と昇格の実行
 */
export function useLeadConvert(recordId: number): UseLeadConvertResult {
  const [info, setInfo] = useState<ConvertLeadInfo | null>(null);
  const [fieldsByModule, setFieldsByModule] =
    useState<FieldsByModule>(EMPTY_FIELDS);
  const [ownerField, setOwnerField] = useState<FieldInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    const load = async () => {
      setLoading(true);
      setError(null);
      try {
        const loaded = await getJson<ConvertLeadInfo>({
          module: "Leads",
          api: "ConvertLeadInfo",
          record: String(recordId),
        });
        const fetchIfAvailable = (module: keyof FieldsByModule) =>
          loaded.modules[module].available
            ? fetchEditFields(module)
            : Promise.resolve([]);
        const [accounts, contacts, potentials, leadFields] = await Promise.all([
          fetchIfAvailable("Accounts"),
          fetchIfAvailable("Contacts"),
          fetchIfAvailable("Potentials"),
          fetchEditFields("Leads"),
        ]);
        if (cancelled) {
          return;
        }
        setInfo(loaded);
        setFieldsByModule({
          Accounts: accounts,
          Contacts: contacts,
          Potentials: potentials,
        });
        setOwnerField(
          leadFields.find((field) => field.name === "assigned_user_id") ?? null,
        );
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : String(err));
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };
    void load();
    return () => {
      cancelled = true;
    };
  }, [recordId]);

  const fetchSummary = useCallback(
    async (module: AttachableModule, id: string) => {
      if (!id) {
        return null;
      }
      try {
        return await getJson<ConvertRecordSummary>({
          module: "Leads",
          api: "ConvertLeadInfo",
          record: String(recordId),
          mode: "summary",
          target: module,
          id,
        });
      } catch {
        return null;
      }
    },
    [recordId],
  );

  const submit = useCallback(
    (payload: ConvertRequestPayload) =>
      postJson<ConvertSaveResult>({
        module: "Leads",
        api: "ConvertLeadSave",
        record: String(recordId),
        data: JSON.stringify(payload),
      }),
    [recordId],
  );

  return {
    info,
    fieldsByModule,
    ownerField,
    loading,
    error,
    fetchSummary,
    submit,
  };
}

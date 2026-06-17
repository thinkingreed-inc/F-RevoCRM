import { useState, useCallback, useEffect } from "react";

export interface DocFieldInfo {
  name: string;
  label: string;
  uitype: string;
  mandatory: boolean;
  editable: boolean;
  displaytype: string;
  defaultvalue: string;
  type: string;
  picklistValues?: Array<{ label: string; value: string }>;
  referenceModules?: string[];
  referenceModuleLabels?: Record<string, string>;
  blockLabel: string;
}

interface UseDocumentFieldsResult {
  fields: DocFieldInfo[];
  isLoading: boolean;
  error: string | null;
}

export function useDocumentFields(recordId?: number | null, includeReadonly?: boolean): UseDocumentFieldsResult {
  const [fields, setFields] = useState<DocFieldInfo[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchFields = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const params = new URLSearchParams({
        module: "Documents",
        api: "GetFields",
        view: "edit",
      });
      if (recordId) {
        params.append("record", String(recordId));
      }

      const response = await fetch(`?${params.toString()}`, {
        method: "GET",
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });
      const data = await response.json();
      if (data.error) throw new Error(data.error);

      if (data.fields && Array.isArray(data.fields)) {
        const converted: DocFieldInfo[] = data.fields
          .filter((f: any) => {
            if (!includeReadonly && String(f.displaytype) === "2") return false;
            return true;
          })
          .map((f: any) => ({
            name: f.name,
            label: f.label,
            uitype: String(f.uitype),
            mandatory: !!f.mandatory,
            editable: !!f.editable,
            displaytype: String(f.displaytype || "1"),
            defaultvalue: f.defaultValue || "",
            type: f.type || "string",
            picklistValues: Array.isArray(f.picklistValues)
              ? f.picklistValues.map((pv: any) => ({
                  value: pv.value,
                  label: pv.label,
                }))
              : undefined,
            referenceModules: Array.isArray(f.referenceModules) ? f.referenceModules : undefined,
            referenceModuleLabels: f.referenceModuleLabels || undefined,
            blockLabel: f.block || "",
          }));
        setFields(converted);
      }
    } catch (e: any) {
      setError(e.message || "Failed to fetch fields");
    } finally {
      setIsLoading(false);
    }
  }, [recordId, includeReadonly]);

  useEffect(() => {
    fetchFields();
  }, [fetchFields]);

  return { fields, isLoading, error };
}

import { useState, useCallback, useEffect } from "react";

export interface DocFieldInfo {
  name: string;
  label: string;
  uitype: string;
  mandatory: boolean;
  editable: boolean;
  defaultvalue: string;
  type: string;
  picklistValues?: Array<{ label: string; value: string }>;
  block?: string;
}

interface UseDocumentFieldsResult {
  fields: DocFieldInfo[];
  isLoading: boolean;
  error: string | null;
}

export function useDocumentFields(recordId?: number | null): UseDocumentFieldsResult {
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
        view: "quickcreate",
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
          .filter((f: any) => f.editable && f.displaytype !== "2")
          .map((f: any) => ({
            name: f.name,
            label: f.label,
            uitype: String(f.uitype),
            mandatory: !!f.mandatory,
            editable: !!f.editable,
            defaultvalue: f.default || "",
            type: f.type?.name || "string",
            picklistValues: f.type?.picklistValues
              ? Object.entries(f.type.picklistValues).map(([value, label]) => ({
                  value,
                  label: String(label),
                }))
              : undefined,
            block: f.blockLabel || "",
          }));
        setFields(converted);
      }
    } catch (e: any) {
      setError(e.message || "Failed to fetch fields");
    } finally {
      setIsLoading(false);
    }
  }, [recordId]);

  useEffect(() => {
    fetchFields();
  }, [fetchFields]);

  return { fields, isLoading, error };
}

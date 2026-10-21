import { useState } from "react";
import { Lock, Plus, Trash2, Undo2 } from "lucide-react";
import { Button } from "../ui/button";
import { cn } from "../../lib/utils";
import type { LeadMappingInfo, MappingRow } from "../../types/leadMapping";
import {
  TARGET_MODULES,
  changeLeadField,
  leadFieldOf,
  targetOptions,
  toRows,
  toSaveRows,
} from "./utils/mappingRules";
import { SaveBar, selectClassName, type Translate } from "./parts";
import { runSave } from "./utils/runSave";
import type { SaveMode } from "./hooks/useLeadMappingSettings";
import type { SettingsSaveResult } from "../../types/leadMapping";

interface MappingTabProps {
  info: LeadMappingInfo;
  t: Translate;
  save: (
    mode: SaveMode,
    params: Record<string, string>,
  ) => Promise<SettingsSaveResult>;
}

let newRowSeq = 0;

/**
 * 項目のマッピング（リードの項目 → 企業・担当者・案件の項目）
 */
export function MappingTab({ info, t, save }: MappingTabProps) {
  const [rows, setRows] = useState<MappingRow[]>(() => toRows(info.mapping));
  const [dirty, setDirty] = useState(false);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<string[]>([]);

  const update = (key: string, change: (row: MappingRow) => MappingRow) => {
    setRows((prev) => prev.map((row) => (row.key === key ? change(row) : row)));
    setDirty(true);
    setMessage(null);
  };

  const addRow = () => {
    newRowSeq += 1;
    setRows((prev) => [
      ...prev,
      {
        id: null,
        key: `new-${newRowSeq}`,
        editable: true,
        lead: null,
        Accounts: null,
        Contacts: null,
        Potentials: null,
        deleted: false,
      },
    ]);
    setDirty(true);
    setMessage(null);
  };

  const handleSave = async () => {
    setSaving(true);
    const result = await runSave(
      () => save("saveMapping", { mapping: JSON.stringify(toSaveRows(rows)) }),
      t,
    );
    setSaving(false);
    setErrors(result.errors);
    if (result.saved) {
      setDirty(false);
      setMessage(t("LBL_CONVERT_SETTINGS_SAVED"));
    }
  };

  // 保存後に info が更新されたら画面の行も作り直す
  const [loadedMapping, setLoadedMapping] = useState(info.mapping);
  if (loadedMapping !== info.mapping) {
    setLoadedMapping(info.mapping);
    setRows(toRows(info.mapping));
  }

  const headers = [
    t("LBL_CONVERT_SETTINGS_LEAD_FIELD"),
    t("LBL_CONVERT_SETTINGS_TYPE"),
    ...TARGET_MODULES.map((module) => info.modules[module].label),
  ];

  return (
    <div data-testid="lead-mapping-tab-mapping">
      <p className="mt-0 mb-3 text-sm text-gray-600">
        {t("LBL_CONVERT_SETTINGS_MAPPING_HELP")}
      </p>
      <div className="grid grid-cols-[40px_minmax(0,1.4fr)_90px_repeat(3,minmax(0,1fr))] gap-2 border-b border-gray-200 px-2 pb-2 text-xs font-semibold text-gray-600 max-md:!hidden">
        <span />
        {headers.map((header) => (
          <span key={header}>{header}</span>
        ))}
      </div>
      <ul className="m-0 list-none space-y-2 p-0 md:space-y-0">
        {rows.map((row) => {
          const leadField = leadFieldOf(info, row.lead);
          const locked = !row.editable;
          return (
            <li
              key={row.key}
              data-testid="lead-mapping-row"
              data-locked={locked ? "true" : "false"}
              className={cn(
                "grid grid-cols-1 gap-2 rounded-md border border-gray-200 p-3 md:grid-cols-[40px_minmax(0,1.4fr)_90px_repeat(3,minmax(0,1fr))] md:items-center md:rounded-none md:border-0 md:border-b md:px-2 md:py-1.5",
                row.deleted && "opacity-50",
                locked && "bg-gray-50",
              )}
            >
              <div className="flex items-center justify-end md:justify-center">
                {locked ? (
                  <Lock
                    className="h-4 w-4 text-gray-400"
                    aria-label={t("LBL_CONVERT_SETTINGS_LOCKED")}
                  />
                ) : (
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={row.deleted ? "Undo" : t("LBL_DELETE")}
                    onClick={() =>
                      update(row.key, (r) => ({ ...r, deleted: !r.deleted }))
                    }
                  >
                    {row.deleted ? (
                      <Undo2 className="h-4 w-4" />
                    ) : (
                      <Trash2 className="h-4 w-4 text-red-600" />
                    )}
                  </Button>
                )}
              </div>
              <label className="flex flex-col gap-1">
                <span className="text-xs text-gray-500 md:sr-only">
                  {headers[0]}
                </span>
                <select
                  className={selectClassName}
                  value={row.lead ?? ""}
                  disabled={locked || row.deleted}
                  onChange={(e) =>
                    update(row.key, (r) =>
                      changeLeadField(
                        info,
                        r,
                        e.target.value ? Number(e.target.value) : null,
                      ),
                    )
                  }
                >
                  <option value="">{t("LBL_CONVERT_SETTINGS_NONE")}</option>
                  {info.leadFields.map((field) => (
                    <option key={field.id} value={field.id}>
                      {field.label}
                    </option>
                  ))}
                </select>
              </label>
              <span className="text-xs text-gray-500">
                {leadField?.type ?? ""}
              </span>
              {TARGET_MODULES.map((module, index) => (
                <label key={module} className="flex flex-col gap-1">
                  <span className="text-xs text-gray-500 md:sr-only">
                    {headers[index + 2]}
                  </span>
                  <select
                    className={selectClassName}
                    value={row[module] ?? ""}
                    disabled={locked || row.deleted || !leadField}
                    data-module={module}
                    onChange={(e) =>
                      update(row.key, (r) => ({
                        ...r,
                        [module]: e.target.value
                          ? Number(e.target.value)
                          : null,
                      }))
                    }
                  >
                    <option value="">{t("LBL_CONVERT_SETTINGS_NONE")}</option>
                    {(locked
                      ? info.modules[module].mappableFields.filter(
                          (field) => field.id === row[module],
                        )
                      : targetOptions(info, row, module)
                    ).map((field) => (
                      <option key={field.id} value={field.id}>
                        {field.label}
                      </option>
                    ))}
                  </select>
                </label>
              ))}
            </li>
          );
        })}
      </ul>
      <Button
        type="button"
        variant="outline"
        className="mt-3"
        onClick={addRow}
        data-testid="lead-mapping-add-row"
      >
        <Plus className="h-4 w-4" aria-hidden="true" />
        {t("LBL_CONVERT_SETTINGS_ADD_MAPPING")}
      </Button>
      <SaveBar
        t={t}
        saving={saving}
        dirty={dirty}
        message={message}
        errors={errors}
        onSave={() => void handleSave()}
        testId="lead-mapping-save-mapping"
      />
    </div>
  );
}

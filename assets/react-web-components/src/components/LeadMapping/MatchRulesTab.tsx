import { useState } from "react";
import { Plus, Trash2, X } from "lucide-react";
import { Button } from "../ui/button";
import type { AttachableModule } from "../../types/leadConvert";
import type {
  LeadMappingInfo,
  SettingsSaveResult,
} from "../../types/leadMapping";
import { addRuleField } from "./utils/mappingRules";
import {
  ModuleSwitcher,
  SaveBar,
  selectClassName,
  type Translate,
} from "./parts";
import { runSave } from "./utils/runSave";
import type { SaveMode } from "./hooks/useLeadMappingSettings";

interface MatchRulesTabProps {
  info: LeadMappingInfo;
  t: Translate;
  save: (
    mode: SaveMode,
    params: Record<string, string>,
  ) => Promise<SettingsSaveResult>;
}

const MATCH_MODULES: readonly AttachableModule[] = ["Accounts", "Contacts"];

/**
 * 昇格済み判定条件（条件内は AND、条件同士は OR）
 */
export function MatchRulesTab({ info, t, save }: MatchRulesTabProps) {
  const [module, setModule] = useState<AttachableModule>("Accounts");
  const [rules, setRules] = useState<Record<AttachableModule, string[][]>>(
    () => ({
      Accounts: info.modules.Accounts.matchRules.map((rule) => [...rule]),
      Contacts: info.modules.Contacts.matchRules.map((rule) => [...rule]),
    }),
  );
  const [dirtyModules, setDirtyModules] = useState<AttachableModule[]>([]);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<string[]>([]);

  const fields = info.modules[module].matchableFields;
  const labelOf = (name: string) =>
    fields.find((field) => field.name === name)?.label ?? name;
  const current = rules[module];
  const labels = {
    Accounts: info.modules.Accounts.label,
    Contacts: info.modules.Contacts.label,
  };

  const change = (next: string[][]) => {
    setRules((prev) => ({ ...prev, [module]: next }));
    setDirtyModules((prev) =>
      prev.includes(module) ? prev : [...prev, module],
    );
    setMessage(null);
  };

  const handleSave = async () => {
    setSaving(true);
    let allErrors: string[] = [];
    for (const target of dirtyModules) {
      const result = await runSave(
        () =>
          save("saveMatchRules", {
            target,
            rules: JSON.stringify(
              rules[target].filter((rule) => rule.length > 0),
            ),
          }),
        t,
      );
      allErrors = [...allErrors, ...result.errors];
    }
    setSaving(false);
    setErrors(allErrors);
    if (allErrors.length === 0) {
      setDirtyModules([]);
      setMessage(t("LBL_CONVERT_SETTINGS_SAVED"));
    }
  };

  return (
    <div data-testid="lead-mapping-tab-match">
      <p className="mt-0 mb-3 text-sm text-gray-600">
        {t("LBL_CONVERT_SETTINGS_MATCH_HELP")}
      </p>
      <ModuleSwitcher
        modules={MATCH_MODULES}
        labels={labels}
        value={module}
        onChange={setModule}
        name="match-module"
      />
      <div className="mt-4 space-y-3">
        {current.length === 0 && (
          <p className="m-0 text-sm text-gray-500">
            {t("LBL_CONVERT_SETTINGS_NO_RULES")}
          </p>
        )}
        {current.map((rule, index) => (
          <section
            key={index}
            className="rounded-md border border-gray-200 bg-white p-3"
            data-testid="lead-mapping-rule"
            aria-label={t("LBL_CONVERT_SETTINGS_RULE", index + 1)}
          >
            <div className="mb-2 flex items-center justify-between">
              <h4 className="m-0 text-sm font-semibold text-gray-700">
                {t("LBL_CONVERT_SETTINGS_RULE", index + 1)}
                {index > 0 && (
                  <span className="ml-2 text-xs font-normal text-gray-500">
                    OR
                  </span>
                )}
              </h4>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => change(current.filter((_, i) => i !== index))}
                aria-label={t("LBL_CONVERT_SETTINGS_REMOVE_RULE")}
              >
                <Trash2 className="h-4 w-4 text-red-600" aria-hidden="true" />
                <span className="max-sm:!hidden">
                  {t("LBL_CONVERT_SETTINGS_REMOVE_RULE")}
                </span>
              </Button>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              {rule.map((name, fieldIndex) => (
                <span key={name} className="inline-flex items-center gap-1">
                  {fieldIndex > 0 && (
                    <span className="text-xs text-gray-500">+</span>
                  )}
                  <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 py-1 pr-1 pl-3 text-sm text-blue-900">
                    {labelOf(name)}
                    <button
                      type="button"
                      className="rounded-full p-0.5 hover:bg-blue-100"
                      aria-label={`${t("LBL_CONVERT_SETTINGS_REMOVE")} ${labelOf(name)}`}
                      onClick={() =>
                        change(
                          current.map((r, i) =>
                            i === index ? r.filter((item) => item !== name) : r,
                          ),
                        )
                      }
                    >
                      <X className="h-3.5 w-3.5" />
                    </button>
                  </span>
                </span>
              ))}
              <select
                className={`${selectClassName} w-auto max-w-[220px]`}
                value=""
                aria-label={t("LBL_CONVERT_SETTINGS_ADD_FIELD")}
                onChange={(e) => {
                  if (e.target.value)
                    change(addRuleField(current, index, e.target.value));
                }}
              >
                <option value="">
                  + {t("LBL_CONVERT_SETTINGS_ADD_FIELD")}
                </option>
                {fields
                  .filter((field) => !rule.includes(field.name))
                  .map((field) => (
                    <option key={field.name} value={field.name}>
                      {field.label}
                    </option>
                  ))}
              </select>
            </div>
          </section>
        ))}
        <Button
          type="button"
          variant="outline"
          onClick={() => change([...current, []])}
          data-testid="lead-mapping-add-rule"
        >
          <Plus className="h-4 w-4" aria-hidden="true" />
          {t("LBL_CONVERT_SETTINGS_ADD_RULE")}
        </Button>
      </div>
      <SaveBar
        t={t}
        saving={saving}
        dirty={dirtyModules.length > 0}
        message={message}
        errors={errors}
        onSave={() => void handleSave()}
        testId="lead-mapping-save-match"
      />
    </div>
  );
}

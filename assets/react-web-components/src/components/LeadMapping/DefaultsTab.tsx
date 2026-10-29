import { useEffect, useState } from "react";
import { Loader2, X } from "lucide-react";
import { Button } from "../ui/button";
import { FieldRenderer } from "../FieldRenderer";
import type { FieldInfo, FieldValue } from "../../types/field";
import type { ConvertModule } from "../../types/leadConvert";
import type {
  LeadMappingInfo,
  SettingsSaveResult,
} from "../../types/leadMapping";
import { fetchEditFields } from "../LeadConvert/api";
import { TARGET_MODULES } from "./utils/mappingRules";
import {
  ModuleSwitcher,
  SaveBar,
  selectClassName,
  type Translate,
} from "./parts";
import { runSave } from "./utils/runSave";
import type { SaveMode } from "./hooks/useLeadMappingSettings";

interface DefaultsTabProps {
  info: LeadMappingInfo;
  t: Translate;
  save: (
    mode: SaveMode,
    params: Record<string, string>,
  ) => Promise<SettingsSaveResult>;
}

type DefaultsState = Record<ConvertModule, Record<string, FieldValue>>;

/**
 * 昇格時デフォルト値（昇格するときだけ使う値。値は DB 形式で保存する）
 */
export function DefaultsTab({ info, t, save }: DefaultsTabProps) {
  const [module, setModule] = useState<ConvertModule>("Potentials");
  const [defaults, setDefaults] = useState<DefaultsState>(() => ({
    Accounts: { ...info.modules.Accounts.defaults },
    Contacts: { ...info.modules.Contacts.defaults },
    Potentials: { ...info.modules.Potentials.defaults },
  }));
  const [fieldsByModule, setFieldsByModule] = useState<
    Partial<Record<ConvertModule, FieldInfo[]>>
  >({});
  const [dirtyModules, setDirtyModules] = useState<ConvertModule[]>([]);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<string[]>([]);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (fieldsByModule[module]) return;
    let cancelled = false;
    void fetchEditFields(module)
      .then((fields) => {
        if (!cancelled)
          setFieldsByModule((prev) => ({ ...prev, [module]: fields }));
      })
      .catch(() => {
        if (!cancelled)
          setFieldsByModule((prev) => ({ ...prev, [module]: [] }));
      });
    return () => {
      cancelled = true;
    };
  }, [module, fieldsByModule]);

  const allowed = new Set(
    info.modules[module].inputFields.map((field) => field.name),
  );
  const meta = (fieldsByModule[module] ?? []).filter((field) =>
    allowed.has(field.name),
  );
  const values = defaults[module];
  const configured = meta.filter((field) => field.name in values);
  const addable = meta.filter((field) => !(field.name in values));
  const labels = Object.fromEntries(
    TARGET_MODULES.map((m) => [m, info.modules[m].label]),
  ) as Record<ConvertModule, string>;

  const change = (next: Record<string, FieldValue>) => {
    setDefaults((prev) => ({ ...prev, [module]: next }));
    setDirtyModules((prev) =>
      prev.includes(module) ? prev : [...prev, module],
    );
    setMessage(null);
  };

  const handleSave = async () => {
    setSaving(true);
    let allErrors: string[] = [];
    let allFieldErrors: Record<string, string> = {};
    for (const target of dirtyModules) {
      const payload = Object.fromEntries(
        Object.entries(defaults[target]).filter(
          ([name]) => !name.endsWith("_display"),
        ),
      );
      const result = await runSave(
        () =>
          save("saveDefaults", { target, defaults: JSON.stringify(payload) }),
        t,
      );
      allErrors = [...allErrors, ...result.errors];
      allFieldErrors = { ...allFieldErrors, ...result.fieldErrors };
    }
    setSaving(false);
    setErrors(allErrors);
    setFieldErrors(allFieldErrors);
    if (allErrors.length === 0) {
      setDirtyModules([]);
      setMessage(t("LBL_CONVERT_SETTINGS_SAVED"));
    }
  };

  return (
    <div data-testid="lead-mapping-tab-defaults">
      <p className="mt-0 mb-3 text-sm text-gray-600">
        {t("LBL_CONVERT_SETTINGS_DEFAULTS_HELP")}
      </p>
      <ModuleSwitcher
        modules={TARGET_MODULES}
        labels={labels}
        value={module}
        onChange={setModule}
        name="defaults-module"
      />
      <div className="mt-4 space-y-3">
        {!fieldsByModule[module] && (
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
            {t("LBL_LOADING")}
          </div>
        )}
        {configured.map((field) => (
          <div
            key={field.name}
            className="flex items-start gap-2"
            data-field={field.name}
          >
            <div className="min-w-0 flex-1">
              <FieldRenderer
                field={{ ...field, mandatory: false }}
                value={values[field.name]}
                onChange={(name, value) => change({ ...values, [name]: value })}
                error={fieldErrors[field.name]}
                className="w-full flex-col items-stretch md:flex-row md:items-start"
                labelClassName="w-full text-left leading-normal md:w-[160px] md:text-right md:leading-[30px]"
                formData={values}
                module={module}
              />
            </div>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              aria-label={`${t("LBL_CONVERT_SETTINGS_REMOVE")} ${field.label}`}
              onClick={() => {
                const next = { ...values };
                delete next[field.name];
                delete next[`${field.name}_display`];
                change(next);
              }}
            >
              <X className="h-4 w-4" />
            </Button>
          </div>
        ))}
        {fieldsByModule[module] && (
          <label className="m-0 flex flex-col gap-1 font-normal md:flex-row md:items-center md:gap-2">
            <span className="text-sm text-gray-600 md:w-[160px] md:text-right">
              {t("LBL_CONVERT_SETTINGS_ADD_FIELD")}
            </span>
            <select
              className={`${selectClassName} md:max-w-[320px]`}
              value=""
              data-testid="lead-mapping-defaults-add"
              onChange={(e) => {
                if (e.target.value) change({ ...values, [e.target.value]: "" });
              }}
            >
              <option value="">{t("LBL_CONVERT_SETTINGS_NONE")}</option>
              {addable.map((field) => (
                <option key={field.name} value={field.name}>
                  {field.label}
                </option>
              ))}
            </select>
          </label>
        )}
      </div>
      <SaveBar
        t={t}
        saving={saving}
        dirty={dirtyModules.length > 0}
        message={message}
        errors={errors}
        onSave={() => void handleSave()}
        testId="lead-mapping-save-defaults"
      />
    </div>
  );
}

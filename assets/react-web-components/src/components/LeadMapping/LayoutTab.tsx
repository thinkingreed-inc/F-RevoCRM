import { useMemo, useState } from "react";
import { ArrowDown, ArrowUp, Plus, X } from "lucide-react";
import { Button } from "../ui/button";
import type { ConvertModule } from "../../types/leadConvert";
import type {
  LeadMappingInfo,
  SettingsSaveResult,
} from "../../types/leadMapping";
import {
  TARGET_MODULES,
  displayedLayout,
  moveItem,
} from "./utils/mappingRules";
import { ModuleSwitcher, SaveBar, type Translate } from "./parts";
import { runSave } from "./utils/runSave";
import type { SaveMode } from "./hooks/useLeadMappingSettings";

interface LayoutTabProps {
  info: LeadMappingInfo;
  t: Translate;
  save: (
    mode: SaveMode,
    params: Record<string, string>,
  ) => Promise<SettingsSaveResult>;
}

function initialLayouts(
  info: LeadMappingInfo,
): Record<ConvertModule, string[]> {
  return {
    Accounts: displayedLayout(
      info.modules.Accounts.layout,
      info.modules.Accounts.inputFields,
    ),
    Contacts: displayedLayout(
      info.modules.Contacts.layout,
      info.modules.Contacts.inputFields,
    ),
    Potentials: displayedLayout(
      info.modules.Potentials.layout,
      info.modules.Potentials.inputFields,
    ),
  };
}

/**
 * 昇格画面に出す項目と並び順（必須項目は外せない）
 */
export function LayoutTab({ info, t, save }: LayoutTabProps) {
  const [module, setModule] = useState<ConvertModule>("Accounts");
  const [layouts, setLayouts] = useState(() => initialLayouts(info));
  const [dirtyModules, setDirtyModules] = useState<ConvertModule[]>([]);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<string[]>([]);
  const [filter, setFilter] = useState("");

  const inputFields = info.modules[module].inputFields;
  const byName = useMemo(
    () => new Map(inputFields.map((field) => [field.name, field])),
    [inputFields],
  );
  const layout = layouts[module];
  const available = inputFields.filter(
    (field) =>
      !layout.includes(field.name) &&
      field.label.toLowerCase().includes(filter.trim().toLowerCase()),
  );
  const labels = Object.fromEntries(
    TARGET_MODULES.map((m) => [m, info.modules[m].label]),
  ) as Record<ConvertModule, string>;

  const change = (next: string[]) => {
    setLayouts((prev) => ({ ...prev, [module]: next }));
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
          save("saveLayout", {
            target,
            fields: JSON.stringify(layouts[target]),
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
    <div data-testid="lead-mapping-tab-layout">
      <p className="mt-0 mb-3 text-sm text-gray-600">
        {t("LBL_CONVERT_SETTINGS_LAYOUT_HELP")}
      </p>
      <ModuleSwitcher
        modules={TARGET_MODULES}
        labels={labels}
        value={module}
        onChange={setModule}
        name="layout-module"
      />
      <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <section>
          <h4 className="mt-0 mb-2 text-sm font-semibold text-gray-700">
            {t("LBL_CONVERT_SETTINGS_DISPLAYED")}
          </h4>
          <ol
            className="m-0 list-none space-y-1 p-0"
            data-testid="lead-mapping-layout-displayed"
          >
            {layout.map((name, index) => {
              const field = byName.get(name);
              const mandatory = field?.mandatory ?? false;
              return (
                <li
                  key={name}
                  data-field={name}
                  className="flex items-center gap-2 rounded border border-gray-200 bg-white px-2 py-1.5 text-sm"
                >
                  <span className="w-6 text-right text-xs text-gray-400">
                    {index + 1}
                  </span>
                  <span className="flex-1 truncate">
                    {field?.label ?? name}
                  </span>
                  {mandatory && (
                    <span
                      className="rounded bg-red-50 px-1.5 py-0.5 text-[11px] text-red-700"
                      title={t("LBL_CONVERT_SETTINGS_ALWAYS_SHOWN")}
                    >
                      {t("LBL_CONVERT_SETTINGS_MANDATORY")}
                    </span>
                  )}
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label={t("LBL_CONVERT_SETTINGS_MOVE_UP")}
                    disabled={index === 0}
                    onClick={() => change(moveItem(layout, index, -1))}
                  >
                    <ArrowUp className="h-4 w-4" />
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label={t("LBL_CONVERT_SETTINGS_MOVE_DOWN")}
                    disabled={index === layout.length - 1}
                    onClick={() => change(moveItem(layout, index, 1))}
                  >
                    <ArrowDown className="h-4 w-4" />
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`${t("LBL_CONVERT_SETTINGS_REMOVE")} ${field?.label ?? name}`}
                    disabled={mandatory}
                    title={
                      mandatory
                        ? t("LBL_CONVERT_SETTINGS_ALWAYS_SHOWN")
                        : undefined
                    }
                    onClick={() =>
                      change(layout.filter((item) => item !== name))
                    }
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </li>
              );
            })}
          </ol>
        </section>
        <section>
          <h4 className="mt-0 mb-2 text-sm font-semibold text-gray-700">
            {t("LBL_CONVERT_SETTINGS_AVAILABLE")}
          </h4>
          <input
            type="search"
            value={filter}
            onChange={(e) => setFilter(e.target.value)}
            placeholder={t(
              "LBL_PLACEHOLDER_SEARCH",
              t("LBL_CONVERT_SETTINGS_AVAILABLE"),
            )}
            className="mb-2 h-[32px] w-full rounded border border-gray-300 px-2 text-sm"
          />
          <ul
            className="m-0 max-h-[420px] list-none space-y-1 overflow-y-auto p-0"
            data-testid="lead-mapping-layout-available"
          >
            {available.map((field) => (
              <li
                key={field.name}
                className="flex items-center gap-2 rounded border border-dashed border-gray-300 px-2 py-1.5 text-sm"
              >
                <span className="flex-1 truncate">{field.label}</span>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => change([...layout, field.name])}
                  aria-label={`${t("LBL_CONVERT_SETTINGS_ADD")} ${field.label}`}
                >
                  <Plus className="h-3.5 w-3.5" aria-hidden="true" />
                  {t("LBL_CONVERT_SETTINGS_ADD")}
                </Button>
              </li>
            ))}
          </ul>
        </section>
      </div>
      <SaveBar
        t={t}
        saving={saving}
        dirty={dirtyModules.length > 0}
        message={message}
        errors={errors}
        onSave={() => void handleSave()}
        testId="lead-mapping-save-layout"
      />
    </div>
  );
}

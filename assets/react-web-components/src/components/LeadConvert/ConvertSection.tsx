import { AlertTriangle, Info, Building2, User, Briefcase } from "lucide-react";
import { FieldRenderer } from "../FieldRenderer";
import { ReferenceField } from "../ReferenceField";
import { cn } from "../../lib/utils";
import { SegmentedControl } from "../ui/segmented-control";
import type { FieldInfo, FieldValue } from "../../types/field";
import type {
  ConvertMode,
  ConvertModule,
  ConvertModuleInfo,
  ConvertRecordSummary,
  ConvertSectionState,
} from "../../types/leadConvert";
import { EXISTING_ERROR_KEY } from "./utils/convertRules";

type Translate = (key: string, ...args: (string | number)[]) => string;

const ICONS: Record<ConvertModule, typeof Building2> = {
  Accounts: Building2,
  Contacts: User,
  Potentials: Briefcase,
};

export interface ConvertSectionProps {
  module: ConvertModule;
  moduleInfo: ConvertModuleInfo;
  state: ConvertSectionState;
  /** 表示する項目（設定の項目 + 追加表示した項目）のメタ情報 */
  fields: FieldInfo[];
  /** 追加表示した項目名 */
  extraFields: string[];
  errors: Record<string, string>;
  /** この モジュールを強制している側のモジュール名（表示用ラベル） */
  forcedByLabel: string | null;
  summary: ConvertRecordSummary | null;
  /** 担当者セクションに出す所属企業の注意（なければ null） */
  notice: { tone: "warning" | "info"; message: string } | null;
  disabled: boolean;
  t: Translate;
  onModeChange: (module: ConvertModule, mode: ConvertMode) => void;
  onValueChange: (
    module: ConvertModule,
    name: string,
    value: FieldValue,
  ) => void;
  onExistingChange: (module: ConvertModule, id: string, label: string) => void;
}

/**
 * 昇格先 1 モジュール分のセクション（新規作成／既存に紐づける／作成しない）
 */
export function ConvertSection({
  module,
  moduleInfo,
  state,
  fields,
  extraFields,
  errors,
  forcedByLabel,
  summary,
  notice,
  disabled,
  t,
  onModeChange,
  onValueChange,
  onExistingChange,
}: ConvertSectionProps) {
  const Icon = ICONS[module];
  const attachable = module !== "Potentials";
  const modes: ConvertMode[] = attachable
    ? ["create", "existing", "none"]
    : ["create", "none"];
  const match = moduleInfo.match;
  const showMatch = attachable && match !== null && state.mode === "existing";
  const regularFields = fields.filter(
    (field) => !extraFields.includes(field.name),
  );
  const addedFields = fields.filter((field) =>
    extraFields.includes(field.name),
  );

  const modeLabel = (mode: ConvertMode) =>
    mode === "create"
      ? t("LBL_CONVERT_MODE_CREATE")
      : mode === "existing"
        ? t("LBL_CONVERT_MODE_EXISTING")
        : t("LBL_CONVERT_MODE_NONE");

  const renderField = (field: FieldInfo) => (
    <FieldRenderer
      key={field.name}
      field={field}
      value={state.values[field.name]}
      onChange={(name, value) => onValueChange(module, name, value)}
      disabled={disabled}
      error={errors[field.name]}
      className="w-full flex-col items-stretch md:flex-row md:items-start"
      labelClassName="w-full text-left leading-normal md:w-[140px] md:text-right md:leading-[30px]"
      formData={state.values}
      module={module}
    />
  );

  const hasBody = state.mode !== "none" || forcedByLabel !== null;

  return (
    <section
      className="rounded border border-gray-200 bg-white"
      aria-labelledby={`lead-convert-${module}-title`}
      data-testid={`lead-convert-section-${module}`}
    >
      <div
        className={cn(
          "flex flex-col gap-3 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5",
          hasBody && "border-b border-gray-200",
        )}
      >
        <h3
          id={`lead-convert-${module}-title`}
          className="m-0 flex items-center gap-2 text-base leading-normal font-semibold text-gray-800"
        >
          <Icon className="h-4 w-4 text-[#596875]" aria-hidden="true" />
          {moduleInfo.label}
        </h3>
        <SegmentedControl
          ariaLabel={moduleInfo.label}
          value={state.mode}
          disabled={disabled}
          onChange={(mode) => onModeChange(module, mode)}
          options={modes.map((mode) => ({
            value: mode,
            label: modeLabel(mode),
            disabled: mode === "none" && forcedByLabel !== null,
          }))}
        />
      </div>

      {hasBody && (
        <div className="space-y-4 px-4 py-4 sm:px-5 sm:py-5">
          {forcedByLabel !== null && (
            <p className="m-0 flex items-center gap-1 text-xs text-gray-500">
              <Info className="h-3.5 w-3.5" aria-hidden="true" />
              {t("LBL_CONVERT_FORCED_ON", forcedByLabel)}
            </p>
          )}

          {showMatch && match && (
            <div
              role="status"
              className="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm leading-relaxed text-amber-900"
              data-testid={`lead-convert-match-${module}`}
            >
              <p className="m-0 flex items-start gap-2 font-semibold">
                <AlertTriangle
                  className="mt-0.5 h-4 w-4 shrink-0"
                  aria-hidden="true"
                />
                {t(
                  "LBL_CONVERT_MATCH_FOUND",
                  moduleInfo.label,
                  match.ruleLabel,
                  match.total,
                )}
              </p>
              <p className="m-0 mt-1.5 pl-6 text-xs">
                {t("LBL_CONVERT_MATCH_HINT")}
              </p>
              {match.records.length > 1 && (
                <fieldset className="m-0 mt-3 border-0 p-0 pl-6">
                  <legend className="mb-1 w-auto border-0 p-0 text-xs font-semibold text-amber-900">
                    {t("LBL_CONVERT_MATCH_CANDIDATES")}
                  </legend>
                  <div className="flex flex-col gap-1.5">
                    {match.records.map((record) => (
                      <label
                        key={record.id}
                        className="m-0 flex cursor-pointer items-center gap-2 text-sm font-normal"
                      >
                        <input
                          type="radio"
                          className="m-0"
                          name={`lead-convert-candidate-${module}`}
                          checked={state.existingId === String(record.id)}
                          disabled={disabled}
                          onChange={() =>
                            onExistingChange(
                              module,
                              String(record.id),
                              record.label,
                            )
                          }
                        />
                        <span>{record.label}</span>
                        {record.accountLabel && (
                          <span className="text-xs text-amber-800">
                            （{record.accountLabel}）
                          </span>
                        )}
                      </label>
                    ))}
                  </div>
                </fieldset>
              )}
            </div>
          )}

          {state.mode === "existing" && (
            <div className="space-y-3">
              <ReferenceField
                name={`${module}_existing`}
                label={t("LBL_CONVERT_SELECT_EXISTING")}
                referenceModules={[module]}
                referenceModuleLabels={{ [module]: moduleInfo.label }}
                value={state.existingId}
                displayValue={state.existingLabel || undefined}
                onChange={(_name, value, record) =>
                  onExistingChange(module, value, record?.label ?? "")
                }
                mandatory
                disabled={disabled}
                error={errors[EXISTING_ERROR_KEY]}
                className="w-full flex-col items-stretch md:flex-row md:items-start"
                labelClassName="w-full text-left leading-normal md:w-[140px] md:text-right md:leading-[30px]"
              />
              {summary &&
                summary.id === Number(state.existingId) &&
                summary.fields.length > 0 && (
                  <dl
                    className="m-0 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-relaxed md:ml-[148px]"
                    data-testid={`lead-convert-summary-${module}`}
                  >
                    {summary.fields.map((item) => (
                      <div key={item.label} className="contents">
                        <dt className="text-gray-500">{item.label}</dt>
                        <dd className="m-0 break-all text-gray-800">
                          {item.value}
                        </dd>
                      </div>
                    ))}
                  </dl>
                )}
              {notice && (
                <p
                  role="status"
                  className={cn(
                    "m-0 flex items-start gap-2 rounded-md px-4 py-3 text-sm leading-relaxed md:ml-[148px]",
                    notice.tone === "warning"
                      ? "border border-amber-300 bg-amber-50 text-amber-900"
                      : "border border-gray-200 bg-gray-50 text-gray-700",
                  )}
                >
                  {notice.tone === "warning" ? (
                    <AlertTriangle
                      className="mt-0.5 h-4 w-4 shrink-0"
                      aria-hidden="true"
                    />
                  ) : (
                    <Info
                      className="mt-0.5 h-4 w-4 shrink-0"
                      aria-hidden="true"
                    />
                  )}
                  {notice.message}
                </p>
              )}
            </div>
          )}

          {state.mode === "create" && (
            <div className="space-y-3">
              {regularFields.length === 0 && addedFields.length === 0 && (
                <p className="m-0 text-sm text-gray-500">
                  {t("LBL_CONVERT_NO_FIELDS")}
                </p>
              )}
              {regularFields.map(renderField)}
              {addedFields.length > 0 && (
                <div className="space-y-3 rounded-md border border-red-200 bg-red-50/40 p-4">
                  <p className="m-0 text-xs font-semibold text-red-700">
                    {t("LBL_CONVERT_ADDED_FIELD")}
                  </p>
                  {addedFields.map(renderField)}
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </section>
  );
}

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { Loader2, XCircle } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "../ui/dialog";
import { Button } from "../ui/button";
import { FieldRenderer } from "../FieldRenderer";
import { TranslationProvider } from "../../contexts/TranslationContext";
import { useTranslation } from "../../hooks/useTranslation";
import type { FieldInfo, FieldValue } from "../../types/field";
import {
  CONVERT_MODULES,
  type AttachableModule,
  type ConvertMode,
  type ConvertModule,
  type ConvertRecordSummary,
  type ConvertSections,
} from "../../types/leadConvert";
import { useLeadConvert } from "./hooks/useLeadConvert";
import { ConvertSection } from "./ConvertSection";
import {
  applyServerErrors,
  buildPayload,
  changeMode,
  defaultTransfer,
  emptyFieldErrors,
  forcedBy,
  hasErrors,
  initialSections,
  validateSections,
  visibleFields,
  type FieldErrors,
} from "./utils/convertRules";

export interface LeadConvertProps {
  /** 昇格するリードのレコードID */
  recordId: number | string;
  /** ダイアログの開閉 */
  isOpen?: boolean | string;
  /** 昇格に成功したとき（detail: { redirectUrl, entityIds }） */
  onConverted?: (detail: {
    redirectUrl: string;
    entityIds: Partial<Record<ConvertModule, number>>;
  }) => void;
  onCancel?: () => void;
  onOpenChange?: (open: boolean) => void;
}

/**
 * リード昇格画面（<lead-convert>）
 *
 * TranslationProvider でリードの翻訳を読み込んでから本体を描画する。
 */
export function LeadConvert(props: LeadConvertProps) {
  return (
    <TranslationProvider module="Leads">
      <LeadConvertDialog {...props} />
    </TranslationProvider>
  );
}

function LeadConvertDialog({
  recordId,
  isOpen = true,
  onConverted,
  onCancel,
  onOpenChange,
}: LeadConvertProps) {
  const { t } = useTranslation();
  const id = Number(recordId);
  const {
    info,
    fieldsByModule,
    ownerField,
    loading,
    error,
    fetchSummary,
    submit,
  } = useLeadConvert(id);
  const [open, setOpen] = useState(isOpen === true || isOpen === "true");
  const [sections, setSections] = useState<ConvertSections | null>(null);
  const [assignedUserId, setAssignedUserId] = useState("");
  const [transferTo, setTransferTo] = useState<AttachableModule>("Contacts");
  const [fieldErrors, setFieldErrors] =
    useState<FieldErrors>(emptyFieldErrors());
  const [generalErrors, setGeneralErrors] = useState<string[]>([]);
  const [summaries, setSummaries] = useState<
    Partial<Record<AttachableModule, ConvertRecordSummary>>
  >({});
  const [submitting, setSubmitting] = useState(false);
  const bodyRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setOpen(isOpen === true || isOpen === "true");
  }, [isOpen]);

  useEffect(() => {
    if (info && sections === null) {
      const initial = initialSections(info);
      setSections(initial);
      setAssignedUserId(info.assignedUserId);
      setTransferTo(defaultTransfer(initial));
    }
  }, [info, sections]);

  // 既存レコードを選んだら、その主な情報（担当者は所属企業も）を取る
  const accountExistingId =
    sections?.Accounts.mode === "existing" ? sections.Accounts.existingId : "";
  const contactExistingId =
    sections?.Contacts.mode === "existing" ? sections.Contacts.existingId : "";
  useEffect(() => {
    const targets: Array<[AttachableModule, string]> = [
      ["Accounts", accountExistingId],
      ["Contacts", contactExistingId],
    ];
    for (const [module, existingId] of targets) {
      if (!existingId || summaries[module]?.id === Number(existingId)) {
        continue;
      }
      void fetchSummary(module, existingId).then((summary) => {
        if (summary) {
          setSummaries((prev) => ({ ...prev, [module]: summary }));
        }
      });
    }
  }, [accountExistingId, contactExistingId, fetchSummary, summaries]);

  const handleOpenChange = useCallback(
    (next: boolean) => {
      setOpen(next);
      onOpenChange?.(next);
      if (!next) {
        onCancel?.();
      }
    },
    [onCancel, onOpenChange],
  );

  const forced = useMemo(
    () => (sections && info ? forcedBy(sections, info.constraints) : {}),
    [sections, info],
  );

  const handleModeChange = useCallback(
    (module: ConvertModule, mode: ConvertMode) => {
      if (!info) return;
      setSections((prev) =>
        prev ? changeMode(prev, module, mode, info) : prev,
      );
    },
    [info],
  );

  // 引き継ぎ先のモジュールを作らないことにしたら、作るほうへ寄せる
  useEffect(() => {
    if (sections && sections[transferTo].mode === "none") {
      setTransferTo(defaultTransfer(sections));
    }
  }, [sections, transferTo]);

  const handleValueChange = useCallback(
    (module: ConvertModule, name: string, value: FieldValue) => {
      setSections((prev) =>
        prev
          ? {
              ...prev,
              [module]: {
                ...prev[module],
                values: { ...prev[module].values, [name]: value },
              },
            }
          : prev,
      );
      setFieldErrors((prev) => {
        if (!prev[module][name]) return prev;
        const moduleErrors = { ...prev[module] };
        delete moduleErrors[name];
        return { ...prev, [module]: moduleErrors };
      });
    },
    [],
  );

  const handleExistingChange = useCallback(
    (module: ConvertModule, existingId: string, label: string) => {
      setSections((prev) =>
        prev
          ? {
              ...prev,
              [module]: { ...prev[module], existingId, existingLabel: label },
            }
          : prev,
      );
    },
    [],
  );

  // エラーは本文の先頭に出すため、そこまで戻す（jsdom には scrollTo が無い）
  const scrollToTop = () => {
    const body = bodyRef.current;
    if (body && typeof body.scrollTo === "function") {
      body.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  const handleSubmit = useCallback(async () => {
    if (!info || !sections) return;
    const local = validateSections(sections, fieldsByModule, info, t);
    setFieldErrors(local.fieldErrors);
    setGeneralErrors(local.generalErrors);
    if (hasErrors(local.fieldErrors, local.generalErrors)) {
      scrollToTop();
      return;
    }
    setSubmitting(true);
    try {
      const result = await submit(
        buildPayload(sections, info, assignedUserId, transferTo),
      );
      if (result.converted && result.redirectUrl) {
        onConverted?.({
          redirectUrl: result.redirectUrl,
          entityIds: result.entityIds ?? {},
        });
        window.location.assign(result.redirectUrl);
        return;
      }
      const applied = applyServerErrors(
        sections,
        result.errors,
        fieldsByModule,
      );
      setSections(applied.sections);
      setFieldErrors(applied.fieldErrors);
      setGeneralErrors(
        applied.generalErrors.length > 0 || !hasErrors(applied.fieldErrors, [])
          ? applied.generalErrors
          : [t("LBL_CONVERT_FIX_FIELDS")],
      );
      scrollToTop();
    } catch (err) {
      setGeneralErrors([err instanceof Error ? err.message : String(err)]);
      scrollToTop();
    } finally {
      setSubmitting(false);
    }
  }, [
    info,
    sections,
    fieldsByModule,
    t,
    submit,
    assignedUserId,
    transferTo,
    onConverted,
  ]);

  const contactNotice = (): {
    tone: "warning" | "info";
    message: string;
  } | null => {
    if (
      !sections ||
      sections.Contacts.mode !== "existing" ||
      sections.Accounts.mode === "none"
    ) {
      return null;
    }
    const summary = summaries.Contacts;
    if (!summary || summary.id !== Number(sections.Contacts.existingId)) {
      return null;
    }
    if (summary.accountId === null) {
      return { tone: "info", message: t("LBL_CONVERT_CONTACT_ACCOUNT_SET") };
    }
    const sameAccount =
      sections.Accounts.mode === "existing" &&
      Number(sections.Accounts.existingId) === summary.accountId;
    return sameAccount
      ? null
      : {
          tone: "warning",
          message: t(
            "LBL_CONVERT_CONTACT_OTHER_ACCOUNT",
            summary.accountLabel ?? "",
          ),
        };
  };

  const title = info
    ? `${t("LBL_CONVERT_TITLE")}: ${info.record.label}`
    : t("LBL_CONVERT_TITLE");

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent
        className="flex h-[100dvh] max-h-[100dvh] w-screen max-w-none flex-col gap-0 rounded-none p-0 sm:h-auto sm:max-h-[90vh] sm:w-full sm:max-w-[820px] sm:rounded-lg"
        closeButtonClassName="absolute top-3 right-3 text-white opacity-80 hover:opacity-100"
        portalClassName="lead-convert-dialog-portal"
        onEscapeKeyDown={(e) => {
          if (document.querySelector("[data-rwc-dropdown]")) {
            e.preventDefault();
          }
        }}
        data-testid="lead-convert-dialog"
      >
        <div className="bg-[#2c3b49] px-5 py-3 pr-12 text-white sm:rounded-t-lg">
          <DialogTitle className="m-0 truncate text-base font-semibold text-white">
            {title}
          </DialogTitle>
          <DialogDescription className="sr-only">
            {t("LBL_CONVERT_TITLE")}
          </DialogDescription>
        </div>

        <div
          ref={bodyRef}
          className="flex-1 space-y-4 overflow-y-auto bg-gray-50 px-3 py-4 sm:px-5"
        >
          {(error || generalErrors.length > 0) && (
            <div
              role="alert"
              className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800"
              data-testid="lead-convert-errors"
            >
              <p className="m-0 flex items-center gap-2 font-semibold">
                <XCircle className="h-4 w-4" aria-hidden="true" />
                {error
                  ? t("LBL_CONVERT_LOAD_FAILED")
                  : t("LBL_CONVERT_ERRORS_TITLE")}
              </p>
              <ul className="m-0 mt-1 list-disc pl-8">
                {(error ? [error] : generalErrors).map((message) => (
                  <li key={message}>{message}</li>
                ))}
              </ul>
            </div>
          )}

          {loading && (
            <div className="flex items-center justify-center py-12 text-gray-500">
              <Loader2 className="h-6 w-6 animate-spin" aria-hidden="true" />
              <span className="ml-2">{t("LBL_LOADING")}</span>
            </div>
          )}

          {info &&
            sections &&
            CONVERT_MODULES.filter(
              (module) => info.modules[module].available,
            ).map((module) => {
              const names = visibleFields(info, sections, module);
              const meta = names
                .map((name) =>
                  fieldsByModule[module].find((field) => field.name === name),
                )
                .filter((field): field is FieldInfo => field !== undefined);
              const forcedModule = forced[module];
              return (
                <ConvertSection
                  key={module}
                  module={module}
                  moduleInfo={info.modules[module]}
                  state={sections[module]}
                  fields={meta}
                  extraFields={sections[module].extraFields}
                  errors={fieldErrors[module]}
                  forcedByLabel={
                    forcedModule ? info.modules[forcedModule].label : null
                  }
                  summary={
                    module === "Potentials" ? null : (summaries[module] ?? null)
                  }
                  notice={module === "Contacts" ? contactNotice() : null}
                  disabled={submitting}
                  t={t}
                  onModeChange={handleModeChange}
                  onValueChange={handleValueChange}
                  onExistingChange={handleExistingChange}
                />
              );
            })}

          {info && sections && (
            <section className="space-y-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
              <h3 className="m-0 text-base font-semibold text-gray-800">
                {t("LBL_CONVERT_COMMON")}
              </h3>
              {ownerField && (
                <FieldRenderer
                  field={{ ...ownerField, mandatory: true }}
                  value={assignedUserId}
                  onChange={(_name, value) =>
                    setAssignedUserId(String(value ?? ""))
                  }
                  disabled={submitting}
                  className="w-full flex-col items-stretch md:flex-row md:items-start"
                  labelClassName="w-full text-left leading-normal md:w-[140px] md:text-right md:leading-[30px]"
                  module="Leads"
                />
              )}
              <fieldset className="m-0 flex flex-col gap-2 border-0 p-0 md:flex-row md:items-center">
                <legend className="float-left mb-1 w-full border-0 p-0 text-sm leading-normal text-gray-700 md:mb-0 md:w-[140px] md:pr-2 md:text-right">
                  {t("LBL_TRANSFER_RELATED_RECORD")}
                </legend>
                <div className="flex flex-wrap gap-4">
                  {info.transferOptions.map((option) => (
                    <label
                      key={option.module}
                      className="flex cursor-pointer items-center gap-2 text-sm"
                    >
                      <input
                        type="radio"
                        name="lead-convert-transfer"
                        value={option.module}
                        checked={transferTo === option.module}
                        disabled={
                          submitting || sections[option.module].mode === "none"
                        }
                        onChange={() => setTransferTo(option.module)}
                      />
                      {option.label}
                    </label>
                  ))}
                </div>
              </fieldset>
            </section>
          )}
        </div>

        <div className="flex flex-col-reverse gap-2 border-t border-gray-200 bg-white px-4 py-3 sm:flex-row sm:justify-end sm:rounded-b-lg">
          <Button
            type="button"
            variant="outline"
            className="h-10 sm:h-9"
            onClick={() => handleOpenChange(false)}
            disabled={submitting}
          >
            {t("LBL_CANCEL")}
          </Button>
          <Button
            type="button"
            className="h-10 !bg-blue-600 font-bold !text-white hover:!bg-blue-700 sm:h-9"
            onClick={() => void handleSubmit()}
            disabled={submitting || !info || !sections}
            data-testid="lead-convert-submit"
          >
            {submitting && (
              <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
            )}
            {submitting ? t("LBL_CONVERT_SUBMITTING") : t("LBL_CONVERT_SUBMIT")}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default LeadConvert;

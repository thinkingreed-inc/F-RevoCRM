import React, { useEffect, useRef } from "react";
import { Input } from "@/components/ui/input";
import { Textarea } from "../ui/textarea";
import { ParameterRecord, ParameterType } from "./types";
import { useTranslation } from "@/hooks/useTranslation";
import { ToggleSwitch } from "@/components/ui/toggle-switch";

interface ParameterEditFormProps {
  /** レコードデータ */
  record: ParameterRecord;
  /** 編集中の値 */
  value: string;
  /** 値変更ハンドラ */
  onValueChange: (value: string) => void;
  /** シークレット設定 */
  secret: boolean;
  /** シークレット変更ハンドラ */
  onSecretChange: (secret: boolean) => void;
  /** 説明文 */
  description?: string;
  /** 説明文変更ハンドラ */
  onDescriptionChange?: (description: string) => void;
  /** バリデーションエラー */
  error?: string | null;
  /** 無効状態 */
  disabled?: boolean;
}

// 表示位置調整用の共通クラス。
// クイック作成（QuickCreateForm / FieldRenderer）と同じ指定に揃えている。
const FIELDS_CLASS = "space-y-3 pr-8";
const ROW_CLASS = "flex items-start gap-2";
const LABEL_CLASS =
  "text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]";
/** クイック作成で必須マークが入る位置。入力の開始位置を揃えるためのスペーサー */
const LABEL_SPACER_CLASS = "w-3 flex-shrink-0";
const RIGHT_COLUMN_CLASS = "flex-1 min-w-0";

const adjustTextareaHeight = (textarea: HTMLTextAreaElement | null) => {
  if (!textarea) {
    return;
  }

  textarea.style.height = "auto";
  textarea.style.height = `${textarea.scrollHeight}px`;
};

const isBooleanTrue = (currentValue: string): boolean => {
  const normalized = String(currentValue).toLowerCase();
  return normalized === "true" || normalized === "1";
};

/**
 * ParameterEditForm - システム変数編集フォーム
 *
 * 型に応じた入力UIを表示:
 * - boolean: トグルスイッチ
 * - integer: 数値入力
 * - string: テキスト入力
 */
export const ParameterEditForm: React.FC<ParameterEditFormProps> = ({
  record,
  value,
  onValueChange,
  secret,
  onSecretChange,
  description,
  onDescriptionChange,
  error,
  disabled = false,
}) => {
  const { t } = useTranslation();
  const descriptionRef = useRef<HTMLTextAreaElement | null>(null);

  // シークレットを解除しようとしている状態。値の再入力が必要になる
  const releasingSecret = record.secret === 1 && !secret;

  useEffect(() => {
    adjustTextareaHeight(descriptionRef.current);
  }, [description]);

  const renderValueInput = (type: ParameterType) => {
    if (type === "boolean") {
      return (
        <ToggleSwitch
          value={isBooleanTrue(value)}
          onChange={(checked) => onValueChange(checked ? "true" : "false")}
          disabled={disabled}
          trueLabel={t("LBL_TRUE")}
          falseLabel={t("LBL_FALSE")}
        />
      );
    }

    if (type === "integer") {
      return (
        <Input
          type="number"
          value={value}
          onChange={(e) => onValueChange(e.target.value)}
          disabled={disabled}
          className="w-full max-w-[200px]"
        />
      );
    }

    return (
      <Input
        type="text"
        value={value}
        onChange={(e) => onValueChange(e.target.value)}
        disabled={disabled}
        maxLength={512}
      />
    );
  };

  /** 項目 1 行分。ラベル幅と入力の開始位置をクイック作成と揃える */
  const renderRow = (label: string, children: React.ReactNode) => (
    <div className={ROW_CLASS}>
      <span className={LABEL_CLASS}>{label}</span>
      <span className={LABEL_SPACER_CLASS} aria-hidden="true" />
      <div className={RIGHT_COLUMN_CLASS}>{children}</div>
    </div>
  );

  return (
    <div className="flex-1 overflow-auto px-8 py-4 text-md">
      {/* キー（読み取り専用） */}
      <h4 className="fieldBlockHeader font-bold leading-[1.1] mt-0 mb-2 pb-1 border-b border-gray-300">
        {record.key}
      </h4>

      <div className={FIELDS_CLASS}>
        {/* 値 */}
        {renderRow(
          t("Value"),
          <>
            {renderValueInput(record.type)}

            {/* シークレット変数は現在の値を取得できないため、値の扱いを明示する */}
            {record.secret === 1 && (
              <p className="mt-1 text-xs text-muted-foreground">
                {releasingSecret
                  ? t("LBL_SECRET_RELEASE_REQUIRES_VALUE")
                  : t("LBL_SECRET_VALUE_HIDDEN")}
              </p>
            )}

            {error && <p className="mt-2 text-sm text-destructive">{error}</p>}
          </>,
        )}

        {/* 備考 */}
        {record.description !== undefined &&
          renderRow(
            t("Description"),
            <Textarea
              ref={descriptionRef}
              value={description ?? ""}
              onChange={(e) => {
                onDescriptionChange?.(e.target.value);
                adjustTextareaHeight(e.currentTarget);
              }}
              rows={3}
              disabled={disabled}
              className="w-full resize-none overflow-hidden pt-[9px]"
            />,
          )}

        {/* シークレット設定（boolean は値が 2 択しかなくマスクの意味がないため出さない） */}
        {record.type !== "boolean" &&
          renderRow(
            t("LBL_SECRET"),
            <>
              <ToggleSwitch
                value={secret}
                onChange={onSecretChange}
                disabled={disabled}
                trueLabel={t("LBL_SECRET_ON")}
                falseLabel={t("LBL_SECRET_OFF")}
              />
              <p className="mt-1 text-xs text-muted-foreground">
                {t("LBL_SECRET_HELP")}
              </p>
            </>,
          )}
      </div>
    </div>
  );
};

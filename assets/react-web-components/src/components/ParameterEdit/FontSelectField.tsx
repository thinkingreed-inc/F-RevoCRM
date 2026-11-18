import React, { useEffect, useRef, useState } from "react";
import { Loader2, Upload } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { useTranslation } from "@/hooks/useTranslation";
import { MAX_FONT_FILE_SIZE, useFontData } from "./hooks/useFontData";

interface FontSelectFieldProps {
  /** 選択中のフォント名（空は既定のフォント） */
  value: string;
  /** 選択変更ハンドラ */
  onChange: (value: string) => void;
  /** 無効状態 */
  disabled?: boolean;
  /** アップロード中かどうかの変化（アップロード中に保存させないために使う） */
  onBusyChange?: (busy: boolean) => void;
}

const SELECT_CLASS = cn(
  "w-full h-[30px] px-2 text-md border border-input rounded-sm shadow-xs bg-transparent transition-[color,box-shadow]",
  "focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring",
  "disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50",
);

/**
 * FontSelectField - PDF_DEFAULT_FONT（font 型）の入力
 *
 * libraries/tcpdf/fonts/ にあるフォントから選ぶ。
 * 「フォントを追加」で TrueType フォント（.ttf）をアップロードすると、
 * サーバーで TCPDF 用に変換して一覧に加え、そのフォントを選んだ状態にする（保存は従来どおり保存ボタンで行う）。
 */
export const FontSelectField: React.FC<FontSelectFieldProps> = ({
  value,
  onChange,
  disabled = false,
  onBusyChange,
}) => {
  const { t } = useTranslation();
  const {
    fonts,
    fallback,
    loading,
    uploading,
    loadError,
    fetchFonts,
    uploadFont,
  } = useFontData();
  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const [addedFont, setAddedFont] = useState<string | null>(null);

  useEffect(() => {
    fetchFonts();
  }, [fetchFonts]);

  // アップロード中に保存すると、追加前の値で保存してダイアログが閉じてしまうため、親に知らせて保存を止める
  useEffect(() => {
    onBusyChange?.(uploading);
  }, [uploading, onBusyChange]);
  // アップロード中に閉じられても、処理中のまま残さない
  useEffect(() => () => onBusyChange?.(false), [onBusyChange]);

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    // 同じファイルを選び直しても change が起きるよう、選択を空に戻しておく
    e.target.value = "";
    if (!file) {
      return;
    }
    setUploadError(null);
    setAddedFont(null);

    // サーバーでも同じ確認をするが、大きなファイルを送る前に止める
    if (!file.name.toLowerCase().endsWith(".ttf")) {
      setUploadError(t("LBL_FONT_UPLOAD_INVALID_EXTENSION"));
      return;
    }
    if (file.size > MAX_FONT_FILE_SIZE) {
      setUploadError(t("LBL_FONT_UPLOAD_TOO_LARGE"));
      return;
    }

    const result = await uploadFont(file);
    if (result.success) {
      setAddedFont(result.font);
      onChange(result.font);
    } else {
      setUploadError(result.error || t("LBL_FONT_UPLOAD_FAILED"));
    }
  };

  // 設定済みのフォントが見つからない場合も、今の値として選択肢に残す（黙って未設定に変えない）
  const missingCurrent =
    !loading && !loadError && value !== "" && !fonts.includes(value);

  return (
    <div>
      <div className="flex items-center gap-2">
        <select
          aria-label={t("Value")}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          disabled={disabled || loading || uploading}
          className={SELECT_CLASS}
        >
          <option value="">{t("LBL_PDF_FONT_DEFAULT", fallback)}</option>
          {missingCurrent && (
            <option value={value}>{t("LBL_PDF_FONT_NOT_FOUND", value)}</option>
          )}
          {/* 一覧を取得できなかった場合も、今の値は選べるようにしておく */}
          {loadError && value !== "" && <option value={value}>{value}</option>}
          {fonts.map((font) => (
            <option key={font} value={font}>
              {font}
            </option>
          ))}
        </select>
        {loading && (
          <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
        )}
      </div>

      <div className="mt-2 flex items-center gap-2">
        <input
          ref={fileInputRef}
          type="file"
          accept=".ttf,font/ttf"
          className="hidden"
          data-testid="font-file-input"
          onChange={handleFileChange}
        />
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => fileInputRef.current?.click()}
          disabled={disabled || uploading}
        >
          {uploading ? (
            <>
              <Loader2 className="mr-1 h-4 w-4 animate-spin" />
              {t("LBL_PDF_FONT_UPLOADING")}
            </>
          ) : (
            <>
              <Upload className="mr-1 h-4 w-4" />
              {t("LBL_PDF_FONT_ADD")}
            </>
          )}
        </Button>
      </div>

      {loadError && (
        <p className="mt-1 text-sm text-destructive">
          {t("LBL_PDF_FONT_LOAD_FAILED")}
        </p>
      )}
      {uploadError && (
        <p className="mt-1 text-sm text-destructive" role="alert">
          {uploadError}
        </p>
      )}
      {addedFont && (
        <p className="mt-1 text-sm text-green-700">
          {t("LBL_PDF_FONT_ADDED", addedFont)}
        </p>
      )}
      <p className="mt-1 text-xs text-muted-foreground">
        {t("LBL_PDF_FONT_HELP")}
      </p>
    </div>
  );
};

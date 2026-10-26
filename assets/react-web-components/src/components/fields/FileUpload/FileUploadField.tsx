import React, { useEffect, useRef, useState } from "react";
import { Paperclip, PenLine, Upload, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { useOptionalTranslation } from "@/hooks/useTranslation";
import type { FieldFileInfo, FieldThumbnail } from "@/types/fileUploadField";
import type { WebComponentFieldMode } from "@/types/webComponentField";
import { HandwritingPad } from "./HandwritingPad";
import { buildHandwritingFileName } from "./handwritingFileName";
import {
  fetchFieldThumbnail,
  fetchFileFieldConfig,
  uploadFieldFile,
} from "./fileFieldApi";

/** 翻訳が無いときの表記 */
const FALLBACK_LABELS: Record<string, string> = {
  LBL_FILE_FIELD_SELECT: "ファイルを選択",
  LBL_FILE_FIELD_CHANGE: "変更",
  LBL_FILE_FIELD_REMOVE: "削除",
  LBL_FILE_FIELD_UPLOADING: "アップロード中...",
  LBL_FILE_FIELD_DROP_HERE: "ここにファイルをドロップ",
  LBL_FILE_FIELD_TOO_LARGE: "ファイルが大きすぎます（上限 %s）",
  LBL_FILE_FIELD_HANDWRITING_PLACEHOLDER: "ここに書いてください",
  LBL_FILE_FIELD_HANDWRITING_CLEAR: "書き直す",
  LBL_FILE_FIELD_HANDWRITING_SAVE: "確定",
  LBL_FILE_FIELD_HANDWRITING_REDRAW: "書き直す",
  LBL_FILE_FIELD_CANCEL: "やめる",
  LBL_FILE_FIELD_DOWNLOAD: "ダウンロード",
};

export interface FileUploadFieldProps {
  /** 項目を持つモジュール */
  module: string;
  fieldName: string;
  fieldLabel?: string;
  mode: WebComponentFieldMode;
  /** 保存済みレコードのID（サムネイルの権限確認に使う） */
  recordId?: number;
  /** 現在のファイル */
  file: FieldFileInfo | null;
  /**
   * 現在の値（ドキュメントID）。file が無く値だけ分かっている場合（React 版のクイック作成で
   * 既存レコードを編集する場合など）は、この値からファイル情報を取得する
   */
  value?: string;
  /** 手書き入力の項目か。未指定なら API から取得する */
  handwriting?: boolean;
  /** アップロードできる大きさ（バイト）。未指定なら API から取得する */
  maxUploadSize?: number;
  disabled?: boolean;
  /** 値（ドキュメントID。削除したら空文字）が変わったとき */
  onChange?: (documentId: string, file: FieldFileInfo | null) => void;
  className?: string;
}

function formatFileSize(bytes: number): string {
  if (!bytes) return "";
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * ファイルアップロード項目の入力・表示
 *
 * 編集: ファイルを選ぶ（手書き項目なら書く）と、その場でドキュメントとして登録し、
 *       ドキュメントIDを onChange で返す。レコードの保存時にこのIDが項目に入る。
 * 詳細: 画像ならサムネイル、それ以外はファイル名とダウンロードのリンクを出す。
 */
export const FileUploadField: React.FC<FileUploadFieldProps> = ({
  module,
  fieldName,
  fieldLabel = "",
  mode,
  recordId,
  file: initialFile,
  value,
  handwriting: handwritingProp,
  maxUploadSize: maxUploadSizeProp,
  disabled = false,
  onChange,
  className,
}) => {
  const { t } = useOptionalTranslation();
  const label = (key: string, ...args: (string | number)[]) => {
    const translated = t(key, ...args);
    if (translated !== key) return translated;
    let text = FALLBACK_LABELS[key] ?? key;
    args.forEach((arg) => {
      text = text.replace("%s", String(arg));
    });
    return text;
  };

  const [file, setFile] = useState<FieldFileInfo | null>(initialFile);
  const [thumbnail, setThumbnail] = useState<FieldThumbnail | null>(null);
  const [handwriting, setHandwriting] = useState<boolean | undefined>(
    handwritingProp,
  );
  const [maxUploadSize, setMaxUploadSize] = useState<number | undefined>(
    maxUploadSizeProp,
  );
  const [isEditing, setIsEditing] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [isDragOver, setIsDragOver] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement | null>(null);

  // 親から渡されるファイルが変わったら（別レコードの表示など）追従する
  useEffect(() => {
    setFile(initialFile);
    setThumbnail(null);
  }, [initialFile?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  // 項目の設定が渡されていなければ取得する（React 版のクイック作成など）
  useEffect(() => {
    if (
      mode !== "edit" ||
      (handwritingProp !== undefined && maxUploadSizeProp !== undefined)
    ) {
      return;
    }
    let cancelled = false;
    fetchFileFieldConfig(module, fieldName)
      .then((config) => {
        if (cancelled) return;
        if (handwritingProp === undefined) setHandwriting(config.handwriting);
        if (maxUploadSizeProp === undefined)
          setMaxUploadSize(config.max_upload_size);
      })
      .catch(() => {
        if (!cancelled && handwritingProp === undefined) setHandwriting(false);
      });
    return () => {
      cancelled = true;
    };
  }, [mode, module, fieldName, handwritingProp, maxUploadSizeProp]);

  // 値だけ分かっている場合はファイル情報を取得する
  useEffect(() => {
    if (initialFile || !value || !/^[0-9]+$/.test(value) || Number(value) <= 0)
      return;
    let cancelled = false;
    fetchFieldThumbnail(module, fieldName, Number(value), recordId)
      .then((response) => {
        if (cancelled) return;
        setFile(response.file);
        setThumbnail(response.thumbnail);
      })
      .catch(() => {
        // 参照できないファイルは出さない（値はそのまま残る）
      });
    return () => {
      cancelled = true;
    };
  }, [initialFile, value, module, fieldName, recordId]);

  // 画像ならサムネイルを取得する
  useEffect(() => {
    if (!file || !file.is_image || thumbnail) return;
    let cancelled = false;
    fetchFieldThumbnail(module, fieldName, file.id, recordId)
      .then((response) => {
        if (!cancelled) setThumbnail(response.thumbnail);
      })
      .catch(() => {
        // サムネイルが出せなくてもファイル名は出す
      });
    return () => {
      cancelled = true;
    };
  }, [file, thumbnail, module, fieldName, recordId]);

  const upload = async (blob: Blob, fileName: string) => {
    if (maxUploadSize && blob.size > maxUploadSize) {
      setError(
        label("LBL_FILE_FIELD_TOO_LARGE", formatFileSize(maxUploadSize)),
      );
      return;
    }
    setError(null);
    setIsUploading(true);
    try {
      const response = await uploadFieldFile(module, fieldName, blob, fileName);
      const { thumbnail: uploadedThumbnail, ...uploadedFile } = response;
      setFile(uploadedFile);
      setThumbnail(uploadedThumbnail);
      setIsEditing(false);
      onChange?.(String(uploadedFile.id), uploadedFile);
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setIsUploading(false);
    }
  };

  const handleFileSelected = (selected: File | undefined) => {
    if (!selected) return;
    void upload(selected, selected.name);
  };

  const handleRemove = () => {
    setFile(null);
    setThumbnail(null);
    setError(null);
    setIsEditing(false);
    onChange?.("", null);
  };

  const renderFileSummary = () => {
    if (!file) return null;
    return (
      <div
        className="flex flex-wrap items-start gap-x-3 gap-y-1"
        data-testid="file-field-value"
        data-document-id={file.id}
      >
        {file.is_image && thumbnail && (
          <a
            href={file.download_url}
            title={label("LBL_FILE_FIELD_DOWNLOAD")}
            className="shrink-0"
          >
            <img
              src={thumbnail.data_uri}
              width={thumbnail.width}
              height={thumbnail.height}
              alt={file.filename}
              data-testid="file-field-thumbnail"
              className="max-h-40 max-w-[240px] h-auto w-auto rounded border border-gray-200 bg-white object-contain"
            />
          </a>
        )}
        <div className="flex min-w-0 flex-col gap-0.5">
          <a
            href={file.download_url}
            className="flex items-center gap-1 break-all text-[#15c] hover:underline"
            title={label("LBL_FILE_FIELD_DOWNLOAD")}
            data-testid="file-field-filename"
          >
            <Paperclip className="size-3.5 shrink-0" aria-hidden="true" />
            <span>{file.filename}</span>
          </a>
          {file.filesize > 0 && (
            <span className="text-xs text-gray-500">
              {formatFileSize(file.filesize)}
            </span>
          )}
        </div>
      </div>
    );
  };

  if (mode === "detail") {
    return (
      <div className={cn("fr-file-field", className)}>
        {renderFileSummary()}
      </div>
    );
  }

  const isBusy = disabled || isUploading;
  const showInput = !file || isEditing;
  // 手書きかどうか分かるまでは入力欄を出さない（ファイル選択を一瞬出さないため）
  const inputReady = handwriting !== undefined;

  return (
    <div
      className={cn("fr-file-field flex flex-col gap-2", className)}
      data-testid="file-field"
    >
      {file && !isEditing && (
        <div className="flex flex-col gap-2">
          {renderFileSummary()}
          {!disabled && (
            <div className="flex gap-2">
              <Button
                type="button"
                size="sm"
                variant="outline"
                onClick={() => setIsEditing(true)}
                data-testid="file-field-change"
              >
                {handwriting ? (
                  <PenLine aria-hidden="true" />
                ) : (
                  <Upload aria-hidden="true" />
                )}
                {handwriting
                  ? label("LBL_FILE_FIELD_HANDWRITING_REDRAW")
                  : label("LBL_FILE_FIELD_CHANGE")}
              </Button>
              <Button
                type="button"
                size="sm"
                variant="ghost"
                onClick={handleRemove}
                data-testid="file-field-remove"
              >
                <X aria-hidden="true" />
                {label("LBL_FILE_FIELD_REMOVE")}
              </Button>
            </div>
          )}
        </div>
      )}

      {showInput && inputReady && !disabled && handwriting && (
        <HandwritingPad
          disabled={isBusy}
          onSave={(image) =>
            void upload(image, buildHandwritingFileName(fieldLabel))
          }
          onCancel={file ? () => setIsEditing(false) : undefined}
          labels={{
            clear: label("LBL_FILE_FIELD_HANDWRITING_CLEAR"),
            save: label("LBL_FILE_FIELD_HANDWRITING_SAVE"),
            cancel: label("LBL_FILE_FIELD_CANCEL"),
            placeholder: label("LBL_FILE_FIELD_HANDWRITING_PLACEHOLDER"),
          }}
        />
      )}

      {showInput && inputReady && !disabled && !handwriting && (
        <div
          className={cn(
            "flex flex-wrap items-center gap-2 rounded-md border border-dashed px-3 py-2",
            isDragOver ? "border-blue-400 bg-blue-50" : "border-gray-300",
          )}
          onDragOver={(event) => {
            event.preventDefault();
            setIsDragOver(true);
          }}
          onDragLeave={() => setIsDragOver(false)}
          onDrop={(event) => {
            event.preventDefault();
            setIsDragOver(false);
            if (!isBusy) handleFileSelected(event.dataTransfer.files?.[0]);
          }}
          data-testid="file-field-dropzone"
        >
          <input
            ref={fileInputRef}
            type="file"
            className="hidden"
            data-testid="file-field-input"
            onChange={(event) => {
              handleFileSelected(event.target.files?.[0]);
              event.target.value = "";
            }}
            disabled={isBusy}
          />
          <Button
            type="button"
            size="sm"
            variant="outline"
            onClick={() => fileInputRef.current?.click()}
            disabled={isBusy}
            data-testid="file-field-select"
          >
            <Upload aria-hidden="true" />
            {label("LBL_FILE_FIELD_SELECT")}
          </Button>
          <span className="text-xs text-gray-500">
            {label("LBL_FILE_FIELD_DROP_HERE")}
            {maxUploadSize ? `（${formatFileSize(maxUploadSize)}）` : ""}
          </span>
          {file && (
            <Button
              type="button"
              size="sm"
              variant="ghost"
              onClick={() => setIsEditing(false)}
            >
              {label("LBL_FILE_FIELD_CANCEL")}
            </Button>
          )}
        </div>
      )}

      {isUploading && (
        <span
          className="text-sm text-gray-500"
          data-testid="file-field-uploading"
        >
          {label("LBL_FILE_FIELD_UPLOADING")}
        </span>
      )}
      {error && (
        <span
          className="text-sm text-red-600"
          role="alert"
          data-testid="file-field-error"
        >
          {error}
        </span>
      )}
    </div>
  );
};

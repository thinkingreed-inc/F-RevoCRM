import React, { useEffect, useMemo, useState } from "react";
import { useOptionalTranslation } from "../../hooks/useTranslation";
import { buildFolderOptions } from "./utils/folderOptions";
import type { Folder } from "./types/documents";
import type {
  DuplicateDecision,
  DuplicatePrompt,
  UploadProgress,
} from "./hooks/useFileUpload";

/**
 * アップロードの進捗表示（件数・ファイル名・％）とキャンセル
 *
 * 一覧・関連リストの両方から使う。
 */
export const UploadProgressBar: React.FC<{
  progress: UploadProgress;
  onCancel: () => void;
  /** 画面の左端からのオフセット（一覧のフォルダツリー分） */
  left?: number;
}> = ({ progress, onCancel, left = 0 }) => {
  const { t } = useOptionalTranslation();
  const total = progress.total || 1;
  return (
    <div
      style={{
        position: "absolute",
        bottom: 0,
        left,
        right: 0,
        padding: "8px 16px",
        backgroundColor: "#EBF8FF",
        borderTop: "1px solid #BEE3F8",
        zIndex: 21,
      }}
    >
      <div
        style={{
          display: "flex",
          alignItems: "center",
          gap: 12,
          fontSize: 13,
        }}
      >
        <span style={{ whiteSpace: "nowrap" }}>
          {t(
            "LBL_UPLOADING_COUNT",
            progress.done,
            progress.total,
            progress.percent,
          )}
        </span>
        <div
          style={{
            flex: 1,
            height: 4,
            backgroundColor: "#BEE3F8",
            borderRadius: 2,
            overflow: "hidden",
            minWidth: 60,
          }}
        >
          <div
            style={{
              width: `${Math.round((progress.done / total) * 100) || progress.percent}%`,
              height: "100%",
              backgroundColor: "#3182CE",
              borderRadius: 2,
              transition: "width 0.2s",
            }}
          />
        </div>
        {progress.currentFile && (
          <span
            style={{
              color: "#4A5568",
              overflow: "hidden",
              textOverflow: "ellipsis",
              whiteSpace: "nowrap",
              maxWidth: 260,
            }}
            title={progress.currentFile}
          >
            {t("LBL_UPLOADING_CURRENT_FILE", progress.currentFile)}
          </span>
        )}
        <button
          type="button"
          onClick={onCancel}
          style={{
            border: "1px solid #BEE3F8",
            backgroundColor: "#fff",
            color: "#2B6CB0",
            borderRadius: 4,
            padding: "3px 10px",
            fontSize: 12,
            cursor: "pointer",
            whiteSpace: "nowrap",
          }}
        >
          {t("LBL_CANCEL_UPLOAD")}
        </button>
      </div>
    </div>
  );
};

/**
 * ドロップ直後に登録先フォルダを選ばせるダイアログ
 *
 * ドロップ地点（一覧・関連リスト）だけでは入れたいフォルダを指定できないため、
 * 送信を始める前にここで選んでもらう。フォルダごとドロップした場合は、
 * 選んだフォルダの下に同じ階層を作って登録する。
 *
 * @param fileCount 展開できたファイル数。null の間はまだ数え終わっていない
 */
export const UploadFolderDialog: React.FC<{
  fileCount: number | null;
  folders: Folder[];
  /** 前回選んだフォルダ（初期選択） */
  defaultFolderId: number;
  onConfirm: (folderId: number) => void;
  onCancel: () => void;
}> = ({ fileCount, folders, defaultFolderId, onConfirm, onCancel }) => {
  const { t } = useOptionalTranslation();
  const [folderId, setFolderId] = useState(defaultFolderId);

  // 書き込めないフォルダは、階層をたどれるよう選択不可で残す
  const options = useMemo(
    () =>
      buildFolderOptions(folders, {
        isDisabled: (folder) => folder.can_edit === false,
      }),
    [folders],
  );
  const selected = options.find((option) => option.id === folderId);
  const firstSelectable = options.find((option) => !option.disabled);

  // 既定フォルダが無い・書き込めない環境では、選べる先頭のフォルダに寄せる
  useEffect(() => {
    if (options.length === 0) return;
    if (selected && !selected.disabled) return;
    if (firstSelectable) setFolderId(firstSelectable.id);
  }, [options, selected, firstSelectable]);

  const isCounting = fileCount === null;
  const hasWritableFolder =
    options.length === 0 || firstSelectable !== undefined;
  const canConfirm = !isCounting && hasWritableFolder;

  return (
    <>
      <div
        style={{
          position: "fixed",
          inset: 0,
          backgroundColor: "rgba(0,0,0,0.4)",
          zIndex: 100010,
        }}
        onClick={onCancel}
      />
      <div
        role="dialog"
        aria-label={t("LBL_UPLOAD_DESTINATION_TITLE")}
        style={{
          position: "fixed",
          top: "20%",
          left: "50%",
          transform: "translateX(-50%)",
          width: "min(440px, 92vw)",
          backgroundColor: "#fff",
          borderRadius: 8,
          boxShadow: "0 10px 40px rgba(0,0,0,0.3)",
          zIndex: 100011,
          padding: 20,
        }}
      >
        <h3 style={{ margin: "0 0 12px", fontSize: 15, color: "#2D3748" }}>
          {t("LBL_UPLOAD_DESTINATION_TITLE")}
        </h3>
        <p style={{ margin: "0 0 12px", fontSize: 13, color: "#4A5568" }}>
          {isCounting
            ? t("LBL_UPLOAD_PREPARING")
            : t("LBL_UPLOAD_FILE_COUNT", fileCount)}
        </p>

        <label
          htmlFor="upload-folder-dialog-folder"
          style={{
            display: "block",
            marginBottom: 4,
            fontSize: 12,
            color: "#718096",
          }}
        >
          {t("LBL_UPLOAD_TO_FOLDER")}
        </label>
        <select
          id="upload-folder-dialog-folder"
          value={options.length > 0 ? folderId : ""}
          disabled={options.length === 0}
          onChange={(e) => setFolderId(Number(e.target.value))}
          style={{
            width: "100%",
            padding: "6px 8px",
            fontSize: 13,
            border: "1px solid #E2E8F0",
            borderRadius: 4,
            backgroundColor: "#fff",
            marginBottom: 16,
          }}
        >
          {options.length === 0 && <option value="">{t("LBL_LOADING")}</option>}
          {options.map((option) => (
            <option
              key={option.id}
              value={option.id}
              disabled={option.disabled}
            >
              {option.label}
            </option>
          ))}
        </select>

        {!hasWritableFolder && (
          <p style={{ margin: "0 0 12px", fontSize: 12, color: "#C53030" }}>
            {t("LBL_NO_WRITABLE_FOLDER")}
          </p>
        )}

        <div style={{ display: "flex", justifyContent: "flex-end", gap: 8 }}>
          <button
            type="button"
            onClick={onCancel}
            style={{
              border: "1px solid #E2E8F0",
              backgroundColor: "#fff",
              color: "#4A5568",
              borderRadius: 4,
              padding: "6px 14px",
              fontSize: 13,
              cursor: "pointer",
            }}
          >
            {t("LBL_CANCEL")}
          </button>
          <button
            type="button"
            disabled={!canConfirm}
            onClick={() => onConfirm(folderId)}
            style={{
              border: "1px solid transparent",
              backgroundColor: canConfirm ? "#3182CE" : "#A0AEC0",
              color: "#fff",
              borderRadius: 4,
              padding: "6px 14px",
              fontSize: 13,
              cursor: canConfirm ? "pointer" : "default",
            }}
          >
            {t("LBL_UPLOAD_START")}
          </button>
        </div>
      </div>
    </>
  );
};

/**
 * 同名ファイルの上書き確認
 *
 * 500件までまとめて扱うため、ファイルごとではなく一括で選ばせる。
 */
export const DuplicateConfirmDialog: React.FC<{
  prompt: DuplicatePrompt;
  onRespond: (decision: DuplicateDecision) => void;
}> = ({ prompt, onRespond }) => {
  const { t } = useOptionalTranslation();
  return (
    <>
      <div
        style={{
          position: "fixed",
          inset: 0,
          backgroundColor: "rgba(0,0,0,0.4)",
          zIndex: 100010,
        }}
        onClick={() => onRespond("cancel")}
      />
      <div
        role="dialog"
        aria-label={t("LBL_DUPLICATE_FILES_TITLE")}
        style={{
          position: "fixed",
          top: "20%",
          left: "50%",
          transform: "translateX(-50%)",
          width: "min(520px, 92vw)",
          backgroundColor: "#fff",
          borderRadius: 8,
          boxShadow: "0 10px 40px rgba(0,0,0,0.3)",
          zIndex: 100011,
          padding: 20,
        }}
      >
        <h3 style={{ margin: "0 0 12px", fontSize: 15, color: "#2D3748" }}>
          {t("LBL_DUPLICATE_FILES_TITLE")}
        </h3>
        <p style={{ margin: "0 0 12px", fontSize: 13, color: "#4A5568" }}>
          {t("LBL_DUPLICATE_FILES_MESSAGE", prompt.filenames.length)}
        </p>
        <ul
          style={{
            margin: "0 0 16px",
            paddingLeft: 18,
            maxHeight: 160,
            overflowY: "auto",
            fontSize: 12,
            color: "#4A5568",
          }}
        >
          {prompt.filenames.slice(0, 50).map((filename) => (
            <li key={filename}>{filename}</li>
          ))}
          {prompt.filenames.length > 50 && (
            <li>{`… (${prompt.filenames.length - 50})`}</li>
          )}
        </ul>
        <div
          style={{
            display: "flex",
            justifyContent: "flex-end",
            gap: 8,
            flexWrap: "wrap",
          }}
        >
          <button
            type="button"
            onClick={() => onRespond("cancel")}
            style={{
              border: "1px solid #E2E8F0",
              backgroundColor: "#fff",
              color: "#4A5568",
              borderRadius: 4,
              padding: "6px 14px",
              fontSize: 13,
              cursor: "pointer",
            }}
          >
            {t("LBL_CANCEL")}
          </button>
          <button
            type="button"
            onClick={() => onRespond("skip")}
            style={{
              border: "1px solid #E2E8F0",
              backgroundColor: "#fff",
              color: "#4A5568",
              borderRadius: 4,
              padding: "6px 14px",
              fontSize: 13,
              cursor: "pointer",
            }}
          >
            {t("LBL_DUPLICATE_SKIP")}
          </button>
          <button
            type="button"
            onClick={() => onRespond("overwrite")}
            style={{
              border: "1px solid #3182CE",
              backgroundColor: "#3182CE",
              color: "#fff",
              borderRadius: 4,
              padding: "6px 14px",
              fontSize: 13,
              cursor: "pointer",
            }}
          >
            {t("LBL_DUPLICATE_OVERWRITE")}
          </button>
        </div>
      </div>
    </>
  );
};

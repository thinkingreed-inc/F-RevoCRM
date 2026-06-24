import React from "react";
import type { FileCategory } from "./types/documents";
import { useOptionalTranslation } from "../../hooks/useTranslation";

const FILE_CATEGORY_CONFIG: Record<
  FileCategory,
  { label: string; color: string; bg: string }
> = {
  pdf: { label: "PDF", color: "#E53E3E", bg: "#FED7D7" },
  word: { label: "Word", color: "#3182CE", bg: "#BEE3F8" },
  excel: { label: "Excel", color: "#38A169", bg: "#C6F6D5" },
  powerpoint: { label: "PPT", color: "#DD6B20", bg: "#FEEBC8" },
  image: { label: "画像", color: "#805AD5", bg: "#E9D8FD" },
  text: { label: "TXT", color: "#718096", bg: "#E2E8F0" },
  video: { label: "動画", color: "#D53F8C", bg: "#FED7E2" },
  audio: { label: "音声", color: "#0BC5EA", bg: "#C4F1F9" },
  archive: { label: "ZIP", color: "#D69E2E", bg: "#FEFCBF" },
  url: { label: "URL", color: "#A0AEC0", bg: "#EDF2F7" },
  other: { label: "", color: "#CBD5E0", bg: "#F7FAFC" },
};

export function getFileCategory(
  filetype: string | null,
  filelocationtype: string
): FileCategory {
  if (filelocationtype === "E") return "url";
  if (!filetype) return "other";

  const t = filetype.toLowerCase();
  if (t.includes("pdf")) return "pdf";
  if (t.includes("word") || t.includes("msword") || t.includes(".doc"))
    return "word";
  if (t.includes("excel") || t.includes("spreadsheet") || t.includes(".xls"))
    return "excel";
  if (t.includes("powerpoint") || t.includes("presentation") || t.includes(".ppt"))
    return "powerpoint";
  if (t.startsWith("image/")) return "image";
  if (t.startsWith("text/") || t.includes("csv")) return "text";
  if (t.startsWith("video/")) return "video";
  if (t.startsWith("audio/")) return "audio";
  if (t.includes("zip") || t.includes("tar") || t.includes("compress") || t.includes("archive"))
    return "archive";
  return "other";
}

export function getFileCategoryFromExtension(filename: string | null): FileCategory {
  if (!filename) return "other";
  const ext = filename.split(".").pop()?.toLowerCase();
  if (!ext) return "other";

  const map: Record<string, FileCategory> = {
    pdf: "pdf",
    doc: "word", docx: "word", odt: "word",
    xls: "excel", xlsx: "excel", ods: "excel", csv: "text",
    ppt: "powerpoint", pptx: "powerpoint", odp: "powerpoint",
    jpg: "image", jpeg: "image", png: "image", gif: "image", svg: "image", webp: "image",
    txt: "text", md: "text", log: "text",
    mp4: "video", avi: "video", mov: "video", webm: "video", mkv: "video",
    mp3: "audio", wav: "audio", ogg: "audio", flac: "audio",
    zip: "archive", tar: "archive", gz: "archive", rar: "archive", "7z": "archive",
  };
  return map[ext] || "other";
}

interface FileIconProps {
  filetype: string | null;
  filelocationtype: string;
  filename?: string | null;
  size?: "sm" | "md" | "lg";
}

const SIZE_MAP = {
  sm: { width: 28, height: 28, fontSize: 9 },
  md: { width: 36, height: 36, fontSize: 10 },
  lg: { width: 48, height: 48, fontSize: 12 },
};

export const FileIcon: React.FC<FileIconProps> = ({
  filetype,
  filelocationtype,
  filename,
  size = "md",
}) => {
  const { t } = useOptionalTranslation();
  let category = getFileCategory(filetype, filelocationtype);
  // filetypeがnullでfilename があれば拡張子から推定
  if (category === "other" && filename) {
    category = getFileCategoryFromExtension(filename);
  }

  const config = FILE_CATEGORY_CONFIG[category];
  const dims = SIZE_MAP[size];

  return (
    <div
      style={{
        width: dims.width,
        height: dims.height,
        borderRadius: 6,
        backgroundColor: config.bg,
        color: config.color,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        fontSize: dims.fontSize,
        fontWeight: 700,
        flexShrink: 0,
        border: `1px solid ${config.color}20`,
      }}
      aria-label={config.label || t('LBL_FILE_ICON_LABEL')}
    >
      {config.label}
    </div>
  );
};

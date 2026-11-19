import { useState, useCallback } from "react";
import {
  ParameterFontsApiResponse,
  ParameterFontsResult,
  ParameterUploadFontApiResponse,
  ParameterUploadFontResult,
} from "../types";
import { getApiBaseUrl, getCsrfParam } from "./useParameterData";

/** アップロードできるフォントの最大サイズ（サーバーの Vtiger_PDF_FontInstaller::MAX_FILE_SIZE と揃える） */
export const MAX_FONT_FILE_SIZE = 30 * 1024 * 1024;

/**
 * useFontData - PDF_DEFAULT_FONT で選べるフォントの取得と、フォントの追加を行うカスタムフック
 */
export function useFontData() {
  const [fonts, setFonts] = useState<string[]>([]);
  const [fallback, setFallback] = useState("");
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [loadError, setLoadError] = useState(false);

  /**
   * フォントの一覧を取得
   */
  const fetchFonts = useCallback(async (): Promise<void> => {
    setLoading(true);
    setLoadError(false);
    try {
      const url = `${getApiBaseUrl()}/index.php?module=Parameters&parent=Settings&api=GetFonts`;
      const response = await fetch(url, {
        method: "GET",
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });
      const result: ParameterFontsApiResponse = await response.json();
      // API は結果をそのまま返す（Vtiger_Response の EMIT_PURE_JSON）。result に包まれた形にも対応する
      const data = result.result ?? (result as ParameterFontsResult);
      if (
        !response.ok ||
        result.success === false ||
        !Array.isArray(data.fonts)
      ) {
        throw new Error(result.error?.message || `HTTP ${response.status}`);
      }
      setFonts(data.fonts);
      setFallback(data.fallback);
    } catch {
      setLoadError(true);
    } finally {
      setLoading(false);
    }
  }, []);

  /**
   * フォントを追加する
   * 成功したら一覧に加え、追加したフォント名を返す
   */
  const uploadFont = useCallback(
    async (
      file: File,
    ): Promise<
      { success: true; font: string } | { success: false; error?: string }
    > => {
      setUploading(true);
      try {
        const body = new FormData();
        body.append("module", "Parameters");
        body.append("parent", "Settings");
        body.append("api", "UploadFont");
        // csrf-magic は XMLHttpRequest だけにトークンを付けるため、fetch では自分で付ける
        const csrf = getCsrfParam();
        body.append(csrf.name, csrf.token);
        body.append("font", file);

        const response = await fetch(`${getApiBaseUrl()}/index.php`, {
          method: "POST",
          credentials: "same-origin",
          headers: { Accept: "application/json" },
          body,
        });
        const result: ParameterUploadFontApiResponse = await response
          .json()
          .catch(() => ({}));
        const data = result.result ?? (result as ParameterUploadFontResult);
        if (!response.ok || result.success === false || !data.font) {
          return { success: false, error: result.error?.message };
        }
        const font = data.font;
        setFonts((prev) =>
          prev.includes(font) ? prev : [...prev, font].sort(),
        );
        return { success: true, font };
      } catch {
        return { success: false };
      } finally {
        setUploading(false);
      }
    },
    [],
  );

  return {
    fonts,
    fallback,
    loading,
    uploading,
    loadError,
    fetchFonts,
    uploadFont,
  };
}

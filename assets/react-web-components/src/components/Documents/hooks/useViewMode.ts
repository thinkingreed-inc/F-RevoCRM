import { useState, useCallback } from "react";
import type { ViewMode } from "../types/documents";

const STORAGE_KEY = "documents_view_mode";

function getInitialMode(defaultMode?: string): ViewMode {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === "list" || stored === "grid" || stored === "preview") {
      return stored;
    }
  } catch {
    // localStorage unavailable
  }
  if (defaultMode === "grid" || defaultMode === "preview") return defaultMode;
  return "list";
}

export function useViewMode(defaultMode?: string) {
  const [viewMode, setViewModeState] = useState<ViewMode>(() =>
    getInitialMode(defaultMode)
  );

  const setViewMode = useCallback((mode: ViewMode) => {
    setViewModeState(mode);
    try {
      localStorage.setItem(STORAGE_KEY, mode);
    } catch {
      // ignore
    }
  }, []);

  return { viewMode, setViewMode };
}

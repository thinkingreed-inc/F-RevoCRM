import { useState, useCallback, useEffect } from "react";
import type { Folder } from "../types/documents";

interface UseFolderTreeResult {
  folders: Folder[];
  totalCount: number;
  starredCount: number;
  isLoading: boolean;
  error: string | null;
  reload: () => void;
}

export function useFolderTree(): UseFolderTreeResult {
  const [folders, setFolders] = useState<Folder[]>([]);
  const [totalCount, setTotalCount] = useState(0);
  const [starredCount, setStarredCount] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchTree = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const url = new URLSearchParams({
        module: "Documents",
        api: "FolderAPI",
        mode: "tree",
      });

      const response = await fetch(`index.php?${url.toString()}`, {
        method: "GET",
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });

      const data = await response.json();
      if (data.success === false || data.error) {
        throw new Error(data.error?.message || "Failed to fetch folders");
      }

      const result = data.result || data;
      setFolders(result.folders);
      setTotalCount(result.totalCount);
      setStarredCount(result.starredCount);
    } catch (e: any) {
      setError(e.message || "Unknown error");
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchTree();
  }, [fetchTree]);

  return { folders, totalCount, starredCount, isLoading, error, reload: fetchTree };
}

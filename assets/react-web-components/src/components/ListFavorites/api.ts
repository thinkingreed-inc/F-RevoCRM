import type {
  FavoriteApiErrorResponse,
  FavoriteListItem,
  GetFavoritesResponse,
} from "@/types/listFavorite";

// API のエラーは HTTP 200 のまま本文の error で返ることがある
const parseResponse = async <T>(response: Response): Promise<T> => {
  const data: unknown = await response.json().catch(() => null);
  const error = (data as FavoriteApiErrorResponse | null)?.error;
  if (!response.ok || data === null || error) {
    throw new Error(error?.message || `HTTP ${response.status}`);
  }
  return data as T;
};

export const fetchFavorites = async (
  module: string,
): Promise<FavoriteListItem[]> => {
  const params = new URLSearchParams({
    module: "CustomView",
    api: "GetFavorites",
    sourceModule: module,
  });
  const response = await fetch(`index.php?${params.toString()}`, {
    method: "GET",
    credentials: "same-origin",
    headers: { Accept: "application/json" },
  });
  const data = await parseResponse<GetFavoritesResponse>(response);
  return data.favorites;
};

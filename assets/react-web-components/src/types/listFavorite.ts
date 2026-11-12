/**
 * CustomView_Favorite_Model::getFavoriteList() の要素
 */
export interface FavoriteListItem {
  id: number;
  /** 翻訳済み */
  name: string;
  /** app パラメータは含まない */
  url: string;
}

export interface GetFavoritesResponse {
  favorites: FavoriteListItem[];
}

export interface FavoriteApiErrorResponse {
  error?: {
    message?: string;
  };
}

export interface FavoritesChangedDetail {
  /** 省略時は受け取った側が API から取り直す */
  favorites?: FavoriteListItem[];
}

export interface ListFavoritesProps {
  module: string;
  favorites?: FavoriteListItem[];
  app?: string;
  /** テンプレートで翻訳済みの文言 */
  labels?: Record<string, string>;
}

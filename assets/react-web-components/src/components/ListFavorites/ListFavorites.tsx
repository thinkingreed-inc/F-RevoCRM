import React, { useEffect, useState } from "react";
import type {
  FavoriteListItem,
  FavoritesChangedDetail,
  ListFavoritesProps,
} from "@/types/listFavorite";
import {
  TranslationProvider,
  useOptionalTranslationContext,
} from "../../contexts/TranslationContext";
import { fetchFavorites } from "./api";
import { FAVORITES_CHANGED_EVENT, FAVORITES_RENDERED_EVENT } from "./events";

export const ListFavorites: React.FC<ListFavoritesProps> = ({
  labels,
  ...props
}) => {
  return (
    <TranslationProvider module={props.module} initialTranslations={labels}>
      <ListFavoritesInner {...props} />
    </TranslationProvider>
  );
};

/**
 * 行の操作は ListSidebar.js が処理する。行の class は jQuery も書き換えるため、React からは変えない。
 */
const ListFavoritesInner: React.FC<Omit<ListFavoritesProps, "labels">> = ({
  module,
  favorites: initialFavorites = [],
  app = "",
}) => {
  const { t } = useOptionalTranslationContext();
  const [favorites, setFavorites] =
    useState<FavoriteListItem[]>(initialFavorites);

  useEffect(() => {
    const handleChanged = (event: Event) => {
      const { detail } = event as CustomEvent<FavoritesChangedDetail | null>;
      if (detail?.favorites) {
        setFavorites(detail.favorites);
        return;
      }
      fetchFavorites(module)
        .then(setFavorites)
        .catch((error: unknown) => {
          console.error("お気に入りリストの取得に失敗しました", error);
        });
    };

    document.addEventListener(FAVORITES_CHANGED_EVENT, handleChanged);
    return () => {
      document.removeEventListener(FAVORITES_CHANGED_EVENT, handleChanged);
    };
  }, [module]);

  useEffect(() => {
    document.dispatchEvent(new CustomEvent(FAVORITES_RENDERED_EVENT));
  }, [favorites]);

  if (favorites.length === 0) {
    return null;
  }

  return (
    <div className="list-group" id="favoriteList">
      <h6 className="lists-header">{t("LBL_FAVORITE_LIST")}</h6>
      <ul className="lists-menu">
        {favorites.map((favorite) => (
          <li
            key={favorite.id}
            style={{ fontSize: 12 }}
            className="listViewFilter"
          >
            <i
              className="fa fa-star active favoriteListStar"
              data-filter-id={favorite.id}
              data-source-module={module}
              title={t("LBL_REMOVE_FROM_FAVORITES")}
            />
            <a
              className="filterName listViewFilterElipsis"
              href={app ? `${favorite.url}&app=${app}` : favorite.url}
              data-filter-id={favorite.id}
              title={favorite.name}
              onContextMenu={(event) => event.preventDefault()}
            >
              {favorite.name}
            </a>
            <div className="pull-right">
              <span
                className="js-popover-container"
                style={{ cursor: "pointer" }}
              >
                <span
                  className="fa fa-angle-down"
                  {...{ rel: "popover" }}
                  data-toggle="popover"
                  aria-expanded="true"
                  data-filter-id={favorite.id}
                />
              </span>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
};

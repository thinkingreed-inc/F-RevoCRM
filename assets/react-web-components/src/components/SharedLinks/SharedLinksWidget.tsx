import React from "react";
import type {
  SharedLinkWidgetItem,
  SharedLinkWidgetLabels,
} from "@/types/sharedLinks";
import { openSharedLink } from "./utils/openSharedLink";
import type { OpenEnvironment } from "./utils/openSharedLink";

interface SharedLinksWidgetProps {
  /** 表示するリンク（サーバーで公開範囲の判定と値の解決を済ませたもの） */
  links?: SharedLinkWidgetItem[];
  labels?: Partial<SharedLinkWidgetLabels>;
  /** 管理者のときだけ渡す。0 件のときに設定画面へのボタンを出す */
  settingsUrl?: string;
  /** テストでブラウザの機能を差し替える */
  environment?: OpenEnvironment;
}

/** 目印の色（タイトルの頭文字の背景）。リンクの ID で決まる */
const BADGE_COLORS = [
  "#3182ce",
  "#38a169",
  "#dd6b20",
  "#805ad5",
  "#d53f8c",
  "#319795",
  "#c05621",
  "#4a5568",
];

type VtigerWindow = Window & {
  app?: {
    helper?: { showErrorNotification?: (o: { message: string }) => void };
  };
  csrfMagicName?: string;
};

function defaultEnvironment(): OpenEnvironment {
  const w = window as VtigerWindow;
  return {
    window,
    document,
    notify: (message) => {
      if (w.app?.helper?.showErrorNotification) {
        w.app.helper.showErrorNotification({ message });
      } else {
        window.alert(message);
      }
    },
    csrfName: w.csrfMagicName,
  };
}

/**
 * ダッシュボードのウィジェット「共有リンク」の中身
 *
 * vtiger の共通 CSS に .group（margin 付き）があるため、Tailwind の group は名前付き（group/link）で使う。
 *
 * 1 件 = 1 行で、行全体がリンク。左にタイトルの頭文字（目印）、右に開き方（別タブ / 別ウィンドウ）を出す。
 * 公開範囲の判定とユーザー情報の差し込みはサーバーで済んでいる（公開範囲外のリンクは渡ってこない）。
 * 仕様: docs/specs/shared_links_widget.md 3.2 / 3.3
 */
export const SharedLinksWidget: React.FC<SharedLinksWidgetProps> = ({
  links = [],
  labels = {},
  settingsUrl,
  environment,
}) => {
  const items = Array.isArray(links) ? links : [];

  if (items.length === 0) {
    return (
      <div
        className="sharedLinksEmpty"
        style={{
          padding: "28px 12px",
          textAlign: "center",
          color: "#718096",
          fontSize: 13,
        }}
      >
        <i
          className="fa fa-link"
          aria-hidden="true"
          style={{
            display: "block",
            marginBottom: 8,
            fontSize: 28,
            color: "#cbd5e0",
          }}
        />
        {labels.empty}
        {settingsUrl && (
          <div style={{ marginTop: 12 }}>
            <a className="btn btn-default btn-sm" href={settingsUrl}>
              {labels.settings}
            </a>
          </div>
        )}
      </div>
    );
  }

  const handleClick = (
    e: React.MouseEvent<HTMLAnchorElement>,
    link: SharedLinkWidgetItem,
  ) => {
    const plainClick =
      e.button === 0 && !e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey;
    const handled = openSharedLink(
      link,
      plainClick,
      environment ?? defaultEnvironment(),
      {
        popupBlocked: labels.popupBlocked ?? "",
        postBlocked: labels.postBlocked ?? "",
      },
    );
    if (handled) {
      e.preventDefault();
    }
  };

  return (
    <div className="sharedLinksList" style={{ padding: "2px 0" }}>
      {items.map((link) => {
        const isGet = link.method === "GET";
        const openLabel =
          link.openMode === "window"
            ? labels.openInNewWindow
            : labels.openInNewTab;
        return (
          <a
            key={link.id}
            className="sharedLink group/link flex items-center rounded-[6px] no-underline hover:bg-[#f1f5f9] hover:no-underline focus:bg-[#f1f5f9] focus:no-underline focus-visible:outline-2 focus-visible:outline-[#3182ce]"
            style={{ gap: 10, padding: "8px 8px", color: "#2d3748" }}
            href={isGet ? link.url : "#"}
            {...(isGet
              ? {
                  target: "_blank",
                  rel: link.noreferrer ? "noopener noreferrer" : "noopener",
                }
              : { role: "button" })}
            onClick={(e) => handleClick(e, link)}
          >
            <span
              className="sharedLinkBadge"
              aria-hidden="true"
              style={{
                flex: "0 0 32px",
                width: 32,
                height: 32,
                borderRadius: 8,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                color: "#fff",
                fontSize: 14,
                fontWeight: 600,
                lineHeight: 1,
                backgroundColor: BADGE_COLORS[link.color % BADGE_COLORS.length],
              }}
            >
              {link.initial}
            </span>
            <span style={{ flex: "1 1 auto", minWidth: 0 }}>
              <span
                className="sharedLinkTitle"
                style={{
                  display: "block",
                  fontSize: 13,
                  fontWeight: 600,
                  lineHeight: 1.4,
                  overflow: "hidden",
                  whiteSpace: "nowrap",
                  textOverflow: "ellipsis",
                }}
              >
                {link.title}
              </span>
              {link.description !== "" && (
                <span
                  className="sharedLinkDescription"
                  style={{
                    display: "-webkit-box",
                    WebkitBoxOrient: "vertical",
                    WebkitLineClamp: 2,
                    overflow: "hidden",
                    marginTop: 1,
                    fontSize: 12,
                    lineHeight: 1.45,
                    color: "#718096",
                    whiteSpace: "pre-wrap",
                    wordBreak: "break-word",
                  }}
                >
                  {link.description}
                </span>
              )}
            </span>
            <span
              className="sharedLinkOpen text-[#a0aec0] group-hover/link:text-[#3182ce] group-focus/link:text-[#3182ce]"
              title={openLabel}
              style={{ flex: "0 0 auto", fontSize: 13 }}
            >
              <i
                className={`fa ${link.openMode === "window" ? "fa-clone" : "fa-external-link"}`}
                aria-hidden="true"
              />
              <span
                style={{
                  position: "absolute",
                  width: 1,
                  height: 1,
                  overflow: "hidden",
                  clip: "rect(0, 0, 0, 0)",
                }}
              >
                {openLabel}
              </span>
            </span>
          </a>
        );
      })}
    </div>
  );
};

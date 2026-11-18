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
 * 1 件 = 1 行で、行全体がリンク。行は罫線で区切り、右端に開き方の印（別タブ / 別ウィンドウ）だけを出す。
 * F-RevoCRM の画面に合わせて、色は文字色・リンク色・灰色だけにする（色付きの目印は使わない）。
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
          color: "#777",
          fontSize: 13,
        }}
      >
        <i
          className="fa fa-link"
          aria-hidden="true"
          style={{
            display: "block",
            marginBottom: 8,
            fontSize: 24,
            color: "#ccc",
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
    <div className="sharedLinksList">
      {items.map((link, index) => {
        const isGet = link.method === "GET";
        const openLabel =
          link.openMode === "window"
            ? labels.openInNewWindow
            : labels.openInNewTab;
        return (
          <a
            key={link.id}
            className="sharedLink group/link flex items-start no-underline hover:bg-[#f5f5f5] hover:no-underline focus:bg-[#f5f5f5] focus:no-underline focus-visible:outline-1 focus-visible:outline-[#15c]"
            style={{
              gap: 8,
              padding: "7px 10px",
              color: "#333",
              borderBottom:
                index === items.length - 1 ? "none" : "1px solid #e5e5e5",
            }}
            href={isGet ? link.url : "#"}
            {...(isGet
              ? {
                  target: "_blank",
                  rel: link.noreferrer ? "noopener noreferrer" : "noopener",
                }
              : { role: "button" })}
            onClick={(e) => handleClick(e, link)}
          >
            <span style={{ flex: "1 1 auto", minWidth: 0 }}>
              <span
                className="sharedLinkTitle group-hover/link:underline"
                style={{
                  display: "block",
                  fontSize: 13,
                  lineHeight: 1.5,
                  color: "#15c",
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
                    color: "#777",
                    whiteSpace: "pre-wrap",
                    wordBreak: "break-word",
                  }}
                >
                  {link.description}
                </span>
              )}
            </span>
            <span
              className="sharedLinkOpen text-[#999] group-hover/link:text-[#15c] group-focus/link:text-[#15c]"
              title={openLabel}
              style={{ flex: "0 0 auto", marginTop: 2, fontSize: 12 }}
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

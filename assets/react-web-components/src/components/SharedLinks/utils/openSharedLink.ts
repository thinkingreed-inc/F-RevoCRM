import type { SharedLinkWidgetItem } from "@/types/sharedLinks";

/**
 * ダッシュボードの共有リンクを開く処理
 *
 * - GET・別タブ: <a target="_blank"> のまま、ブラウザに任せる（ここでは何もしない）
 * - GET・別ウィンドウ: window.open で開く
 * - POST・別タブ: フォームを組み立て、target="_blank" で送信する
 * - POST・別ウィンドウ: 空のウィンドウを開き、その中でフォームを組み立てて送信する
 *
 * POST のフォームを HTML に書き出さないのは、csrf-magic が <form method="post"> に
 * CRM の CSRF トークンを埋め込み、リンク先へ送ってしまうため。
 * 仕様: docs/specs/shared_links_widget.md 3.3 / 5 / 6.2 / 6.5
 */

/** テストで差し替えられるよう、使うブラウザの機能をまとめて受け取る */
export interface OpenEnvironment {
  window: Window;
  document: Document;
  /** 通知（vtiger の app.helper.showErrorNotification） */
  notify: (message: string) => void;
  /** CRM の CSRF トークンのパラメータ名（csrf-magic） */
  csrfName?: string;
}

export interface OpenMessages {
  popupBlocked: string;
  postBlocked: string;
}

/** 同じリンクは同じウィンドウを使い回す */
export function windowName(link: SharedLinkWidgetItem): string {
  return `frSharedLink_${link.id}`;
}

/**
 * window.open に渡すサイズと位置
 *
 * 今のウィンドウの中央に来るようにし、画面より大きいときは画面に収まるサイズに縮める。
 */
export function windowFeatures(
  link: SharedLinkWidgetItem,
  win: Window,
): string {
  const screenWidth = win.screen.availWidth || win.outerWidth;
  const screenHeight = win.screen.availHeight || win.outerHeight;
  const width = Math.min(link.width ?? screenWidth, screenWidth);
  const height = Math.min(link.height ?? screenHeight, screenHeight);
  const left =
    win.screenX + Math.max(0, Math.round((win.outerWidth - width) / 2));
  const top =
    win.screenY + Math.max(0, Math.round((win.outerHeight - height) / 2));
  return `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`;
}

/**
 * フォームを組み立てて送信し、送信後に消す
 *
 * @param rel フォームの rel（null なら付けない）
 */
export function submitPost(
  targetDocument: Document,
  link: SharedLinkWidgetItem,
  target: string,
  rel: string | null,
  csrfName?: string,
): void {
  const form = targetDocument.createElement("form");
  form.method = "POST";
  form.action = link.url;
  form.target = target;
  form.style.display = "none";
  if (rel) {
    form.setAttribute("rel", rel);
  }
  for (const param of link.params) {
    // 念のため、CRM の CSRF トークンと同じ名前の値は送らない
    if (csrfName && param.name === csrfName) continue;
    const input = targetDocument.createElement("input");
    input.type = "hidden";
    input.name = param.name;
    input.value = param.value;
    form.appendChild(input);
  }
  targetDocument.body.appendChild(form);
  form.submit();
  form.remove();
}

/** 開いたウィンドウの文書。中を操作できなければ null */
function accessibleDocument(opened: Window): Document | null {
  try {
    const doc = opened.document;
    return doc && doc.body ? doc : null;
  } catch {
    // 同じ名前のウィンドウが別オリジンのページを開いたまま残っている
    return null;
  }
}

/**
 * リンクを押したときの処理
 *
 * @param plainClick 修飾キーなしの左クリックか（中クリック・Ctrl+クリックなどはブラウザに任せる）
 * @returns 既定の動作（<a> の遷移）を止めるなら true
 */
export function openSharedLink(
  link: SharedLinkWidgetItem,
  plainClick: boolean,
  env: OpenEnvironment,
  messages: OpenMessages,
): boolean {
  if (link.method === "GET") {
    if (link.openMode !== "window" || !plainClick) {
      return false;
    }
    const features =
      windowFeatures(link, env.window) +
      ",noopener" +
      (link.noreferrer ? ",noreferrer" : "");
    // noopener を付けると成功しても null が返るため、ブロックされたかは判定しない
    env.window.open(link.url, windowName(link), features);
    return true;
  }

  // CRM の CSP（form-action）で送り先が許可されていないと、ブラウザが送信を止める。
  // 空のウィンドウだけが開いて何も起きないのを避け、理由を知らせる
  if (link.blocked) {
    env.notify(messages.postBlocked);
    return true;
  }
  if (link.openMode !== "window") {
    submitPost(
      env.document,
      link,
      "_blank",
      link.noreferrer ? "noopener noreferrer" : "noopener",
      env.csrfName,
    );
    return true;
  }

  const name = windowName(link);
  const opened = env.window.open(
    "about:blank",
    name,
    windowFeatures(link, env.window),
  );
  if (!opened) {
    env.notify(messages.popupBlocked);
    return true;
  }
  // 開いた空のウィンドウ（CRM と同じオリジンの about:blank）の中でフォームを作って送る。
  // CRM の画面から target=名前 で送る方法は、フォームに rel="noopener" を付けると
  // ブラウザが名前付きのウィンドウを探さずに新しいタブを開いてしまい、空のウィンドウが残るため使わない
  const popupDocument = accessibleDocument(opened);
  if (popupDocument) {
    if (link.noreferrer) {
      const meta = popupDocument.createElement("meta");
      meta.name = "referrer";
      meta.content = "no-referrer";
      popupDocument.head.appendChild(meta);
    }
    submitPost(popupDocument, link, "_self", null, env.csrfName);
  } else {
    // 開いているウィンドウへ名前で送る（rel を付けると名前で探さなくなるため付けない）
    submitPost(env.document, link, name, null, env.csrfName);
  }
  // 送信先のページから CRM の画面を操作できないようにする
  try {
    (opened as { opener: unknown }).opener = null;
  } catch {
    // 別オリジンへ遷移済みなら設定できないが、その場合も参照は残らない
  }
  return true;
}

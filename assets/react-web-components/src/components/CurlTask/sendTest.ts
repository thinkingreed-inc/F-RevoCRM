import { TestSendPayload, TestSendResult } from "./TestSendPanel";

/** F-RevoCRM が window に載せる jQuery Deferred ベースのAjaxヘルパー */
interface VtigerAjaxDeferred {
  then(
    onDone: (err: unknown, data: TestSendResult | null) => void,
    onFail?: (err: unknown) => void,
  ): void;
}

/**
 * app.request の必要部分だけの型。
 * post を必須プロパティにしているのは、呼び出し箇所で
 * オプショナルの絞り込みに頼らずに済ませるため。
 */
interface VtigerRequest {
  post(params: { data: Record<string, string> }): VtigerAjaxDeferred;
}

/** 送信処理が出す文言。呼び出し側から翻訳済み文字列を渡す */
export interface SendTestMessages {
  unknownError: string;
  noResponse: string;
}

const DEFAULT_MESSAGES: SendTestMessages = {
  unknownError: "不明なエラー",
  noResponse: "サーバから応答がありません",
};

/** app.request 等が返す様々な形のエラーを、人が読める文字列にする */
export function stringifyError(
  err: unknown,
  unknownError: string = DEFAULT_MESSAGES.unknownError,
): string {
  if (err == null) return unknownError;
  if (typeof err === "string") return err;
  if (typeof err === "object") {
    const o = err as Record<string, unknown>;
    if (typeof o.responseText === "string" && o.responseText) {
      return o.responseText;
    }
    if (typeof o.message === "string" && o.message) return o.message;
    try {
      return JSON.stringify(err);
    } catch {
      return String(err);
    }
  }
  return String(err);
}

/**
 * テスト送信はTestCurlAjaxアクションを叩く。
 * テスト送信では対象レコードが無いためフィールド変数は置換されず、入力そのままを送る。
 */
export function defaultSendTest(messages: SendTestMessages = DEFAULT_MESSAGES) {
  return (p: TestSendPayload): Promise<TestSendResult> => {
    const raw = (window as unknown as { app?: { request?: unknown } }).app
      ?.request;
    if (!raw || typeof (raw as Partial<VtigerRequest>).post !== "function") {
      return Promise.resolve({
        success: false,
        error: "app.request is not available",
      });
    }
    const request = raw as VtigerRequest;

    return new Promise<TestSendResult>((resolve) => {
      // post は内部で this を参照する。変数へ切り出して呼ぶと
      // this が失われ "this._request is not a function" になるため、
      // 必ず request.post(...) の形でメソッドとして呼ぶ。
      request
        .post({
          data: {
            module: "Workflows",
            parent: "Settings",
            action: "TestCurlAjax",
            url: p.url,
            method: p.method,
            headers: p.headers,
            body: p.body,
            timeout: p.timeout,
          },
        })
        .then(
          (err: unknown, data: TestSendResult | null) => {
            if (err)
              resolve({
                success: false,
                error: stringifyError(err, messages.unknownError),
              });
            else if (data == null)
              resolve({ success: false, error: messages.noResponse });
            else resolve(data);
          },
          // Deferredがrejectされた場合(通信失敗・非JSON応答など)
          (rejectErr: unknown) =>
            resolve({
              success: false,
              error: stringifyError(rejectErr, messages.unknownError),
            }),
        );
    });
  };
}

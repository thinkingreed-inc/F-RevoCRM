/**
 * ドキュメントの削除（ごみ箱へ移動）
 *
 * サーバーが削除を拒否する場合（電帳法対象など）はエラーメッセージを返す。
 * 応答を見ずに成功扱いにすると、削除できていないのに画面が閉じてしまう。
 */

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) return { name: csrfName, value: csrfToken };
  return null;
}

/** 応答からエラーメッセージを取り出す。エラーが無ければ null */
export function extractDeleteError(text: string): string | null {
  if (!text) return null;
  let data: {
    success?: boolean;
    error?: { message?: string; code?: string };
  };
  try {
    data = JSON.parse(text);
  } catch {
    // 成功時は HTML（リダイレクト）が返ることがあるためエラーとしない
    return null;
  }
  if (data && (data.success === false || data.error)) {
    return data.error?.message || data.error?.code || "";
  }
  return null;
}

export interface DeleteResult {
  ok: boolean;
  /** ok が false のときのサーバーメッセージ（空なら理由不明） */
  message: string;
}

/**
 * ドキュメントをごみ箱へ移動する
 *
 * @param recordId 対象ドキュメント
 */
export async function deleteDocument(recordId: number): Promise<DeleteResult> {
  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "Documents");
  body.append("action", "DeleteAjax");
  body.append("record", String(recordId));

  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: body.toString(),
  });
  const text = await response.text();
  const error = extractDeleteError(text);
  if (error !== null) {
    return { ok: false, message: error };
  }
  if (!response.ok) {
    return { ok: false, message: "" };
  }
  return { ok: true, message: "" };
}

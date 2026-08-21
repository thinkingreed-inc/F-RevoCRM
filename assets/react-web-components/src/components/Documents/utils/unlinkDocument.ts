/**
 * ドキュメントと親レコードの紐づけ解除
 *
 * 解除は RelationAPI（mode=unlink）に任せる。参照できないフォルダのドキュメントや
 * 既に解除済みのものはサーバー側で対象外になるため、件数を見て結果を判断する。
 */

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  if (csrfName && csrfToken) return { name: csrfName, value: csrfToken };
  return null;
}

export interface UnlinkParams {
  /** 親レコードのモジュール（例: ServiceContracts） */
  parentModule: string;
  /** 親レコードID */
  parentId: number;
  /** 解除するドキュメントID */
  recordId: number;
}

export interface UnlinkResult {
  ok: boolean;
  /** ok が false のときの理由（空なら理由不明） */
  message: string;
  /** 実際に解除した件数 */
  unlinked: number;
  /** 参照権限が無く対象外になった件数 */
  denied: number;
}

/**
 * ドキュメントと親レコードの紐づけを解除する
 *
 * @param params 親レコードと対象ドキュメント
 */
export async function unlinkDocument(
  params: UnlinkParams,
): Promise<UnlinkResult> {
  const csrf = getCsrfToken();
  const body = new URLSearchParams();
  if (csrf) body.append(csrf.name, csrf.value);
  body.append("module", "Documents");
  body.append("api", "RelationAPI");
  body.append("mode", "unlink");
  body.append("parent_module", params.parentModule);
  body.append("parent_id", String(params.parentId));
  body.append("records[]", String(params.recordId));

  let data: {
    success?: boolean;
    error?: { message?: string; code?: string };
    result?: { unlinked?: number; denied?: number };
    unlinked?: number;
    denied?: number;
  };
  try {
    const response = await fetch("index.php", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: body.toString(),
    });
    data = await response.json();
  } catch (e) {
    return {
      ok: false,
      message: e instanceof Error ? e.message : "",
      unlinked: 0,
      denied: 0,
    };
  }

  if (data.success === false || data.error) {
    return {
      ok: false,
      message: data.error?.message || data.error?.code || "",
      unlinked: 0,
      denied: 0,
    };
  }

  const result = data.result || data;
  const unlinked = result.unlinked ?? 0;
  const denied = result.denied ?? 0;
  // 解除0件は成功ではない（権限が無い・既に解除済みなど）
  return { ok: unlinked > 0, message: "", unlinked, denied };
}

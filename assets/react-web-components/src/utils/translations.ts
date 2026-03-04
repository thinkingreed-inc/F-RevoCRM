/**
 * GetTranslations API 呼び出しユーティリティ
 *
 * F-RevoCRMの翻訳データを取得する。
 * Vtiger標準のモジュール中心設計に準拠。
 *
 * Usage:
 *   ?module=Potentials&api=GetTranslations
 *   ?module=Potentials&api=GetTranslations&language=ja_jp
 */

export interface TranslationData {
  [key: string]: string;
}

/**
 * 翻訳キャッシュ
 * キー: `${module}:${language}` 形式
 * 同一モジュール・言語の重複API呼び出しを防止
 */
const translationCache = new Map<string, {
  data: TranslationsResponse;
  timestamp: number;
}>();

/** キャッシュの有効期限（ミリ秒）: 5分 */
const CACHE_TTL = 5 * 60 * 1000;

export interface TranslationsResponse {
  module: string;
  language: string;
  translations: {
    [moduleName: string]: TranslationData;
  };
  timestamp: string;
}

export interface GetTranslationsParams {
  /** 対象モジュール名（必須） */
  module: string;
  /** 言語コード（省略時はサーバー側でユーザー設定を使用） */
  language?: string;
}

/**
 * キャッシュキーを生成
 */
function getCacheKey(module: string, language?: string): string {
  return `${module}:${language || 'default'}`;
}

/**
 * キャッシュをクリア（テストや言語切り替え時に使用）
 */
export function clearTranslationCache(): void {
  translationCache.clear();
}

/**
 * GetTranslations APIを呼び出して翻訳データを取得
 *
 * Vtiger標準設計に準拠し、moduleパラメータで対象モジュールを指定。
 * レスポンスには対象モジュール + Vtiger共通翻訳が含まれる。
 * キャッシュ機構により、同一リクエストの重複呼び出しを防止。
 *
 * @param params - APIパラメータ
 * @param options - オプション
 * @param options.skipCache - trueの場合、キャッシュをスキップ
 * @returns 翻訳レスポンス
 */
export async function fetchTranslations(
  params: GetTranslationsParams,
  options: { skipCache?: boolean } = {}
): Promise<TranslationsResponse> {
  const cacheKey = getCacheKey(params.module, params.language);

  // キャッシュ確認（skipCacheがfalseで、有効期限内の場合）
  if (!options.skipCache) {
    const cached = translationCache.get(cacheKey);
    if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
      return cached.data;
    }
  }

  const searchParams = new URLSearchParams();
  searchParams.set('module', params.module);
  searchParams.set('api', 'GetTranslations');

  if (params.language) {
    searchParams.set('language', params.language);
  }

  const url = `index.php?${searchParams.toString()}`;

  const response = await fetch(url, {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch translations: ${response.statusText}`);
  }

  // JSONパースエラーのハンドリング
  let data;
  try {
    data = await response.json();
  } catch (parseError) {
    throw new Error('Invalid JSON response from server');
  }

  // APIレスポンスの形式を確認
  if (data.success === false) {
    throw new Error(data.error?.message || 'Failed to fetch translations');
  }

  // result wrapper がある場合とない場合に対応
  const result = data.result || data;

  const translationsResponse: TranslationsResponse = {
    module: result.module,
    language: result.language,
    translations: result.translations,
    timestamp: result.timestamp,
  };

  // キャッシュに保存
  translationCache.set(cacheKey, {
    data: translationsResponse,
    timestamp: Date.now(),
  });

  return translationsResponse;
}

/**
 * 翻訳データをマージする（Vtiger共通 + モジュール固有）
 *
 * @param translations - 翻訳レスポンス
 * @returns マージされた翻訳データ
 */
export function mergeTranslations(
  translations: TranslationsResponse
): TranslationData {
  const merged: TranslationData = {};

  // Vtiger (共通) を先に適用
  if (translations.translations.Vtiger) {
    Object.assign(merged, translations.translations.Vtiger);
  }

  // 他のモジュールを上書き適用
  for (const [moduleName, moduleTranslations] of Object.entries(
    translations.translations
  )) {
    if (moduleName !== 'Vtiger' && !moduleName.endsWith('_JS')) {
      Object.assign(merged, moduleTranslations);
    }
  }

  return merged;
}

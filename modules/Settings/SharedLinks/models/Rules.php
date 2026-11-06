<?php

/**
 * 共有リンクの判定ロジック（DB を使わない）
 *
 * 入力の検証・公開範囲の判定・パラメータの値の解決・GET の URL の組み立てを行う。
 * DB や実行ユーザーに依存しないため、単体テストから直接呼べる。
 * 仕様: docs/specs/shared_links_widget.md
 *
 * @phpstan-type SharedLinkParam array{name: string, value_type: string, value: string}
 * @phpstan-type SharedLinkTarget array{type: string, id: string}
 * @phpstan-type SharedLinkInput array{title: string, url: string, description: string, method: string,
 *     open_mode: string, window_width: int|null, window_height: int|null, noreferrer: bool, active: bool,
 *     visibility: string, params: list<SharedLinkParam>, targets: list<SharedLinkTarget>}
 * @phpstan-type SharedLinkUser array{id: int|string, roleid: string, parent_role_seq: string,
 *     groups: list<int|string>}
 */
class Settings_SharedLinks_Rules_Model
{
    public const METHODS = ['GET', 'POST'];
    public const OPEN_MODES = ['tab', 'window'];
    public const VISIBILITIES = ['all', 'targets'];
    public const VALUE_TYPES = ['fixed', 'user'];
    public const TARGET_TYPES = ['Users', 'Groups', 'Roles', 'RoleAndSubordinates'];

    public const TITLE_MAX = 100;
    public const URL_MAX = 2000;
    public const DESCRIPTION_MAX = 500;
    public const PARAMS_MAX = 20;
    public const PARAM_NAME_MAX = 100;
    public const PARAM_VALUE_MAX = 1000;
    public const WINDOW_MIN = 200;
    public const WINDOW_MAX = 4000;
    public const DEFAULT_WINDOW_WIDTH = 1024;
    public const DEFAULT_WINDOW_HEIGHT = 768;

    /** 差し込めないユーザーの項目（パスワード・認証用・テキストに向かない項目） */
    public const EXCLUDED_USER_FIELDS = [
        'user_password', 'confirm_password',
        'accesskey', 'user_hash', 'crypt_type', 'user_preferences',
        'imagename', 'signature',
    ];

    /** パスワード項目の uitype */
    public const PASSWORD_UITYPE = 99;

    /** ユーザーの項目以外に差し込める項目 */
    public const FIELD_ID = 'id';
    public const FIELD_FULL_NAME = 'full_name';

    /**
     * 画面から受け取った値を保存用の形にそろえる
     *
     * 型の違い（数値が文字列で来るなど）と前後の空白だけを吸収する。
     * 妥当かどうかは validate() で判定する。
     *
     * @param array<mixed> $input
     * @return SharedLinkInput
     */
    public static function normalize(array $input): array
    {
        $openMode = self::toString($input['open_mode'] ?? 'tab');
        $visibility = self::toString($input['visibility'] ?? 'all');

        $params = [];
        foreach (self::toList($input['params'] ?? []) as $param) {
            $params[] = [
                'name' => trim(self::toString($param['name'] ?? '')),
                'value_type' => self::toString($param['value_type'] ?? 'fixed'),
                'value' => self::toString($param['value'] ?? ''),
            ];
        }

        $targets = [];
        foreach (self::toList($input['targets'] ?? []) as $target) {
            $targets[] = [
                'type' => self::toString($target['type'] ?? ''),
                'id' => trim(self::toString($target['id'] ?? '')),
            ];
        }

        return [
            'title' => trim(self::toString($input['title'] ?? '')),
            'url' => trim(self::toString($input['url'] ?? '')),
            'description' => trim(self::toString($input['description'] ?? '')),
            'method' => strtoupper(self::toString($input['method'] ?? 'GET')),
            'open_mode' => $openMode,
            'window_width' => $openMode === 'window' ? self::toIntOrNull($input['window_width'] ?? null) : null,
            'window_height' => $openMode === 'window' ? self::toIntOrNull($input['window_height'] ?? null) : null,
            'noreferrer' => !empty($input['noreferrer']),
            'active' => !array_key_exists('active', $input) || !empty($input['active']),
            'visibility' => $visibility,
            'params' => $params,
            // 「全員」のときは対象を持たない
            'targets' => $visibility === 'targets' ? $targets : [],
        ];
    }

    /**
     * 入力を検証する
     *
     * @param SharedLinkInput $link normalize() の戻り値
     * @param list<string> $allowedUserFields 差し込める項目名
     * @param callable(string, string): bool $targetExists 公開範囲の対象が存在するか（種類, ID）
     * @return array<string, string> 項目ごとのエラー（キーは項目名、値は言語ファイルのキー）。空なら妥当
     */
    public static function validate(array $link, array $allowedUserFields, callable $targetExists): array
    {
        $errors = [];

        $title = $link['title'];
        if ($title === '') {
            $errors['title'] = 'LBL_ERROR_TITLE_REQUIRED';
        } elseif (mb_strlen($title) > self::TITLE_MAX) {
            $errors['title'] = 'LBL_ERROR_TITLE_TOO_LONG';
        }

        if ($link['url'] === '') {
            $errors['url'] = 'LBL_ERROR_URL_REQUIRED';
        } elseif (mb_strlen($link['url']) > self::URL_MAX) {
            $errors['url'] = 'LBL_ERROR_URL_TOO_LONG';
        } elseif (!self::isValidUrl($link['url'])) {
            $errors['url'] = 'LBL_ERROR_URL_INVALID';
        }

        if (mb_strlen($link['description']) > self::DESCRIPTION_MAX) {
            $errors['description'] = 'LBL_ERROR_DESCRIPTION_TOO_LONG';
        }

        if (!in_array($link['method'], self::METHODS, true)) {
            $errors['method'] = 'LBL_ERROR_INVALID_VALUE';
        }

        if (!in_array($link['open_mode'], self::OPEN_MODES, true)) {
            $errors['open_mode'] = 'LBL_ERROR_INVALID_VALUE';
        } elseif ($link['open_mode'] === 'window') {
            foreach (['window_width', 'window_height'] as $key) {
                $size = $link[$key];
                if ($size === null || $size < self::WINDOW_MIN || $size > self::WINDOW_MAX) {
                    $errors[$key] = 'LBL_ERROR_WINDOW_SIZE';
                }
            }
        }

        $params = $link['params'];
        if (count($params) > self::PARAMS_MAX) {
            $errors['params'] = 'LBL_ERROR_TOO_MANY_PARAMS';
        }
        foreach ($params as $index => $param) {
            if ($param['name'] === '') {
                $errors["params.$index.name"] = 'LBL_ERROR_PARAM_NAME_REQUIRED';
            } elseif (mb_strlen($param['name']) > self::PARAM_NAME_MAX) {
                $errors["params.$index.name"] = 'LBL_ERROR_PARAM_NAME_TOO_LONG';
            }

            if ($param['value_type'] === 'fixed') {
                if (mb_strlen($param['value']) > self::PARAM_VALUE_MAX) {
                    $errors["params.$index.value"] = 'LBL_ERROR_PARAM_VALUE_TOO_LONG';
                }
            } elseif ($param['value_type'] === 'user') {
                if ($param['value'] === '') {
                    $errors["params.$index.value"] = 'LBL_ERROR_USER_FIELD_REQUIRED';
                } elseif (!in_array($param['value'], $allowedUserFields, true)) {
                    $errors["params.$index.value"] = 'LBL_ERROR_USER_FIELD_INVALID';
                }
            } else {
                $errors["params.$index.value_type"] = 'LBL_ERROR_INVALID_VALUE';
            }
        }

        if (!in_array($link['visibility'], self::VISIBILITIES, true)) {
            $errors['visibility'] = 'LBL_ERROR_INVALID_VALUE';
        } elseif ($link['visibility'] === 'targets') {
            if (count($link['targets']) === 0) {
                $errors['targets'] = 'LBL_ERROR_TARGETS_REQUIRED';
            }
            $seen = [];
            foreach ($link['targets'] as $index => $target) {
                $key = $target['type'] . ':' . $target['id'];
                if (!in_array($target['type'], self::TARGET_TYPES, true) || $target['id'] === '') {
                    $errors["targets.$index"] = 'LBL_ERROR_INVALID_VALUE';
                } elseif (isset($seen[$key])) {
                    $errors["targets.$index"] = 'LBL_ERROR_TARGET_DUPLICATED';
                } elseif (!$targetExists($target['type'], $target['id'])) {
                    $errors["targets.$index"] = 'LBL_ERROR_TARGET_NOT_FOUND';
                }
                $seen[$key] = true;
            }
        }

        return $errors;
    }

    /**
     * http / https の URL かどうか
     */
    public static function isValidUrl(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            return false;
        }
        return in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }

    /**
     * 見ているユーザーが公開範囲に含まれるか
     *
     * 対象のどれか 1 つに当てはまれば表示する（OR）。
     * 削除された役割・グループ・ユーザーには、誰も当てはまらないため自然に無視される。
     *
     * @param string $visibility all / targets
     * @param list<SharedLinkTarget> $targets
     * @param SharedLinkUser $user
     */
    public static function isVisible(string $visibility, array $targets, array $user): bool
    {
        if ($visibility === 'all') {
            return true;
        }
        if ($visibility !== 'targets') {
            return false;
        }

        $roleChain = $user['parent_role_seq'] === '' ? [] : explode('::', $user['parent_role_seq']);
        $groups = array_map('strval', $user['groups']);

        foreach ($targets as $target) {
            $id = (string) $target['id'];
            switch ($target['type']) {
                case 'Users':
                    if ($id === (string) $user['id']) {
                        return true;
                    }
                    break;
                case 'Groups':
                    if (in_array($id, $groups, true)) {
                        return true;
                    }
                    break;
                case 'Roles':
                    if ($id === $user['roleid']) {
                        return true;
                    }
                    break;
                case 'RoleAndSubordinates':
                    if ($id === $user['roleid'] || in_array($id, $roleChain, true)) {
                        return true;
                    }
                    break;
            }
        }
        return false;
    }

    /**
     * 役割を、親の後ろに子が来る順（階層順）に並べる
     *
     * 階層の経路（H1::H2::H5）の文字列順で並べると、子孫は親の直後にまとまる。
     * 末尾に区切りを足して比べるのは、ID の先頭が同じ兄弟（H1::H2 と H1::H20）があっても、
     * H1::H2 の子孫（H1::H2::H5）が親から離れて H1::H20 の後ろへ回らないようにするため。
     * 兄弟同士の順序は問わない。
     *
     * @param list<array{id: string, name: string, depth: int, path: string}> $roles
     * @return list<array{id: string, name: string, depth: int}>
     */
    public static function sortRolesByHierarchy(array $roles): array
    {
        usort($roles, function (array $a, array $b): int {
            return strcmp($a['path'] . '::', $b['path'] . '::');
        });
        return array_map(function (array $role): array {
            return ['id' => $role['id'], 'name' => $role['name'], 'depth' => $role['depth']];
        }, $roles);
    }

    /**
     * 差し込める項目かどうか
     */
    public static function isInsertableUserField(string $fieldName, int $uitype): bool
    {
        return $uitype !== self::PASSWORD_UITYPE && !in_array($fieldName, self::EXCLUDED_USER_FIELDS, true);
    }

    /**
     * 氏名（姓 名）を作る。どちらかが空なら、空でないほうだけ
     */
    public static function fullName(string $lastName, string $firstName): string
    {
        return implode(' ', array_filter([trim($lastName), trim($firstName)], function (string $part): bool {
            return $part !== '';
        }));
    }

    /**
     * パラメータの値を解決する
     *
     * @param list<SharedLinkParam> $params
     * @param array<string, string> $userValues 見ているユーザーの項目の値（差し込める項目だけ）
     * @return list<array{name: string, value: string}>
     */
    public static function resolveParams(array $params, array $userValues): array
    {
        $resolved = [];
        foreach ($params as $param) {
            if ($param['value_type'] === 'user') {
                // 保存後に項目が削除された・差し込めなくなったときは空文字を送る
                $value = $userValues[$param['value']] ?? '';
            } else {
                $value = $param['value'];
            }
            $resolved[] = ['name' => $param['name'], 'value' => $value];
        }
        return $resolved;
    }

    /**
     * GET で開く URL を組み立てる
     *
     * URL に既にあるクエリ文字列は残し、その後ろにパラメータを足す（同じ名前でも上書きしない）。
     * フラグメント（#以降）はパラメータの後ろへ付け直す。
     *
     * @param list<array{name: string, value: string}> $params
     */
    public static function buildGetUrl(string $url, array $params): string
    {
        if (count($params) === 0) {
            return $url;
        }

        $fragment = '';
        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $fragment = substr($url, $hashPos);
            $url = substr($url, 0, $hashPos);
        }

        $pairs = [];
        foreach ($params as $param) {
            $pairs[] = rawurlencode($param['name']) . '=' . rawurlencode($param['value']);
        }
        $query = implode('&', $pairs);

        if (strpos($url, '?') === false) {
            $url .= '?' . $query;
        } elseif (substr($url, -1) === '?' || substr($url, -1) === '&') {
            $url .= $query;
        } else {
            $url .= '&' . $query;
        }

        return $url . $fragment;
    }

    /**
     * 文字列にする。スカラー以外（配列・null など）は空文字
     */
    public static function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * 整数にする。整数（または数字だけの文字列）でなければ null
     */
    public static function toIntOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', trim($value))) {
            return (int) trim($value);
        }
        return null;
    }

    /**
     * @return list<array<mixed>>
     */
    private static function toList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $list = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $list[] = $item;
            }
        }
        return $list;
    }
}

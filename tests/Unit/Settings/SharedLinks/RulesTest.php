<?php

declare(strict_types=1);

namespace Tests\Unit\Settings\SharedLinks;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Settings_SharedLinks_Rules_Model as Rules;

require_once dirname(__DIR__, 4) . '/modules/Settings/SharedLinks/models/Rules.php';

/**
 * 共有リンクの判定ロジック（Settings_SharedLinks_Rules_Model）のテスト
 *
 * 仕様: docs/specs/shared_links_widget.md
 *   3.1 入力の制約 / 3.3 開き方 / 3.4 公開範囲 / 4 ユーザー情報の差し込み / 6.1 入力の検証 / 7.2 GET のパラメータ
 */
final class RulesTest extends TestCase
{
    /** 妥当な入力（各テストはここから 1 か所だけ変える） */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'title' => '勤怠システム',
            'url' => 'https://kintai.example.com/login',
            'description' => '',
            'method' => 'GET',
            'open_mode' => 'tab',
            'window_width' => null,
            'window_height' => null,
            'noreferrer' => false,
            'active' => true,
            'visibility' => 'all',
            'params' => [],
            'targets' => [],
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validate(array $input, ?callable $targetExists = null): array
    {
        return Rules::validate(
            Rules::normalize($input),
            ['email1', 'user_name', 'id', 'full_name'],
            $targetExists ?? fn (string $type, string $id): bool => true
        );
    }

    // ---- normalize ------------------------------------------------------

    public function test_normalize_前後の空白を落としメソッドを大文字にする(): void
    {
        $link = Rules::normalize($this->validInput([
            'title' => '  勤怠  ',
            'url' => ' https://a.example.com ',
            'method' => 'post',
            'params' => [['name' => ' id ', 'value_type' => 'fixed', 'value' => ' 値 ']],
        ]));

        $this->assertSame('勤怠', $link['title']);
        $this->assertSame('https://a.example.com', $link['url']);
        $this->assertSame('POST', $link['method']);
        $this->assertSame('id', $link['params'][0]['name']);
        // 固定値の空白は意味を持ちうるため残す
        $this->assertSame(' 値 ', $link['params'][0]['value']);
    }

    public function test_normalize_別タブのときはウィンドウサイズを捨てる(): void
    {
        $link = Rules::normalize($this->validInput([
            'open_mode' => 'tab', 'window_width' => 800, 'window_height' => 600,
        ]));

        $this->assertNull($link['window_width']);
        $this->assertNull($link['window_height']);
    }

    public function test_normalize_別ウィンドウのサイズは数字の文字列も受け付け数字以外はnullにする(): void
    {
        $link = Rules::normalize($this->validInput([
            'open_mode' => 'window', 'window_width' => '800', 'window_height' => '6oo',
        ]));

        $this->assertSame(800, $link['window_width']);
        $this->assertNull($link['window_height']);
    }

    public function test_normalize_全員のときは公開範囲の対象を捨てる(): void
    {
        $link = Rules::normalize($this->validInput([
            'visibility' => 'all', 'targets' => [['type' => 'Users', 'id' => '1']],
        ]));

        $this->assertSame([], $link['targets']);
    }

    public function test_normalize_activeを省略したら有効にする(): void
    {
        $input = $this->validInput();
        unset($input['active']);

        $this->assertTrue(Rules::normalize($input)['active']);
        $this->assertFalse(Rules::normalize($this->validInput(['active' => false]))['active']);
    }

    public function test_normalize_配列でないparamsやtargetsの要素は捨てる(): void
    {
        $link = Rules::normalize($this->validInput([
            'visibility' => 'targets',
            'params' => ['x', ['name' => 'a', 'value_type' => 'fixed', 'value' => '1']],
            'targets' => 'Users:1',
        ]));

        $this->assertCount(1, $link['params']);
        $this->assertSame([], $link['targets']);
    }

    // ---- validate -------------------------------------------------------

    public function test_validate_妥当な入力はエラーなし(): void
    {
        $this->assertSame([], $this->validate($this->validInput()));
    }

    public function test_validate_タイトルは必須で100文字まで(): void
    {
        $this->assertSame('LBL_ERROR_TITLE_REQUIRED', $this->validate($this->validInput(['title' => ' ']))['title']);
        $this->assertArrayNotHasKey('title', $this->validate($this->validInput(['title' => str_repeat('あ', 100)])));
        $this->assertSame(
            'LBL_ERROR_TITLE_TOO_LONG',
            $this->validate($this->validInput(['title' => str_repeat('あ', 101)]))['title']
        );
    }

    public function test_validate_URLは必須(): void
    {
        $this->assertSame('LBL_ERROR_URL_REQUIRED', $this->validate($this->validInput(['url' => '']))['url']);
    }

    public function test_validate_URLは2000文字まで(): void
    {
        $base = 'https://example.com/';
        $ok = $base . str_repeat('a', 2000 - strlen($base));
        $this->assertArrayNotHasKey('url', $this->validate($this->validInput(['url' => $ok])));
        $this->assertSame('LBL_ERROR_URL_TOO_LONG', $this->validate($this->validInput(['url' => $ok . 'a']))['url']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'ftp' => ['ftp://example.com/'],
            'スキームなし' => ['example.com/path'],
            'ホストなし' => ['https:///path'],
            '相対パス' => ['/index.php'],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function test_validate_httpとhttps以外のURLは保存しない(string $url): void
    {
        $this->assertSame('LBL_ERROR_URL_INVALID', $this->validate($this->validInput(['url' => $url]))['url']);
    }

    public function test_validate_大文字のスキームやクエリ付きのURLは受け付ける(): void
    {
        $this->assertSame([], $this->validate($this->validInput(['url' => 'HTTPS://example.com/a?b=1&c=2#top'])));
        $this->assertSame([], $this->validate($this->validInput(['url' => 'http://192.168.0.1:8080/'])));
    }

    public function test_validate_説明は500文字まで(): void
    {
        $this->assertSame([], $this->validate($this->validInput(['description' => str_repeat('あ', 500)])));
        $this->assertSame(
            'LBL_ERROR_DESCRIPTION_TOO_LONG',
            $this->validate($this->validInput(['description' => str_repeat('あ', 501)]))['description']
        );
    }

    public function test_validate_メソッドと開き方と公開範囲は決まった値だけ(): void
    {
        $this->assertSame('LBL_ERROR_INVALID_VALUE', $this->validate($this->validInput(['method' => 'PUT']))['method']);
        $this->assertSame(
            'LBL_ERROR_INVALID_VALUE',
            $this->validate($this->validInput(['open_mode' => 'popup']))['open_mode']
        );
        $this->assertSame(
            'LBL_ERROR_INVALID_VALUE',
            $this->validate($this->validInput(['visibility' => 'none']))['visibility']
        );
    }

    /**
     * @return array<string, array{int|string|null, bool}>
     */
    public static function windowSizes(): array
    {
        return [
            '199' => [199, false],
            '200' => [200, true],
            '4000' => [4000, true],
            '4001' => [4001, false],
            '未入力' => [null, false],
            '0' => [0, false],
            '負の数' => ['-300', false],
            '小数' => ['300.5', false],
        ];
    }

    #[DataProvider('windowSizes')]
    public function test_validate_別ウィンドウの幅と高さは200から4000(int|string|null $size, bool $valid): void
    {
        $errors = $this->validate($this->validInput([
            'open_mode' => 'window', 'window_width' => $size, 'window_height' => $size,
        ]));

        if ($valid) {
            $this->assertSame([], $errors);
        } else {
            $this->assertSame('LBL_ERROR_WINDOW_SIZE', $errors['window_width'] ?? null);
            $this->assertSame('LBL_ERROR_WINDOW_SIZE', $errors['window_height'] ?? null);
        }
    }

    public function test_validate_別タブならウィンドウサイズは見ない(): void
    {
        $this->assertSame([], $this->validate($this->validInput([
            'open_mode' => 'tab', 'window_width' => 1, 'window_height' => 99999,
        ])));
    }

    public function test_validate_パラメータは20件まで(): void
    {
        $param = ['name' => 'a', 'value_type' => 'fixed', 'value' => ''];
        $this->assertSame([], $this->validate($this->validInput(['params' => array_fill(0, 20, $param)])));
        $this->assertSame(
            'LBL_ERROR_TOO_MANY_PARAMS',
            $this->validate($this->validInput(['params' => array_fill(0, 21, $param)]))['params']
        );
    }

    public function test_validate_パラメータ名は必須で100文字まで(): void
    {
        $errors = $this->validate($this->validInput(['params' => [
            ['name' => '', 'value_type' => 'fixed', 'value' => 'x'],
            ['name' => str_repeat('a', 100), 'value_type' => 'fixed', 'value' => 'x'],
            ['name' => str_repeat('a', 101), 'value_type' => 'fixed', 'value' => 'x'],
        ]]));

        $this->assertSame('LBL_ERROR_PARAM_NAME_REQUIRED', $errors['params.0.name']);
        $this->assertArrayNotHasKey('params.1.name', $errors);
        $this->assertSame('LBL_ERROR_PARAM_NAME_TOO_LONG', $errors['params.2.name']);
    }

    public function test_validate_固定値は空でもよく1000文字まで(): void
    {
        $errors = $this->validate($this->validInput(['params' => [
            ['name' => 'a', 'value_type' => 'fixed', 'value' => ''],
            ['name' => 'b', 'value_type' => 'fixed', 'value' => str_repeat('あ', 1000)],
            ['name' => 'c', 'value_type' => 'fixed', 'value' => str_repeat('あ', 1001)],
        ]]));

        $this->assertSame(['params.2.value' => 'LBL_ERROR_PARAM_VALUE_TOO_LONG'], $errors);
    }

    public function test_validate_ユーザー情報は項目の選択が必須で差し込める項目だけ(): void
    {
        $errors = $this->validate($this->validInput(['params' => [
            ['name' => 'a', 'value_type' => 'user', 'value' => ''],
            ['name' => 'b', 'value_type' => 'user', 'value' => 'email1'],
            ['name' => 'c', 'value_type' => 'user', 'value' => 'user_password'],
        ]]));

        $this->assertSame([
            'params.0.value' => 'LBL_ERROR_USER_FIELD_REQUIRED',
            'params.2.value' => 'LBL_ERROR_USER_FIELD_INVALID',
        ], $errors);
    }

    public function test_validate_値の種類は固定値かユーザー情報だけ(): void
    {
        $errors = $this->validate($this->validInput(['params' => [
            ['name' => 'a', 'value_type' => 'expression', 'value' => '{user.email1}'],
        ]]));

        $this->assertSame(['params.0.value_type' => 'LBL_ERROR_INVALID_VALUE'], $errors);
    }

    public function test_validate_指定した対象のみなら対象が1件以上必要(): void
    {
        $this->assertSame(
            ['targets' => 'LBL_ERROR_TARGETS_REQUIRED'],
            $this->validate($this->validInput(['visibility' => 'targets', 'targets' => []]))
        );
    }

    public function test_validate_対象の種類とIDと重複と存在を確かめる(): void
    {
        $errors = $this->validate(
            $this->validInput(['visibility' => 'targets', 'targets' => [
                ['type' => 'Roles', 'id' => 'H2'],
                ['type' => 'RoleAndSubordinates', 'id' => 'H2'],
                ['type' => 'Roles', 'id' => 'H2'],
                ['type' => 'Profiles', 'id' => '1'],
                ['type' => 'Users', 'id' => ''],
                ['type' => 'Groups', 'id' => '999'],
            ]]),
            fn (string $type, string $id): bool => !($type === 'Groups' && $id === '999')
        );

        // 「役割」と「役割と下位」は別の対象として両方追加できる
        $this->assertSame([
            'targets.2' => 'LBL_ERROR_TARGET_DUPLICATED',
            'targets.3' => 'LBL_ERROR_INVALID_VALUE',
            'targets.4' => 'LBL_ERROR_INVALID_VALUE',
            'targets.5' => 'LBL_ERROR_TARGET_NOT_FOUND',
        ], $errors);
    }

    public function test_validate_存在の確認には種類とIDを渡す(): void
    {
        $calls = [];
        $this->validate(
            $this->validInput(['visibility' => 'targets', 'targets' => [['type' => 'Users', 'id' => '5']]]),
            function (string $type, string $id) use (&$calls): bool {
                $calls[] = [$type, $id];
                return true;
            }
        );

        $this->assertSame([['Users', '5']], $calls);
    }

    // ---- isVisible ------------------------------------------------------

    /** 営業部（H3）の配下の役割（H5）を持ち、グループ 10・20 に属するユーザー */
    private const USER = [
        'id' => 7,
        'roleid' => 'H5',
        'parent_role_seq' => 'H1::H2::H3::H5',
        'groups' => [10, 20],
    ];

    /**
     * @return array<string, array{string, list<array{type: string, id: string}>, bool}>
     */
    public static function visibilityCases(): array
    {
        return [
            '全員' => ['all', [], true],
            'ユーザー一致' => ['targets', [['type' => 'Users', 'id' => '7']], true],
            'ユーザー不一致' => ['targets', [['type' => 'Users', 'id' => '8']], false],
            'グループに属する' => ['targets', [['type' => 'Groups', 'id' => '20']], true],
            'グループに属さない' => ['targets', [['type' => 'Groups', 'id' => '30']], false],
            '役割一致' => ['targets', [['type' => 'Roles', 'id' => 'H5']], true],
            '上位の役割（下位を含まない）' => ['targets', [['type' => 'Roles', 'id' => 'H3']], false],
            '上位の役割（下位を含む）' => ['targets', [['type' => 'RoleAndSubordinates', 'id' => 'H3']], true],
            '自分の役割（下位を含む）' => ['targets', [['type' => 'RoleAndSubordinates', 'id' => 'H5']], true],
            '別系統の役割（下位を含む）' => ['targets', [['type' => 'RoleAndSubordinates', 'id' => 'H4']], false],
            'IDの前方一致で誤判定しない' => ['targets', [['type' => 'RoleAndSubordinates', 'id' => 'H']], false],
            'どれか1つに当てはまれば表示（OR）' => ['targets', [
                ['type' => 'Users', 'id' => '99'],
                ['type' => 'Groups', 'id' => '10'],
            ], true],
            '対象なし' => ['targets', [], false],
            '削除された対象には誰も当てはまらない' => ['targets', [['type' => 'Roles', 'id' => 'H999']], false],
            '不明な公開範囲' => ['nobody', [['type' => 'Users', 'id' => '7']], false],
            '不明な種類' => ['targets', [['type' => 'Profiles', 'id' => '7']], false],
        ];
    }

    /**
     * @param list<array{type: string, id: string}> $targets
     */
    #[DataProvider('visibilityCases')]
    public function test_isVisible_公開範囲の判定(string $visibility, array $targets, bool $expected): void
    {
        $this->assertSame($expected, Rules::isVisible($visibility, $targets, self::USER));
    }

    public function test_isVisible_グループIDは文字列でも数値でも一致する(): void
    {
        $user = ['id' => '7', 'roleid' => 'H2', 'parent_role_seq' => 'H1::H2', 'groups' => ['10']];

        $this->assertTrue(Rules::isVisible('targets', [['type' => 'Groups', 'id' => '10']], $user));
        $this->assertTrue(Rules::isVisible('targets', [['type' => 'Users', 'id' => '7']], $user));
    }

    public function test_isVisible_役割の経路が空でも落ちない(): void
    {
        $user = ['id' => 1, 'roleid' => '', 'parent_role_seq' => '', 'groups' => []];

        $this->assertFalse(Rules::isVisible('targets', [['type' => 'RoleAndSubordinates', 'id' => 'H1']], $user));
    }

    // ---- 役割の並び順 ---------------------------------------------------

    public function test_sortRolesByHierarchy_親の後ろに子が並ぶ(): void
    {
        $role = fn (string $path): array => [
            'id' => substr($path, (int) strrpos(':' . $path, ':')),
            'name' => $path,
            'depth' => substr_count($path, '::'),
            'path' => $path,
        ];
        $sorted = Rules::sortRolesByHierarchy([
            $role('H1::H20'),
            $role('H1::H2::H5'),
            $role('H1'),
            $role('H1::H2'),
            $role('H1::H20::H21'),
            $role('H1::H2::H3'),
        ]);

        // 子孫は親の直後にまとまる。ID の先頭が同じ兄弟（H2 と H20）があっても、
        // H2 の子孫が H20 の後ろへ回って親から離れない
        $this->assertSame(
            ['H1', 'H1::H20', 'H1::H20::H21', 'H1::H2', 'H1::H2::H3', 'H1::H2::H5'],
            array_column($sorted, 'name')
        );
        // 経路は返さない
        $this->assertSame(['id', 'name', 'depth'], array_keys($sorted[0]));
    }

    // ---- 差し込める項目・値の解決 --------------------------------------

    public function test_isInsertableUserField_パスワードや認証用の項目は差し込めない(): void
    {
        foreach (Rules::EXCLUDED_USER_FIELDS as $fieldName) {
            $this->assertFalse(Rules::isInsertableUserField($fieldName, 1), $fieldName);
        }
        $this->assertFalse(Rules::isInsertableUserField('cf_secret', Rules::PASSWORD_UITYPE));
        $this->assertTrue(Rules::isInsertableUserField('email1', 13));
        $this->assertTrue(Rules::isInsertableUserField('cf_employee_no', 1));
    }

    public function test_fullName_姓と名を半角スペースでつなぎ片方が空ならもう片方だけ(): void
    {
        $this->assertSame('山田 太郎', Rules::fullName('山田', '太郎'));
        $this->assertSame('山田', Rules::fullName('山田', ''));
        $this->assertSame('太郎', Rules::fullName(' ', '太郎'));
        $this->assertSame('', Rules::fullName('', ''));
        // 「0」も名前として扱う（空扱いしない）
        $this->assertSame('0 太郎', Rules::fullName('0', '太郎'));
    }

    public function test_resolveParams_固定値とユーザー情報を解決し順序を保つ(): void
    {
        $resolved = Rules::resolveParams([
            ['name' => 'from', 'value_type' => 'fixed', 'value' => 'crm'],
            ['name' => 'mail', 'value_type' => 'user', 'value' => 'email1'],
            ['name' => 'uid', 'value_type' => 'user', 'value' => 'id'],
            ['name' => 'gone', 'value_type' => 'user', 'value' => 'cf_deleted'],
        ], ['email1' => 'taro@example.com', 'id' => '7']);

        $this->assertSame([
            ['name' => 'from', 'value' => 'crm'],
            ['name' => 'mail', 'value' => 'taro@example.com'],
            ['name' => 'uid', 'value' => '7'],
            // 削除された項目は空文字を送る
            ['name' => 'gone', 'value' => ''],
        ], $resolved);
    }

    public function test_resolveParams_固定値の中の記法は置き換えない(): void
    {
        $resolved = Rules::resolveParams(
            [['name' => 'a', 'value_type' => 'fixed', 'value' => '{user.email1}']],
            ['email1' => 'taro@example.com']
        );

        $this->assertSame('{user.email1}', $resolved[0]['value']);
    }

    // ---- buildGetUrl ----------------------------------------------------

    /**
     * @return array<string, array{string, list<array{name: string, value: string}>, string}>
     */
    public static function getUrlCases(): array
    {
        return [
            'パラメータなし' => ['https://a.example.com/x?y=1#top', [], 'https://a.example.com/x?y=1#top'],
            'クエリなし' => ['https://a.example.com/x', [['name' => 'id', 'value' => '7']], 'https://a.example.com/x?id=7'],
            '既存のクエリの後ろに足す' => [
                'https://a.example.com/x?lang=ja',
                [['name' => 'id', 'value' => '7']],
                'https://a.example.com/x?lang=ja&id=7',
            ],
            '同じ名前でも上書きしない' => [
                'https://a.example.com/x?a=1',
                [['name' => 'a', 'value' => '2'], ['name' => 'a', 'value' => '3']],
                'https://a.example.com/x?a=1&a=2&a=3',
            ],
            '末尾が?' => ['https://a.example.com/x?', [['name' => 'a', 'value' => '1']], 'https://a.example.com/x?a=1'],
            '末尾が&' => ['https://a.example.com/x?b=2&', [['name' => 'a', 'value' => '1']], 'https://a.example.com/x?b=2&a=1'],
            'フラグメントは後ろへ付け直す' => [
                'https://a.example.com/x?b=2#section',
                [['name' => 'a', 'value' => '1']],
                'https://a.example.com/x?b=2&a=1#section',
            ],
            'URLエンコード' => [
                'https://a.example.com/',
                [['name' => 'q&x', 'value' => '山田 太郎&a=b#c+d']],
                'https://a.example.com/?q%26x=%E5%B1%B1%E7%94%B0%20%E5%A4%AA%E9%83%8E%26a%3Db%23c%2Bd',
            ],
            '空の値' => ['https://a.example.com/', [['name' => 'a', 'value' => '']], 'https://a.example.com/?a='],
        ];
    }

    /**
     * @param list<array{name: string, value: string}> $params
     */
    #[DataProvider('getUrlCases')]
    public function test_buildGetUrl_パラメータを付けたURLを組み立てる(string $url, array $params, string $expected): void
    {
        $this->assertSame($expected, Rules::buildGetUrl($url, $params));
    }
}

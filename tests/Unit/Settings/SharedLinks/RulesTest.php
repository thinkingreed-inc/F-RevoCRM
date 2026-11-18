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
 * テスト仕様書: docs/tests/SharedLinks/
 *   TS-01 入力の検証 / TS-02 公開範囲の判定 / TS-03 値の解決と URL
 * メソッド名とデータの見出しに仕様書のケース ID を入れている。
 */
final class RulesTest extends TestCase
{
    /** 妥当な入力（各テストはここから一部だけ変える） */
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

    /**
     * @param array{name: string, value_type: string, value: string} ...$params
     * @return array<string, string>
     */
    private function validateParams(array ...$params): array
    {
        return $this->validate($this->validInput(['params' => $params]));
    }

    private static function param(string $name, string $type, string $value): array
    {
        return ['name' => $name, 'value_type' => $type, 'value' => $value];
    }

    // ==== TS-01 入力の検証 ==================================================

    public function test_妥当な入力はエラーなし(): void
    {
        $this->assertSame([], $this->validate($this->validInput()));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function titles(): array
    {
        return [
            'TC-VAL-001 空' => ['', 'LBL_ERROR_TITLE_REQUIRED'],
            'TC-VAL-001 空白だけ' => ['   ', 'LBL_ERROR_TITLE_REQUIRED'],
            'TC-VAL-002 100文字' => [str_repeat('あ', 100), null],
            'TC-VAL-003 サロゲートペア100文字' => [str_repeat('𠮷', 100), null],
            'TC-VAL-004 101文字' => [str_repeat('あ', 101), 'LBL_ERROR_TITLE_TOO_LONG'],
        ];
    }

    #[DataProvider('titles')]
    public function test_TC_VAL_001_004_タイトル(string $title, ?string $expected): void
    {
        $this->assertSame($expected, $this->validate($this->validInput(['title' => $title]))['title'] ?? null);
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function urls(): array
    {
        $base = 'https://example.com/';
        $max = $base . str_repeat('a', 2000 - strlen($base));
        return [
            'TC-VAL-010 空' => ['', 'LBL_ERROR_URL_REQUIRED'],
            'TC-VAL-011 2000文字' => [$max, null],
            'TC-VAL-012 2001文字' => [$max . 'a', 'LBL_ERROR_URL_TOO_LONG'],
            'TC-VAL-013 スキームなし' => ['example.com/path', 'LBL_ERROR_URL_INVALID'],
            'TC-VAL-013 ホストなし' => ['https:///path', 'LBL_ERROR_URL_INVALID'],
            'TC-VAL-013 相対パス' => ['/index.php', 'LBL_ERROR_URL_INVALID'],
            'TC-VAL-014 javascript' => ['javascript:alert(1)', 'LBL_ERROR_URL_INVALID'],
            'TC-VAL-014 data' => ['data:text/html,<script>alert(1)</script>', 'LBL_ERROR_URL_INVALID'],
            'TC-VAL-014 ftp' => ['ftp://example.com/', 'LBL_ERROR_URL_INVALID'],
            'TC-VAL-015 大文字のスキームとクエリ' => ['HTTPS://example.com/a?b=1&c=2#top', null],
            'TC-VAL-015 IPとポート' => ['http://192.168.0.1:8080/', null],
        ];
    }

    #[DataProvider('urls')]
    public function test_TC_VAL_010_015_URL(string $url, ?string $expected): void
    {
        $this->assertSame($expected, $this->validate($this->validInput(['url' => $url]))['url'] ?? null);
    }

    public function test_TC_VAL_016_説明は500文字まで(): void
    {
        $this->assertSame([], $this->validate($this->validInput(['description' => str_repeat('あ', 500)])));
        $this->assertSame(
            ['description' => 'LBL_ERROR_DESCRIPTION_TOO_LONG'],
            $this->validate($this->validInput(['description' => str_repeat('あ', 501)]))
        );
    }

    public function test_TC_VAL_017_メソッドは大文字にそろえGETとPOSTだけ(): void
    {
        $this->assertSame('POST', Rules::normalize($this->validInput(['method' => 'post']))['method']);
        $this->assertSame([], $this->validate($this->validInput(['method' => 'post'])));
        $this->assertSame(['method' => 'LBL_ERROR_INVALID_VALUE'], $this->validate($this->validInput(['method' => 'PUT'])));
    }

    public function test_TC_VAL_020_別タブならサイズは見ずに捨てる(): void
    {
        $input = $this->validInput(['open_mode' => 'tab', 'window_width' => 1, 'window_height' => 99999]);

        $this->assertSame([], $this->validate($input));
        $link = Rules::normalize($input);
        $this->assertNull($link['window_width']);
        $this->assertNull($link['window_height']);
    }

    /**
     * @return array<string, array{int|string|float|null, bool}>
     */
    public static function windowSizes(): array
    {
        return [
            'TC-VAL-021 200' => [200, true],
            'TC-VAL-021 4000' => [4000, true],
            'TC-VAL-022 199' => [199, false],
            'TC-VAL-022 0' => [0, false],
            'TC-VAL-023 4001' => [4001, false],
            'TC-VAL-024 未入力' => [null, false],
            'TC-VAL-024 小数' => ['300.5', false],
            'TC-VAL-024 負の数' => ['-300', false],
            'TC-VAL-024 数字以外' => ['6oo', false],
            'TC-VAL-024 float' => [300.0, false],
            'TC-VAL-026 数字の文字列' => ['800', true],
        ];
    }

    #[DataProvider('windowSizes')]
    public function test_TC_VAL_021_026_別ウィンドウの幅と高さ(int|string|float|null $size, bool $valid): void
    {
        $errors = $this->validate($this->validInput([
            'open_mode' => 'window', 'window_width' => $size, 'window_height' => $size,
        ]));

        if ($valid) {
            $this->assertSame([], $errors);
        } else {
            $this->assertSame(
                ['window_width' => 'LBL_ERROR_WINDOW_SIZE', 'window_height' => 'LBL_ERROR_WINDOW_SIZE'],
                $errors
            );
        }
    }

    public function test_TC_VAL_025_開き方は決まった値だけ(): void
    {
        $this->assertSame(
            ['open_mode' => 'LBL_ERROR_INVALID_VALUE'],
            $this->validate($this->validInput(['open_mode' => 'popup']))
        );
    }

    public function test_TC_VAL_030_031_パラメータ名は必須で100文字まで(): void
    {
        $this->assertSame([
            'params.0.name' => 'LBL_ERROR_PARAM_NAME_REQUIRED',
            'params.1.name' => 'LBL_ERROR_PARAM_NAME_REQUIRED',
            'params.3.name' => 'LBL_ERROR_PARAM_NAME_TOO_LONG',
        ], $this->validateParams(
            self::param('', 'fixed', 'x'),
            self::param(' ', 'fixed', 'x'),
            self::param(str_repeat('a', 100), 'fixed', 'x'),
            self::param(str_repeat('a', 101), 'fixed', 'x'),
        ));
    }

    public function test_TC_VAL_032_034_固定値は空でもよく1000文字まで(): void
    {
        $this->assertSame(['params.2.value' => 'LBL_ERROR_PARAM_VALUE_TOO_LONG'], $this->validateParams(
            self::param('a', 'fixed', ''),
            self::param('b', 'fixed', str_repeat('あ', 1000)),
            self::param('c', 'fixed', str_repeat('あ', 1001)),
        ));
    }

    public function test_TC_VAL_035_037_ユーザー情報は項目の選択が必須で差し込める項目だけ(): void
    {
        $this->assertSame([
            'params.0.value' => 'LBL_ERROR_USER_FIELD_REQUIRED',
            'params.2.value' => 'LBL_ERROR_USER_FIELD_INVALID',
            'params.3.value' => 'LBL_ERROR_USER_FIELD_INVALID',
        ], $this->validateParams(
            self::param('a', 'user', ''),
            self::param('b', 'user', 'email1'),
            self::param('c', 'user', 'user_password'),
            self::param('d', 'user', 'accesskey'),
        ));
    }

    public function test_TC_VAL_038_値の種類は固定値かユーザー情報だけ(): void
    {
        $this->assertSame(
            ['params.0.value_type' => 'LBL_ERROR_INVALID_VALUE'],
            $this->validateParams(self::param('a', 'expression', '{user.email1}'))
        );
    }

    public function test_TC_VAL_039_パラメータは20行まで(): void
    {
        $param = self::param('a', 'fixed', '');
        $this->assertSame([], $this->validate($this->validInput(['params' => array_fill(0, 20, $param)])));
        $this->assertSame(
            ['params' => 'LBL_ERROR_TOO_MANY_PARAMS'],
            $this->validate($this->validInput(['params' => array_fill(0, 21, $param)]))
        );
    }

    public function test_TC_VAL_040_全員なら対象を捨てる(): void
    {
        $input = $this->validInput(['visibility' => 'all', 'targets' => [['type' => 'Profiles', 'id' => '']]]);

        $this->assertSame([], $this->validate($input));
        $this->assertSame([], Rules::normalize($input)['targets']);
    }

    public function test_TC_VAL_041_指定した対象のみなら対象が1件以上必要(): void
    {
        $this->assertSame(
            ['targets' => 'LBL_ERROR_TARGETS_REQUIRED'],
            $this->validate($this->validInput(['visibility' => 'targets', 'targets' => []]))
        );
    }

    public function test_TC_VAL_042_046_対象の種類とIDと重複と存在(): void
    {
        $errors = $this->validate(
            $this->validInput(['visibility' => 'targets', 'targets' => [
                ['type' => 'Roles', 'id' => 'H2'],
                ['type' => 'RoleAndSubordinates', 'id' => 'H2'],
                ['type' => 'Roles', 'id' => 'H2'],
                ['type' => 'Profiles', 'id' => '1'],
                ['type' => 'Users', 'id' => ''],
                ['type' => 'Groups', 'id' => '999'],
                ['type' => 'Users', 'id' => '5'],
            ]]),
            fn (string $type, string $id): bool => !($type === 'Groups' && $id === '999')
        );

        // TC-VAL-046: 「役割」と「役割と下位」は別の対象として両方追加できる
        $this->assertSame([
            'targets.2' => 'LBL_ERROR_TARGET_DUPLICATED', // TC-VAL-043
            'targets.3' => 'LBL_ERROR_INVALID_VALUE',     // TC-VAL-042
            'targets.4' => 'LBL_ERROR_INVALID_VALUE',     // TC-VAL-042
            'targets.5' => 'LBL_ERROR_TARGET_NOT_FOUND',  // TC-VAL-044
        ], $errors);
    }

    public function test_TC_VAL_045_存在の確認には種類とIDを渡す(): void
    {
        $calls = [];
        $errors = $this->validate(
            $this->validInput(['visibility' => 'targets', 'targets' => [
                ['type' => 'Users', 'id' => ' 5 '],
                ['type' => 'RoleAndSubordinates', 'id' => 'H3'],
            ]]),
            function (string $type, string $id) use (&$calls): bool {
                $calls[] = [$type, $id];
                return true;
            }
        );

        $this->assertSame([], $errors);
        $this->assertSame([['Users', '5'], ['RoleAndSubordinates', 'H3']], $calls);
    }

    public function test_TC_VAL_047_公開範囲は決まった値だけ(): void
    {
        $this->assertSame(
            ['visibility' => 'LBL_ERROR_INVALID_VALUE'],
            $this->validate($this->validInput(['visibility' => 'none']))
        );
    }

    public function test_TC_VAL_060_前後の空白を落とし固定値の空白は残す(): void
    {
        $link = Rules::normalize($this->validInput([
            'title' => '  勤怠  ',
            'url' => ' https://a.example.com ',
            'description' => ' 説明 ',
            'params' => [self::param(' id ', 'fixed', ' 値 ')],
        ]));

        $this->assertSame('勤怠', $link['title']);
        $this->assertSame('https://a.example.com', $link['url']);
        $this->assertSame('説明', $link['description']);
        $this->assertSame('id', $link['params'][0]['name']);
        $this->assertSame(' 値 ', $link['params'][0]['value']);
    }

    public function test_TC_VAL_061_有効とリファラーの既定値(): void
    {
        $input = $this->validInput();
        unset($input['active'], $input['noreferrer']);

        $link = Rules::normalize($input);
        $this->assertTrue($link['active']);
        $this->assertFalse($link['noreferrer']);
        $this->assertFalse(Rules::normalize($this->validInput(['active' => false]))['active']);
        $this->assertTrue(Rules::normalize($this->validInput(['noreferrer' => true]))['noreferrer']);
    }

    public function test_TC_VAL_062_想定外の型は落として検証を続ける(): void
    {
        $link = Rules::normalize($this->validInput([
            'title' => ['配列'],
            'visibility' => 'targets',
            'params' => ['x', self::param('a', 'fixed', '1'), ['name' => ['x'], 'value_type' => 'fixed']],
            'targets' => 'Users:1',
        ]));

        $this->assertSame('', $link['title']);
        $this->assertCount(2, $link['params']);
        $this->assertSame(['name' => '', 'value_type' => 'fixed', 'value' => ''], $link['params'][1]);
        $this->assertSame([], $link['targets']);
        $this->assertSame([
            'title' => 'LBL_ERROR_TITLE_REQUIRED',
            'params.1.name' => 'LBL_ERROR_PARAM_NAME_REQUIRED',
            'targets' => 'LBL_ERROR_TARGETS_REQUIRED',
        ], Rules::validate($link, [], fn (): bool => true));
    }

    // ==== TS-02 公開範囲の判定 ==============================================

    /** 役割 H5（H1 ⊃ H2 ⊃ H3 ⊃ H5）で、グループ 10・20 に属するユーザー */
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
        $one = fn (string $type, string $id): array => [['type' => $type, 'id' => $id]];
        return [
            'TC-VIS-001 本人' => ['targets', $one('Users', '7'), true],
            'TC-VIS-002 他人' => ['targets', $one('Users', '8'), false],
            'TC-VIS-003 所属グループ' => ['targets', $one('Groups', '20'), true],
            'TC-VIS-004 非所属グループ' => ['targets', $one('Groups', '30'), false],
            'TC-VIS-005 同じ役割' => ['targets', $one('Roles', 'H5'), true],
            'TC-VIS-006 上位の役割（下位を含まない）' => ['targets', $one('Roles', 'H3'), false],
            'TC-VIS-007 下位の役割（下位を含まない）' => ['targets', $one('Roles', 'H6'), false],
            'TC-VIS-008 同じ役割（下位を含む）' => ['targets', $one('RoleAndSubordinates', 'H5'), true],
            'TC-VIS-009 上位の役割（下位を含む）' => ['targets', $one('RoleAndSubordinates', 'H3'), true],
            'TC-VIS-010 下位の役割（下位を含む）' => ['targets', $one('RoleAndSubordinates', 'H6'), false],
            'TC-VIS-011 別系統の役割（下位を含む）' => ['targets', $one('RoleAndSubordinates', 'H4'), false],
            'TC-VIS-012 IDの先頭だけ一致' => ['targets', $one('RoleAndSubordinates', 'H'), false],
            'TC-VIS-013 削除された役割' => ['targets', $one('Roles', 'H999'), false],
            'TC-VIS-014 想定外の種類' => ['targets', $one('Profiles', '7'), false],
            'TC-VIS-020 全員' => ['all', [], true],
            'TC-VIS-021 どれか1つに当てはまる（OR）' => ['targets', [
                ['type' => 'Users', 'id' => '99'],
                ['type' => 'Groups', 'id' => '10'],
            ], true],
            'TC-VIS-022 どれにも当てはまらない' => ['targets', [
                ['type' => 'Users', 'id' => '99'],
                ['type' => 'Roles', 'id' => 'H3'],
            ], false],
            'TC-VIS-023 対象0件' => ['targets', [], false],
            'TC-VIS-024 想定外の公開範囲' => ['nobody', $one('Users', '7'), false],
        ];
    }

    /**
     * @param list<array{type: string, id: string}> $targets
     */
    #[DataProvider('visibilityCases')]
    public function test_TC_VIS_001_024_公開範囲の判定(string $visibility, array $targets, bool $expected): void
    {
        $this->assertSame($expected, Rules::isVisible($visibility, $targets, self::USER));
    }

    public function test_TC_VIS_015_IDは数値でも文字列でも一致する(): void
    {
        $user = ['id' => '7', 'roleid' => 'H2', 'parent_role_seq' => 'H1::H2', 'groups' => ['10']];

        $this->assertTrue(Rules::isVisible('targets', [['type' => 'Groups', 'id' => '10']], $user));
        $this->assertTrue(Rules::isVisible('targets', [['type' => 'Users', 'id' => '7']], $user));
        $this->assertTrue(Rules::isVisible('targets', [['type' => 'Groups', 'id' => '20']], self::USER));
    }

    public function test_TC_VIS_016_役割の経路が空でも落ちない(): void
    {
        $user = ['id' => 1, 'roleid' => '', 'parent_role_seq' => '', 'groups' => []];

        $this->assertFalse(Rules::isVisible('targets', [['type' => 'RoleAndSubordinates', 'id' => 'H1']], $user));
        $this->assertFalse(Rules::isVisible('targets', [['type' => 'Roles', 'id' => '']], $user));
    }

    // ==== TS-03 値の解決と URL ==============================================

    public function test_TC_RES_001_007_値を解決し登録順を保つ(): void
    {
        $resolved = Rules::resolveParams([
            self::param('from', 'fixed', 'crm'),                 // TC-RES-001
            self::param('raw', 'fixed', '{user.email1}'),        // TC-RES-002
            self::param('login', 'user', 'user_name'),           // TC-RES-003
            self::param('uid', 'user', 'id'),                    // TC-RES-004
            self::param('gone', 'user', 'cf_deleted'),           // TC-RES-006
        ], ['user_name' => 'taro', 'id' => '7', 'email1' => 'taro@example.com']);

        // TC-RES-007: 登録した順に並ぶ
        $this->assertSame([
            ['name' => 'from', 'value' => 'crm'],
            ['name' => 'raw', 'value' => '{user.email1}'],
            ['name' => 'login', 'value' => 'taro'],
            ['name' => 'uid', 'value' => '7'],
            ['name' => 'gone', 'value' => ''],
        ], $resolved);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function fullNames(): array
    {
        return [
            'TC-RES-010 姓と名' => ['山田', '太郎', '山田 太郎'],
            'TC-RES-011 姓だけ' => ['山田', '', '山田'],
            'TC-RES-012 姓が空白だけ' => [' ', '太郎', '太郎'],
            'TC-RES-013 どちらも空' => ['', '', ''],
            'TC-RES-014 姓が0' => ['0', '太郎', '0 太郎'],
        ];
    }

    #[DataProvider('fullNames')]
    public function test_TC_RES_010_014_氏名(string $lastName, string $firstName, string $expected): void
    {
        $this->assertSame($expected, Rules::fullName($lastName, $firstName));
    }

    public function test_TC_RES_020_022_差し込める項目の判定(): void
    {
        // TC-RES-021: 除外の一覧
        $this->assertSame([
            'user_password', 'confirm_password', 'accesskey', 'user_hash', 'crypt_type', 'user_preferences',
            'imagename', 'signature',
        ], Rules::EXCLUDED_USER_FIELDS);
        foreach (Rules::EXCLUDED_USER_FIELDS as $fieldName) {
            $this->assertFalse(Rules::isInsertableUserField($fieldName, 1), $fieldName);
        }
        // TC-RES-022: カスタム項目でも uitype 99（パスワード）は除外する
        $this->assertFalse(Rules::isInsertableUserField('cf_secret', Rules::PASSWORD_UITYPE));
        // TC-RES-020: 通常の項目・カスタム項目は差し込める
        $this->assertTrue(Rules::isInsertableUserField('email1', 13));
        $this->assertTrue(Rules::isInsertableUserField('cf_employee_no', 1));
    }

    /**
     * @return array<string, array{string, list<array{name: string, value: string}>, string}>
     */
    public static function getUrlCases(): array
    {
        $one = fn (string $name, string $value): array => [['name' => $name, 'value' => $value]];
        return [
            'TC-RES-030 パラメータなし' => ['https://a.example.com/x?y=1#top', [], 'https://a.example.com/x?y=1#top'],
            'TC-RES-031 クエリなし' => ['https://a.example.com/x', $one('id', '7'), 'https://a.example.com/x?id=7'],
            'TC-RES-032 既存のクエリの後ろ' => [
                'https://a.example.com/x?lang=ja', $one('id', '7'), 'https://a.example.com/x?lang=ja&id=7',
            ],
            'TC-RES-033 末尾が?' => ['https://a.example.com/x?', $one('a', '1'), 'https://a.example.com/x?a=1'],
            'TC-RES-034 末尾が&' => ['https://a.example.com/x?b=2&', $one('a', '1'), 'https://a.example.com/x?b=2&a=1'],
            'TC-RES-035 フラグメント' => [
                'https://a.example.com/x?b=2#section', $one('a', '1'), 'https://a.example.com/x?b=2&a=1#section',
            ],
            'TC-RES-035 クエリなしでフラグメント' => [
                'https://a.example.com/x#s', $one('a', '1'), 'https://a.example.com/x?a=1#s',
            ],
            'TC-RES-036 同じ名前でも上書きしない' => [
                'https://a.example.com/x?a=1',
                [['name' => 'a', 'value' => '2'], ['name' => 'a', 'value' => '3']],
                'https://a.example.com/x?a=1&a=2&a=3',
            ],
            'TC-RES-037 エンコード' => [
                'https://a.example.com/',
                $one('q&x', '山田 太郎&a=b#c+d'),
                'https://a.example.com/?q%26x=%E5%B1%B1%E7%94%B0%20%E5%A4%AA%E9%83%8E%26a%3Db%23c%2Bd',
            ],
            'TC-RES-038 空の値' => ['https://a.example.com/', $one('a', ''), 'https://a.example.com/?a='],
        ];
    }

    /**
     * @param list<array{name: string, value: string}> $params
     */
    #[DataProvider('getUrlCases')]
    public function test_TC_RES_030_038_GETのURL(string $url, array $params, string $expected): void
    {
        $this->assertSame($expected, Rules::buildGetUrl($url, $params));
    }

    // ==== TS-04 POST の送り先と CSP（form-action） ==========================

    /**
     * @return array<string, array{string, list<string>, bool}>
     */
    public static function formActionCases(): array
    {
        $url = 'https://kintai.example.com/login';
        return [
            'TC-OPN-050 設定なし' => [$url, [], false],
            'TC-OPN-051 オリジンが一致' => [$url, ['https://kintai.example.com'], true],
            'TC-OPN-051 末尾にスラッシュ' => [$url, ['https://kintai.example.com/'], true],
            'TC-OPN-051 大文字のホスト' => [$url, ['https://KINTAI.example.com'], true],
            'TC-OPN-052 別のホスト' => [$url, ['https://other.example.com'], false],
            'TC-OPN-052 ホストの前方一致では許さない' => ['https://kintai.example.com.evil.test/', ['https://kintai.example.com'], false],
            'TC-OPN-053 ワイルドカードのサブドメイン' => [$url, ['https://*.example.com'], true],
            'TC-OPN-053 ワイルドカードは親ドメイン自体を含まない' => ['https://example.com/', ['https://*.example.com'], false],
            'TC-OPN-054 スキームだけ（https:）' => [$url, ['https:'], true],
            'TC-OPN-054 http: は https を許す' => [$url, ['http:'], true],
            'TC-OPN-054 https: は http を許さない' => ['http://kintai.example.com/', ['https:'], false],
            'TC-OPN-055 http のソースは https へ格上げして許す' => [$url, ['http://kintai.example.com'], true],
            'TC-OPN-055 https のソースは http を許さない' => ['http://kintai.example.com/', ['https://kintai.example.com'], false],
            'TC-OPN-056 スキームなしのホスト' => [$url, ['kintai.example.com'], true],
            'TC-OPN-057 ポートが一致' => ['https://kintai.example.com:8443/x', ['https://kintai.example.com:8443'], true],
            'TC-OPN-057 ポートが違う' => ['https://kintai.example.com:8443/x', ['https://kintai.example.com'], false],
            'TC-OPN-057 ポートのワイルドカード' => ['https://kintai.example.com:8443/x', ['https://kintai.example.com:*'], true],
            'TC-OPN-058 パスが / で終わらなければ完全一致だけ' => [$url, ['https://kintai.example.com/log'], false],
            'TC-OPN-058 パスが / で終わればその配下を許す' => ['https://kintai.example.com/app/login', ['https://kintai.example.com/app/'], true],
            'TC-OPN-058 パスの完全一致' => [$url, ['https://kintai.example.com/login'], true],
            'TC-OPN-059 *' => [$url, ['*'], true],
            'TC-OPN-059 複数のうちどれかに一致' => [$url, ['https://a.example.com', 'https://kintai.example.com'], true],
            "TC-OPN-059 'self' は外部を許さない" => [$url, ["'self'"], false],
            'TC-OPN-059 不正な URL' => ['not a url', ['*'], false],
        ];
    }

    /**
     * @param list<string> $sources
     */
    #[DataProvider('formActionCases')]
    public function test_TC_OPN_050_059_POSTの送り先がCSPで許可されているか(string $url, array $sources, bool $expected): void
    {
        $this->assertSame($expected, Rules::isFormActionAllowed($url, $sources));
    }

    // ==== TS-08 ウィジェット（リンクのまとまり）の見出し =====================

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function widgetTitles(): array
    {
        return [
            'TC-TTL-001 空' => ['', 'LBL_ERROR_WIDGET_TITLE_REQUIRED'],
            'TC-TTL-001 空白だけ' => ['   ', 'LBL_ERROR_WIDGET_TITLE_REQUIRED'],
            'TC-TTL-002 30文字' => [str_repeat('あ', 30), null],
            'TC-TTL-002 サロゲートペア30文字' => [str_repeat('𠮷', 30), null],
            'TC-TTL-004 31文字' => [str_repeat('あ', 31), 'LBL_ERROR_WIDGET_TITLE_TOO_LONG'],
            'TC-TTL-005 <' => ['社内<システム', 'LBL_ERROR_WIDGET_TITLE_CHARS'],
            'TC-TTL-005 >' => ['a>b', 'LBL_ERROR_WIDGET_TITLE_CHARS'],
            'TC-TTL-005 &' => ['R&D', 'LBL_ERROR_WIDGET_TITLE_CHARS'],
            'TC-TTL-005 "' => ['a"b', 'LBL_ERROR_WIDGET_TITLE_CHARS'],
            "TC-TTL-005 '" => ["a'b", 'LBL_ERROR_WIDGET_TITLE_CHARS'],
            'TC-TTL-006 使える記号' => ['社内（システム）・リンク集!', null],
        ];
    }

    #[DataProvider('widgetTitles')]
    public function test_TC_TTL_001_006_ウィジェットの見出しの検証(string $title, ?string $expected): void
    {
        $this->assertSame($expected, Rules::validateWidgetTitle($title));
    }

    // ==== TS-05 TC-API-041 役割の並び順 ====================================

    public function test_TC_API_041_役割は子孫が親の直後にまとまる(): void
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

        // ID の先頭が同じ兄弟（H2 と H20）があっても、H2 の子孫が H20 の後ろへ回って親から離れない
        $this->assertSame(
            ['H1', 'H1::H20', 'H1::H20::H21', 'H1::H2', 'H1::H2::H3', 'H1::H2::H5'],
            array_column($sorted, 'name')
        );
        // 経路は返さない
        $this->assertSame(['id', 'name', 'depth'], array_keys($sorted[0]));
    }
}

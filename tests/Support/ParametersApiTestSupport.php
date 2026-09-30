<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

// PHPUnit テスト環境用: Settings/Parameters の API（modules/Settings/Parameters/apis/）を
// DB なしで動かすための依存ロードとスタブ。
// テスト bootstrap は Vtiger_Loader を起動しないため、必要なクラスを明示ロードする。

$root = dirname(__DIR__, 2);

// テストからスタブの挙動を制御するための状態
require_once __DIR__ . '/ParametersApiTestState.php';

// Vtiger_Api_Controller と ApiException 系
require_once $root . '/includes/runtime/Controller.php';

// Vtiger_Request のコンストラクタが Vtiger_Functions::validateRequestParameters() を呼ぶため必須
require_once $root . '/vtlib/Vtiger/Functions.php';
require_once $root . '/includes/http/Request.php';

if (!function_exists('vglobal')) {
    require_once $root . '/includes/runtime/Globals.php';
}

// vtranslate() はフレームワーク全体ロード時に定義される。テストではキーをそのまま返す。
if (!function_exists('vtranslate')) {
    function vtranslate(string $key, string $module = ''): string
    {
        return $key;
    }
}

// csrf_check() は csrf-magic ライブラリで定義される。
// $GLOBALS['__test_csrf_pass'] で通過/失敗を切り替えられるようにする。
if (!function_exists('csrf_check')) {
    function csrf_check(bool $fatal = true): bool
    {
        return (bool)($GLOBALS['__test_csrf_pass'] ?? true);
    }
}

/**
 * Users_Record_Model のスタブ。
 * API の checkPermission() が使う管理者判定だけを差し替える。
 */
if (!class_exists('Users_Record_Model')) {
    class Users_Record_Model
    {
        public static function getCurrentUserModel(): self
        {
            return new self();
        }

        public function isAdminUser(): bool
        {
            return ParametersApiTestState::$isAdmin;
        }
    }
}

/**
 * Settings_Parameters_Record_Model のスタブ。
 * DB へ触れずに、API が「どの値を保存しようとしたか」を検証できるようにする。
 */
if (!class_exists('Settings_Parameters_Record_Model')) {
    class Settings_Parameters_Record_Model
    {
        /** @var array<string, mixed> */
        private array $data = [];

        public static function getInstanceById(int|string $id): self
        {
            $instance = new self();
            $record = ParametersApiTestState::$records[ParametersApiTestState::toId($id)] ?? [];
            foreach ($record as $field => $value) {
                $instance->set((string)$field, $value);
            }

            return $instance;
        }

        public function getId(): mixed
        {
            return $this->data['id'] ?? null;
        }

        public function getType(): string
        {
            $type = $this->data['type'] ?? 'string';

            return is_string($type) && $type !== '' ? $type : 'string';
        }

        public function getSecret(): int
        {
            return ParametersApiTestState::toId($this->data['secret'] ?? 0);
        }

        public function getKey(): mixed
        {
            return $this->data['key'] ?? null;
        }

        public function getValue(): mixed
        {
            return $this->data['value'] ?? null;
        }

        public function getDescription(): mixed
        {
            return $this->data['description'] ?? null;
        }

        public function get(string $key): mixed
        {
            return $this->data[$key] ?? null;
        }

        public function set(string $key, mixed $value): self
        {
            $this->data[$key] = $value;

            return $this;
        }

        public function save(): void
        {
            ParametersApiTestState::$saved[ParametersApiTestState::toId($this->get('id'))] = $this->data;
        }
    }
}

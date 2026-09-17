# F-RevoCRM Migration System

F-RevoCRMのデータベーススキーマやデータの変更を管理するためのマイグレーションシステムです。

## 概要

このマイグレーションシステムは以下の機能を提供します：

- データベースの変更を段階的に管理
- 重複実行制御（同じマイグレーションの重複実行を防止）
- 実行状態の追跡（`com_vtiger_migrations`テーブルに記録）
- トランザクション制御によるデータ整合性の保証
  - ただし `ALTER TABLE` などの DDL は MySQL が暗黙にコミットするため巻き戻らない。
    DDL を含むマイグレーションは、失敗しても途中まで適用された状態になることを前提に書くこと
    （失敗時は `com_vtiger_migrations` に記録されないため、直してから実行し直せる）

## ディレクトリ構成

```
setup/migration/
├── FRMigrationClass.php       # マイグレーションの基底クラス
├── generate_migration.php     # マイグレーション雛形生成スクリプト
├── run_migration.php         # マイグレーション実行スクリプト
├── README.md                 # このドキュメント
├── usage_examples.sh         # 使用例スクリプト
└── scripts/                  # 生成されたマイグレーションスクリプト格納ディレクトリ
    ├── 20250825123456_add_custom_field.php
    └── 20250825134567_create_custom_table.php
```

## 使用方法

### 1. 新しいマイグレーションの作成

```bash
php setup/migration/generate_migration.php migration_name
```

例：
```bash
php setup/migration/generate_migration.php add_custom_field_to_accounts
```

これにより、`setup/migration/scripts/`ディレクトリに以下のようなファイルが生成されます：
`setup/migration/scripts/20250825123456_add_custom_field_to_accounts.php`

### 2. マイグレーションの編集

生成されたファイルの `process()` メソッドに実際の変更処理を記述します：

```php
public function process() {
    // テーブルに新しいフィールドを追加
    $sql = "ALTER TABLE vtiger_account ADD COLUMN custom_field VARCHAR(255) DEFAULT NULL";
    $this->query($sql);
    
    $this->log("Custom field added to accounts table");
}
```

### 3. マイグレーションの実行

#### 特定のマイグレーションを実行
```bash
php setup/migration/run_migration.php setup/migration/scripts/20250825123456_add_custom_field_to_accounts.php
```

#### すべての未実行マイグレーションを実行
```bash
php setup/migration/run_migration.php --all
```

## utf8mb4 への変換について（#77）

`setup/migration/scripts/20260917072813_convert_all_tables_to_utf8mb4.php` は、
データベース全体を utf8mb4 / utf8mb4_general_ci に揃える。

- 実行前にデータベースのバックアップを取ること。`ALTER TABLE` は暗黙のコミットを起こすため巻き戻せない
- 大きなテーブルを含む場合はメタデータロックがかかるため、メンテナンス時間帯に実行すること
- MariaDB と MySQL 5.7.7 未満では変換を見送る（インストールやアップグレードを止めないため）。
  その場合は手動で `ALTER DATABASE` / `ALTER TABLE ... CONVERT TO` を実行する
- latin1 など utf8 系でない文字セットの列を持つテーブルは変換しない。
  UTF-8 のバイト列がそのまま入っている場合、変換すると文字化けして戻せないため。
  **対象のテーブルは実行時のログに一覧で出る。2 回目以降は「実行済み」としてスキップされ一覧が出ないので、
  初回の実行ログを保存し、必要なテーブルは内容を確認したうえで手動で変換すること**

## マイグレーションクラスの構造

### 必須メソッド

- `process()` - マイグレーション処理を記述（必須）

### 利用可能なヘルパーメソッド

- `$this->log($message)` - ログメッセージの出力

## マイグレーションファイルの命名規則

- フォーマット: `scripts/YYYYMMDDHHmmss_migration_name.php`
- 例: `scripts/20250825123456_add_custom_field_to_accounts.php`
- タイムスタンプにより実行順序が管理されます
- すべてのマイグレーションスクリプトは `scripts/` ディレクトリ内に格納されます

## 重複実行制御

- 各マイグレーションの実行状態は `com_vtiger_migrations` テーブルに記録されます
- 既に実行済みのマイグレーションは自動的にスキップされます
- テーブルが存在しない場合は自動的に作成されます

## データベーステーブル: com_vtiger_migrations

```sql
CREATE TABLE com_vtiger_migrations (
    migration_name VARCHAR(255) PRIMARY KEY,
    executed_at DATETIME NOT NULL,
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
```

## マイグレーションの例

### 1. 新しいテーブルの作成

```php
public function process() {
    $sql = "CREATE TABLE vtiger_custom_module (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $this->query($sql);
    $this->log("Created vtiger_custom_module table");
}
```

### 2. データの更新

```php
public function process() {
    // 既存データの更新
    $sql = "UPDATE vtiger_account SET priority = 'High' WHERE annual_revenue > 1000000";
    $this->query($sql);
    
    // 新しいデータの挿入
    $sql = "INSERT INTO vtiger_custom_settings (setting_name, setting_value) VALUES (?, ?)";
    $this->query($sql, array('feature_enabled', '1'));
    
    $this->log("Updated account priorities and added custom settings");
}
```

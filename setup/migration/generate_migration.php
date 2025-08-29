<?php
/**
 * F-RevoCRM マイグレーション生成ツール
 * 
 * 使用方法: php setup/migration/generate_migration.php マイグレーション名
 * 例: php setup/migration/generate_migration.php add_new_field_to_accounts
 */

if ($argc < 2) {
    echo "使用方法: php setup/migration/generate_migration.php マイグレーション名\n";
    echo "例: php setup/migration/generate_migration.php add_new_field_to_accounts\n";
    exit(1);
}

$migrationName = $argv[1];

// マイグレーション名の検証（英数字とアンダースコアのみ許可）
if (!preg_match('/^[a-zA-Z0-9_]+$/', $migrationName)) {
    echo "エラー: マイグレーション名は英数字とアンダースコアのみ使用できます。\n";
    exit(1);
}

// このスクリプトファイルのパスを基準にして適切なパスを取得
$scriptDir = dirname(__FILE__);
$rootDir = dirname(dirname($scriptDir));

// タイムスタンプを生成
$timestamp = date('YmdHis');

// ファイル名を生成
$filename = $timestamp . '_' . $migrationName . '.php';

// クラス名を生成（アンダースコアで区切られた各単語の最初の文字を大文字にする）
// PHPクラス名は数字から始めることができないため、'Migration'プレフィックスを付与
$className = 'Migration' . $timestamp . '_' . str_replace('_', '', ucwords($migrationName, '_'));

// マイグレーションテンプレートを作成
$template = <<<PHP
<?php
/**
 * マイグレーション: {$migrationName}
 * 生成日時: {$timestamp}
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class {$className} extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        // 例: 新しいテーブルを作成
        // \$sql = "CREATE TABLE vtiger_custom_table (
        //     id INT AUTO_INCREMENT PRIMARY KEY,
        //     name VARCHAR(255) NOT NULL,
        //     created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        // ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        // \$this->query(\$sql);
        
        // 例: データを挿入
        // \$sql = "INSERT INTO vtiger_custom_table (name) VALUES (?)";
        // \$this->query(\$sql, array('サンプルデータ'));
        
        \$this->log("マイグレーション {$migrationName} が正常に完了しました");
    }
}
PHP;

// scriptsディレクトリのパスを取得（スクリプトファイルの場所を基準）
$scriptsDir = $scriptDir . '/scripts';

// scriptsディレクトリが存在しない場合は作成
if (!is_dir($scriptsDir)) {
    if (!mkdir($scriptsDir, 0755, true)) {
        echo "エラー: scriptsディレクトリを作成できませんでした: {$scriptsDir}\n";
        exit(1);
    }
}

$filePath = $scriptsDir . '/' . $filename;

// ファイルに書き込み
if (file_put_contents($filePath, $template) !== false) {
    echo "マイグレーションファイルが正常に作成されました: {$filename}\n";
    echo "クラス名: {$className}\n";
    echo "パス: {$filePath}\n";
    echo "\n";
    echo "このマイグレーションを実行するには:\n";
    echo "php setup/migration/run_migration.php migration/scripts/{$filename}\n";
} else {
    echo "エラー: マイグレーションファイルを作成できませんでした。\n";
    exit(1);
}

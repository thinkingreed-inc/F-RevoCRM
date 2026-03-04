<?php
/**
 * F-RevoCRM マイグレーション基底クラス
 *
 * マイグレーションの実行状態は com_vtiger_migrations テーブルで管理
 */

// データベース
require_once 'include/database/PearDatabase.php';

// vtlib関連（モジュール・フィールド操作に必須）
include_once 'vtlib/Vtiger/Menu.php';
include_once 'vtlib/Vtiger/Module.php';

// ユーティリティ
include_once 'modules/PickList/DependentPickListUtils.php';
include_once 'modules/ModTracker/ModTracker.php';
include_once 'include/utils/CommonUtils.php';

// オートローダー・ランタイム
include_once 'includes/Loader.php';
include_once 'includes/runtime/BaseModel.php';
include_once 'includes/runtime/Globals.php';
include_once 'includes/runtime/LanguageHandler.php';

// F-RevoCRMセットアップユーティリティ
require_once 'setup/utils/FRFieldSetting.php';
require_once 'setup/utils/FRFilterSetting.php';

// モデル関連
include_once 'modules/Vtiger/models/Module.php';
include_once 'modules/Vtiger/models/Record.php';

abstract class FRMigrationClass {
    
    protected $db;
    protected $migrationName;
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        global $adb;
        $this->db = PearDatabase::getInstance();
        
        // クラス名からマイグレーション名を取得
        $this->migrationName = get_class($this);
        
        // マイグレーションテーブルが存在することを確認
        $this->ensureMigrationsTableExists();
    }
    
    /**
     * 子クラスで実装される抽象メソッド
     * 実際のマイグレーション処理を記述する
     */
    abstract public function process();
    
    /**
     * マイグレーションを実行する
     * マイグレーションが既に実行済みかどうかをチェックする
     * 
     * @return boolean 実行に成功した場合はtrue、既に実行済みの場合はfalse
     * @throws Exception マイグレーションが失敗した場合
     */
    public function execute() {
        try {
            // マイグレーションが既に実行済みかチェック
            if ($this->isExecuted()) {
                echo "マイグレーション {$this->migrationName} は既に実行済みです。スキップします。\n";
                return false;
            }
            
            echo "マイグレーションを実行中: {$this->migrationName}\n";
            
            // トランザクション開始
            $this->db->database->StartTrans();

            // 前のマイグレーションでキャッシュされたUsers_Record_Modelをクリア
            if (class_exists('Users_Record_Model')) {
                Users_Record_Model::$currentUserModels = array();
            }

            // マイグレーション実行
            $this->process();

            // トランザクション内でエラーが発生していないかチェック
            if (!$this->db->database->_transOK) {
                throw new Exception(
                    "マイグレーション処理中にSQLエラーが発生しました。\n" .
                    "  ※エラー詳細はログファイル (logs/vtigercrm_YYYYMMDD.log) を確認してください。"
                );
            }

            // 実行済みとしてマーク
            $this->markAsExecuted();

            // トランザクションコミット
            $commitResult = $this->db->database->CompleteTrans();

            if (!$commitResult) {
                throw new Exception("トランザクションのコミットに失敗しました。");
            }

            echo "マイグレーション {$this->migrationName} が正常に実行されました。\n";
            return true;
            
        } catch (Exception $e) {
            // トランザクションロールバック
            $this->db->database->FailTrans();
            $this->db->database->CompleteTrans();
            
            echo "マイグレーション {$this->migrationName} が失敗しました: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    /**
     * マイグレーションが実行済みかどうかをチェックする
     * 
     * @return boolean
     */
    protected function isExecuted() {
        $query = "SELECT migration_name FROM com_vtiger_migrations WHERE migration_name = ?";
        $result = $this->db->pquery($query, array($this->migrationName));
        
        return ($this->db->num_rows($result) > 0);
    }
    
    /**
     * マイグレーションを実行済みとしてマークする
     */
    protected function markAsExecuted() {
        $query = "INSERT INTO com_vtiger_migrations (migration_name, executed_at) VALUES (?, NOW())";
        $this->db->pquery($query, array($this->migrationName));
    }
    
    /**
     * マイグレーション追跡テーブルが存在することを確認する
     */
    protected function ensureMigrationsTableExists() {
        $tableExists = $this->checkTableExists('com_vtiger_migrations');
        
        // テーブルがなければ作る
        if (!$tableExists) {
            $this->createMigrationsTable();
        }
    }
    
    /**
     * テーブルが存在するかどうかをチェックする
     * 
     * @param string $tableName テーブル名
     * @return boolean
     */
    protected function checkTableExists($tableName) {
        $query = "SHOW TABLES LIKE ?";
        $result = $this->db->pquery($query, array($tableName));
        
        return ($this->db->num_rows($result) > 0);
    }
    
    /**
     * マイグレーション追跡テーブルを作成する
     */
    protected function createMigrationsTable() {
        $createTableSQL = "
            CREATE TABLE com_vtiger_migrations (
                migration_name VARCHAR(255) PRIMARY KEY,
                executed_at DATETIME NOT NULL,
                INDEX idx_executed_at (executed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ";
        
        $this->db->pquery($createTableSQL, array());
        echo "com_vtiger_migrations テーブルを作成しました。\n";
    }
    
    /**
     * マイグレーションメッセージをログに出力するヘルパーメソッド
     * 
     * @param string $message メッセージ
     */
    protected function log($message) {
        echo "[" . date('Y-m-d H:i:s') . "] {$this->migrationName}: {$message}\n";
    }
}

<?php

/**
 * マイグレーション実行時の Vtiger_Cache クリア
 *
 * Issue #1429: マイグレーションを一括実行すると 1 プロセスで複数スクリプトが動くため、
 * 先に動いたスクリプトが Vtiger_Cache に載せたインスタンスが後のスクリプトへ引き継がれる。
 * そのままでは Vtiger_Module::getInstance() などが想定外の状態のインスタンスを返しうるので、
 * スクリプトごとにキャッシュを空にする。
 *
 * Vtiger_Cache は「キー名 = プロパティ名」で値を保持しており、まとめて初期化する手段が
 * 用意されていない。コア (includes/) を書き換えずに済ませるため、ここでリフレクション経由で
 * 初期値へ戻す。
 */
class FRMigrationCache
{
    /**
     * 初期化対象から外す Vtiger_Cache の静的プロパティ。
     * selfInstance はシングルトン、cacheEnable は運用側の設定値であり、
     * どちらもキャッシュされたデータではないため触らない。
     *
     * @var string[]
     */
    private const PRESERVED_PROPERTIES = ['selfInstance', 'cacheEnable'];

    /**
     * Vtiger_Cache が保持するキャッシュをすべて破棄する。
     *
     * Vtiger_Cache が未ロードならキャッシュも存在しないため何もしない
     * (オートロードを誘発しないよう class_exists の第 2 引数は false)。
     */
    public static function clear(): void
    {
        if (!class_exists('Vtiger_Cache', false)) {
            return;
        }

        $cacheClass = new ReflectionClass('Vtiger_Cache');

        self::clearConnector($cacheClass);
        self::clearStaticProperties($cacheClass);
    }

    /**
     * Vtiger_Cache::set() 系が使うコネクタ上の値を破棄する。
     *
     * 既定の Vtiger_Cache_Connector_Memory は値をオブジェクトの動的プロパティとして持ち、
     * flush() が何もしない実装のため、プロパティを直接落とす。
     * config_override.php で別のコネクタに差し替えている場合は、そのコネクタの
     * flush() に任せる。
     *
     * @param ReflectionClass<object> $cacheClass
     */
    private static function clearConnector(ReflectionClass $cacheClass): void
    {
        $instanceProperty = $cacheClass->getProperty('selfInstance');
        $cacheInstance = $instanceProperty->getValue();

        // 一度も getInstance() されていなければコネクタ自体が存在しない
        if (!is_object($cacheInstance)) {
            return;
        }

        $connectorProperty = $cacheClass->getProperty('connector');
        $connector = $connectorProperty->getValue($cacheInstance);

        if (!is_object($connector)) {
            return;
        }

        $connection = self::getConnection($connector);

        if ($connection instanceof Vtiger_Cache_Connector_Memory) {
            foreach (array_keys(get_object_vars($connection)) as $key) {
                unset($connection->$key);
            }
            return;
        }

        if (method_exists($connector, 'flush')) {
            $connector->flush();
        }
    }

    /**
     * コネクタが内部で使っている接続オブジェクトを取り出す。
     *
     * @param object $connector
     * @return object|null 取り出せない構造のコネクタなら null
     */
    private static function getConnection(object $connector): ?object
    {
        $connectorClass = new ReflectionObject($connector);

        if (!$connectorClass->hasProperty('connection')) {
            return null;
        }

        $connectionProperty = $connectorClass->getProperty('connection');
        $connection = $connectionProperty->getValue($connector);

        return is_object($connection) ? $connection : null;
    }

    /**
     * Vtiger_Cache が直接持つ静的プロパティ (モジュール名・項目インスタンス等) を
     * クラス定義時の初期値へ戻す。
     *
     * 初期値はクラス定義から取るため、コア側にキャッシュ用プロパティが増えても追従する。
     *
     * @param ReflectionClass<object> $cacheClass
     */
    private static function clearStaticProperties(ReflectionClass $cacheClass): void
    {
        $defaults = $cacheClass->getDefaultProperties();

        foreach ($cacheClass->getProperties(ReflectionProperty::IS_STATIC) as $property) {
            $name = $property->getName();

            if (in_array($name, self::PRESERVED_PROPERTIES, true)) {
                continue;
            }

            $property->setValue(null, $defaults[$name] ?? null);
        }
    }
}

<?php

declare(strict_types=1);

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
 *
 * 対象は Vtiger_Cache だけで、Vtiger_Functions や VTCacheUtils が持つ静的キャッシュは残る。
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
     *
     * キャッシュを消せないままマイグレーションを止めても得られるものが無いので、
     * コア側の作りが変わってプロパティを辿れなくなった場合は黙って何もしない。
     *
     * @param ReflectionClass<object> $cacheClass
     */
    private static function clearConnector(ReflectionClass $cacheClass): void
    {
        if (!$cacheClass->hasProperty('selfInstance') || !$cacheClass->hasProperty('connector')) {
            return;
        }

        $cacheInstance = $cacheClass->getProperty('selfInstance')->getValue();

        // 一度も getInstance() されていなければコネクタ自体が存在しない
        if (!is_object($cacheInstance)) {
            return;
        }

        $connector = $cacheClass->getProperty('connector')->getValue($cacheInstance);

        if (!is_object($connector)) {
            return;
        }

        $connectorClass = new ReflectionObject($connector);

        if ($connectorClass->hasProperty('connection')) {
            $connection = $connectorClass->getProperty('connection')->getValue($connector);

            if ($connection instanceof Vtiger_Cache_Connector_Memory) {
                foreach (array_keys(get_object_vars($connection)) as $key) {
                    unset($connection->$key);
                }
                return;
            }

            // 接続が未設定なら保持している値も無い
            if (!is_object($connection)) {
                return;
            }
        }

        // config_override.php で差し替えた独自コネクタ。値の持ち方が分からないため flush() に任せる。
        // 既定の Vtiger_Cache_Connector::flush() を引き継いでいる場合は最大 1 秒のビジーループが
        // マイグレーション 1 本ごとに走るため、実行時間が本数分だけ延びる。
        if (method_exists($connector, 'flush')) {
            $connector->flush();
        }
    }

    /**
     * Vtiger_Cache が直接持つ静的プロパティ (モジュール名・項目インスタンス等) を
     * クラス定義時の初期値へ戻す。
     *
     * 初期値はクラス定義から取るため、コア側にキャッシュ用プロパティが増えても追従する。
     * 初期値を持たない型付きプロパティは戻す値が決まらないため対象外とする。
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

            if (!array_key_exists($name, $defaults)) {
                continue;
            }

            $property->setValue(null, $defaults[$name]);
        }
    }
}

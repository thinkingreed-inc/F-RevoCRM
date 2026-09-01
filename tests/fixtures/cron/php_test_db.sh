#!/bin/sh
#
# FR_CronDispatcher::spawn() から起動される子プロセスを、テスト用DB へ接続させるための
# ラッパー。テストでは $cron_php_binary にこのスクリプトを指定する。
#
# spawn() が組み立てるコマンドは
#   <binary> -f <root>/vtigercron.php -- --child --service=<名前>
# の形なので、先頭の「-f <script> --」を落として、テスト用DB を指す入口
# （run_vtigercron.php）へ読み替えて実行する。
#
# 実際に使う PHP は環境変数 FREVOCRM_TEST_PHP で受け取る（PHP_BINARY が PATH 上の
# php と異なる場合があるため）。

set -e

TESTS_DIR=$(cd "$(dirname "$0")/../.." && pwd)

# 先頭の "-f <script>" と、それに続く "--" を取り除く
if [ "$1" = "-f" ]; then
    shift 2
fi
if [ "$1" = "--" ]; then
    shift
fi

exec "${FREVOCRM_TEST_PHP:-php}" -d xdebug.mode=off \
    -f "$TESTS_DIR/fixtures/cron/run_vtigercron.php" -- "$@"

/**
 * xlsx の解析を別スレッドで行うワーカー
 *
 * LuckyExcel.transformExcelToLucky() はメインスレッドを同期的に占有するため、
 * 大きなファイルではタブ全体が固まり、読み込み中のまま操作できなくなる。
 * （setTimeout による打ち切りもスレッドが空かないため発火しない）
 * 解析をここへ移すことで、画面は応答したまま待てるようになる。
 *
 * LuckyExcel は window / document が無くても解析できる
 *   - XML は内部のパーサで読む（DOMParser を使わない）
 *   - UMD は window が無ければ self に載る
 *
 * 行数の打ち切りもここで行う。luckysheet の描画はメインスレッドで動くため、
 * 数十万セルを渡すと解析後に描画で固まる。渡す前に減らす。
 * （返信時の複製コストも下がる）
 *
 * やりとり:
 *   受信 { buffer: ArrayBuffer, maxRows: number }  maxRows は 0 以下で無制限
 *   返信 { ok: true, json: <LuckyExcel の出力>, truncated: bool, maxRows: number }
 *        | { ok: false, error: 'キー' }
 */

/** 解析結果を返す前に落ちた場合でも、必ず1回だけ返信する */
var replied = false;

function reply(message) {
  if (replied) return;
  replied = true;
  self.postMessage(message);
}

try {
  importScripts('luckyexcel.umd.js');
} catch (e) {
  // 読み込めない場合は呼び出し側でメインスレッド解析に切り替える
  reply({ ok: false, error: 'LIB_LOAD_FAILED' });
}

// LuckyExcel は解析失敗を内部で握りつぶしコールバックを呼ばないことがあるため、
// ワーカー内の非同期エラーも拾って返す
self.addEventListener('error', function () {
  reply({ ok: false, error: 'PARSE_FAILED' });
});
self.addEventListener('unhandledrejection', function () {
  reply({ ok: false, error: 'PARSE_FAILED' });
});

self.onmessage = function (event) {
  replied = false;
  var buffer = event.data && event.data.buffer;
  if (!buffer) {
    reply({ ok: false, error: 'PARSE_FAILED' });
    return;
  }
  if (typeof self.LuckyExcel === 'undefined') {
    reply({ ok: false, error: 'LIB_LOAD_FAILED' });
    return;
  }

  var maxRows = parseInt(event.data.maxRows, 10);
  if (isNaN(maxRows) || maxRows < 0) maxRows = 0;

  try {
    self.LuckyExcel.transformExcelToLucky(buffer, function (exportJson) {
      if (!exportJson || !exportJson.sheets || exportJson.sheets.length === 0) {
        reply({ ok: false, error: 'PARSE_FAILED' });
        return;
      }
      var truncated = truncateRows(exportJson.sheets, maxRows);
      reply({ ok: true, json: exportJson, truncated: truncated, maxRows: maxRows });
    });
  } catch (e) {
    reply({ ok: false, error: 'PARSE_FAILED' });
  }
};

/**
 * 各シートを先頭 maxRows 行までに切る
 *
 * @param {Array} sheets LuckyExcel の出力の sheets
 * @param {number} maxRows 0 以下は無制限
 * @return {boolean} 1シートでも切ったら true
 */
function truncateRows(sheets, maxRows) {
  if (maxRows <= 0) return false;
  var truncated = false;
  sheets.forEach(function (sheet) {
    // celldata は { r: 行, c: 列, v: 値 } の配列
    if (Object.prototype.toString.call(sheet.celldata) === '[object Array]') {
      var kept = sheet.celldata.filter(function (cell) {
        return cell && cell.r < maxRows;
      });
      if (kept.length !== sheet.celldata.length) {
        truncated = true;
        sheet.celldata = kept;
      }
    }
    // 数式の計算順（読み取り専用でも整合させておく）
    if (Object.prototype.toString.call(sheet.calcChain) === '[object Array]') {
      sheet.calcChain = sheet.calcChain.filter(function (item) {
        return item && item.r < maxRows;
      });
    }
  });
  return truncated;
}

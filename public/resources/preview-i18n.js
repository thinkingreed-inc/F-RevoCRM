/**
 * プレビュー用ビューア（iframe）の多言語化ヘルパー
 *
 * PDF / Excel / PowerPoint のプレビューは同梱ライブラリを iframe で開くため、
 * 親画面（React）の翻訳を直接使えない。このヘルパーが GetTranslations API から
 * 翻訳を取得し、ラッパー側は翻訳キーだけを書けばよいようにする。
 *
 * ライブラリ本体には手を入れず、F-RevoCRM 側のラッパー（viewer.html）から読み込む。
 *
 * 使い方:
 *   <script src="../../../resources/preview-i18n.js"></script>
 *   PreviewI18n.apply('#loading', 'LBL_PREVIEW_LOADING', '読み込み中...');
 *   PreviewI18n.text('LBL_PREVIEW_FETCH_FAILED', 'ファイルの取得に失敗しました')
 *     .then(function (message) { ... });
 */
(function (global) {
  "use strict";

  /** 翻訳を取得するモジュール（プレビュー関連のラベルはここにある） */
  var MODULE = "Documents";

  /**
   * サイトのベースURL（末尾スラッシュ付き）を求める
   *
   * このスクリプトは <base>/libraries/... 配下のラッパーから読み込まれるため、
   * パスの "/libraries/" より前をベースとして扱う。
   */
  function getSiteBase() {
    var path = global.location.pathname;
    var index = path.indexOf("/libraries/");
    if (index !== -1) {
      return path.substring(0, index + 1);
    }
    // 想定外の配置でも動くようにする
    return path.substring(0, path.lastIndexOf("/") + 1);
  }

  var translations = null;
  var loading = null;

  /** 翻訳をまとめて取得する（1回だけ） */
  function load() {
    if (loading) return loading;
    loading = new Promise(function (resolve) {
      var url =
        getSiteBase() +
        "index.php?module=" +
        encodeURIComponent(MODULE) +
        "&api=GetTranslations";
      var xhr = new XMLHttpRequest();
      xhr.open("GET", url, true);
      xhr.withCredentials = true;
      xhr.setRequestHeader("Accept", "application/json");
      xhr.onload = function () {
        try {
          var data = JSON.parse(xhr.responseText);
          var result = data.result || data;
          var maps = result.translations || {};
          var merged = {};
          // Vtiger共通 → モジュール の順に重ねる（モジュール側を優先）
          Object.keys(maps).forEach(function (name) {
            if (name.indexOf(MODULE) !== 0) {
              Object.assign(merged, maps[name]);
            }
          });
          Object.keys(maps).forEach(function (name) {
            if (name.indexOf(MODULE) === 0) {
              Object.assign(merged, maps[name]);
            }
          });
          translations = merged;
        } catch (e) {
          translations = {};
        }
        resolve(translations);
      };
      xhr.onerror = function () {
        // 取得できなくてもフォールバック文言で動かす
        translations = {};
        resolve(translations);
      };
      xhr.send();
    });
    return loading;
  }

  /** %s を引数で置換する */
  function format(template, args) {
    var index = 0;
    return template.replace(/%s/g, function () {
      return index < args.length ? String(args[index++]) : "%s";
    });
  }

  /**
   * 翻訳文言を取得する
   *
   * @param {string} key 翻訳キー
   * @param {string} fallback 翻訳が無い場合の文言
   * @param {...(string|number)} args %s に代入する値
   * @returns {Promise<string>}
   */
  function text(key, fallback) {
    var args = Array.prototype.slice.call(arguments, 2);
    return load().then(function (map) {
      var template = (map && map[key]) || fallback || key;
      return args.length ? format(template, args) : template;
    });
  }

  /**
   * 要素のテキストを翻訳文言に差し替える
   *
   * 翻訳の取得を待たずに fallback を先に表示し、取得後に置き換える
   * （プレビューの表示を翻訳の取得で遅らせないため）。
   *
   * @param {string} selector
   * @param {string} key
   * @param {string} fallback
   */
  function apply(selector, key, fallback) {
    var element = document.querySelector(selector);
    if (!element) return;
    if (fallback) element.textContent = fallback;
    text(key, fallback).then(function (message) {
      element.textContent = message;
    });
  }

  global.PreviewI18n = {
    load: load,
    text: text,
    apply: apply,
  };
})(window);

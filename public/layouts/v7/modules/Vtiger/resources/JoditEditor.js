/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
/**
 * Vtiger_Jodit_Js
 *   Jodit 4.x ラッパークラス。旧CKEditor4からの移行に伴い作成。
 *   - クラス名・ファイル名・公開メソッド名は互換のため維持
 *   - 内部実装は Jodit API を呼び出す
 *   - PDFTemplates はフルHTML文書（<!DOCTYPE>/<html>/<head>/<body>）を保存するため、
 *     退避・復元層で外皮タグのロスレス往復を保証する（Jodit div モードは body 内容のみを扱うため）
 */
jQuery.Class("Vtiger_Jodit_Js", {

    /**
     * 静的レジストリ（CKEDITOR.instances[name] の代替）
     * key   : textarea の id
     * value : JoditWrapper オブジェクト
     */
    instances: {},

    /**
     * 指定idの JoditWrapper を取得する。
     * @param {string} id textarea の id
     * @returns {object|undefined} 未登録なら undefined
     */
    getInstance: function (id) {
        return Vtiger_Jodit_Js.instances[id];
    },

    /**
     * 登録済み全instancesのtextareaを同期する（submit前呼び出し必須）。
     *   - Task G で form submit/SaveAjax/Modal閉じ時から呼び出される共通経路
     *   - 登録が解除されているインスタンスは自動スキップ
     */
    syncAllInstances: function () {
        var instances = Vtiger_Jodit_Js.instances;
        for (var id in instances) {
            if (!instances.hasOwnProperty(id)) {
                continue;
            }
            var wrapper = instances[id];
            if (wrapper && typeof wrapper.updateElement === 'function') {
                try {
                    wrapper.updateElement();
                } catch (e) {
                    // 個別instance失敗で他instancesを止めない
                    if (typeof console !== 'undefined' && console.warn) {
                        console.warn('Vtiger_Jodit_Js.syncAllInstances: ' + id + ' failed', e);
                    }
                }
            }
        }
    }

}, {

    element: null,
    jodit: null,
    /**
     * 退避した外皮情報（PDFTemplates フルHTML文書モード時のみ非null）。
     *   { doctype, htmlAttrs, headInner, bodyAttrs }
     *   Jodit div モードは body innerHTML のみを扱うため、
     *   外皮タグを退避して保存時に復元する。
     */
    stashedParts: null,

    /**
     * textarea要素を設定する。
     */
    setElement: function (element) {
        this.element = element;
        return this;
    },

    /**
     * textarea要素を取得する。
     */
    getElement: function () {
        return this.element;
    },

    /**
     * textarea要素のid属性を取得する。
     */
    getElementId: function () {
        var element = this.getElement();
        return element.attr('id');
    },

    /**
     * 登録済み JoditWrapper を取得する。
     */
    getJoditInstanceFromName: function () {
        var elementName = this.getElementId();
        return Vtiger_Jodit_Js.getInstance(elementName);
    },

    /**
     * エディタ内容をプレーンテキストで取得する。
     */
    getPlainText: function () {
        var wrapper = this.getJoditInstanceFromName();
        if (!wrapper) {
            return '';
        }
        return wrapper.getPlainText();
    },

    /**
     * Jodit エディタをロードする。
     *   - 既存インスタンスがあれば破棄してから再生成する
     *   - PDFTemplates templatecontent の場合、フルHTML文書の外皮タグを退避・復元する
     *     （Jodit は div モードで body innerHTML のみを扱うため）
     *
     * @param {jQuery} element       textarea jQueryオブジェクト
     * @param {object} [customConfig] Jodit 追加設定（jQuery.extend でマージ）
     */
    loadJoditEditor: function (element, customConfig) {
        this.setElement(element);
        var elementName = this.getElementId();

        // 既存インスタンスがあれば破棄
        var existing = Vtiger_Jodit_Js.getInstance(elementName);
        if (existing) {
            existing.destroy();
        }

        // フルHTML文書退避・復元が必要かを判定する
        //   PDFTemplates の templatecontent はフルHTML文書（DOCTYPE/html/head/body 付き）を保存するため、
        //   外皮タグを退避して body innerHTML のみを Jodit に渡し、保存時に復元する。
        //   iframeモード（joditConfig.iframe）は廃止。div モードで統一することで
        //   「iframeの高さ = コンテンツ高のみ → workplace の空白がクリック不能」という問題を解消する。
        var moduleName = (typeof app !== 'undefined' && app.getModuleName) ? app.getModuleName() : '';
        var isPDFTemplates = (moduleName === 'PDFTemplates')
            && elementName
            && elementName.indexOf('templatecontent') !== -1;
        var useExtractRestore = isPDFTemplates;

        // ツールバーボタン設定（ホワイトリスト方式）
        // 再追加候補: superscript, subscript, classSpan, align, font, lineHeight,
        //             file, video, link, hr, symbols, spellcheck, cut, copy, paste,
        //             selectall, copyformat, undo/redo, find, fullsize, preview,
        //             print, about, dots, ai-assistant/ai-commands, speechRecognize
        var TOOLBAR_BUTTONS = [
            'paragraph', 'fontsize', '|',
            'bold', 'italic', 'underline', 'strikethrough', 'eraser', '|',
            'brush', '|',
            'ul', 'ol', 'outdent', 'indent', '|',
            'image', 'table', '|',
            'source'
        ];

        // Jodit 標準設定（PoC で確認済み）
        var userLang = (typeof app !== 'undefined' && app.getUserLanguage) ? app.getUserLanguage() : 'ja';
        var joditLang = userLang ? userLang.split('_')[0] : 'ja';
        var joditConfig = {
            language: joditLang,
            enter: 'p',
            minHeight: false, // Joditデフォルト(200)を無効化。高さはapplyHeight()でworkplaceに直接設定
            maxHeight: false, // JoditのmaxHeight自動算出を無効化。高さはapplyHeight()でworkplaceに直接設定
            statusbar: true,
            hidePoweredByJodit: false,
            defaultActionOnPaste: 'insert_as_html',
            askBeforePasteHTML: false,
            askBeforePasteFromWord: false,
            processPasteHTML: false,
            cleanHTML: {
                timeout: 0,
                removeEmptyElements: false,
                fillEmptyParagraph: false,
                replaceNBSP: false,
                removeOnError: false,
                useIframeResizer: false,
                allowTags: false
            },
            observer: { timeout: 100 },
            uploader: { insertImageAsBase64URI: true },
            buttons: TOOLBAR_BUTTONS,
            // source を全画面サイズで常時表示するため responsive 設定にも明示する
            // （デフォルトの buttonsMD/SM/XS には sourceグループが含まれないため dots に隠れる）
            buttonsMD: TOOLBAR_BUTTONS,
            buttonsSM: TOOLBAR_BUTTONS,
            buttonsXS: TOOLBAR_BUTTONS
        };

        // 呼出し側からの追加設定をマージ（末尾で上書き可）
        // buttons/buttonsMD/SM/XS は jQuery deep merge を避けて直接上書きする。
        // jQuery.extend(true,...) は配列をオブジェクト({0:...,1:...})として処理するため
        // buttons 配列が正しく設定されなくなる。
        // ※ シャローコピーで処理し呼び出し元オブジェクトへの破壊的変更を防ぐ。
        if (typeof customConfig !== 'undefined' && customConfig !== null) {
            var mergeConfig = jQuery.extend({}, customConfig);
            // customConfig.buttons が配列なら全4レスポンシブプロパティに同一配列を適用
            if (Array.isArray(mergeConfig.buttons)) {
                joditConfig.buttons   = mergeConfig.buttons;
                joditConfig.buttonsMD = mergeConfig.buttons;
                joditConfig.buttonsSM = mergeConfig.buttons;
                joditConfig.buttonsXS = mergeConfig.buttons;
            }
            // 全4プロパティを mergeConfig から除去してディープマージの対象外にする
            ['buttons', 'buttonsMD', 'buttonsSM', 'buttonsXS', 'minHeight', 'maxHeight'].forEach(function(k) {
                delete mergeConfig[k];
            });
            jQuery.extend(true, joditConfig, mergeConfig);
        }

        // QuickCreate 判定・workplace 高さ変数の定義
        // PC 通常時のみ customConfig.minHeight/maxHeight で上書き可能
        var isQuickCreate = element.closest('form[name="QuickCreate"]').length > 0;
        var isModalEditor = element.closest('.myModal').length > 0;
        var fieldName = element.attr('name') || '';
        var elementId = element.attr('id') || '';
        var isDocumentsNotecontent = (fieldName === 'notecontent' && elementId.indexOf('Documents') !== -1);
        var isDocumentsQuickCreate = isDocumentsNotecontent && (isQuickCreate || isModalEditor);
        var mobileMinH   = '40vh';
        var mobileMaxH   = '40vh';
        var qcMinH       = '150px';
        var qcMaxH       = '200px';
        var pcNormalMinH = ((customConfig && customConfig.minHeight) || 150) + 'px';
        var pcNormalMaxH = ((customConfig && customConfig.maxHeight) || 450) + 'px';
        var mobileDocumentMinH   = '40vh';
        var mobileDocumentMaxH   = '40vh';
        var qcMobileDocumentMinH = '25vh';
        var qcMobileDocumentMaxH = '25vh';
        var qcDocumentMinH       = '200px';
        var qcDocumentMaxH       = '200px';
        var pcNormalDocumentMinH = '150px';
        var pcNormalDocumentMaxH = '450px';

        // 退避・復元層（PDFTemplates フルHTML文書モード時のみ有効化）
        //   入力HTMLから <!DOCTYPE>/<html>/<head>/<body> を退避し、
        //   body innerHTML だけを Jodit に渡す（Jodit div モードは body 内容のみを扱うため）。
        var originalHtml = element.val() || '';
        var initialBody = originalHtml;
        this.stashedParts = null;
        if (useExtractRestore && originalHtml.length > 0) {
            var extracted = Vtiger_Jodit_Js._extractParts(originalHtml);
            if (extracted && extracted.parts) {
                this.stashedParts = extracted.parts;
                initialBody = extracted.bodyInner;
                // textarea の初期値を body innerHTML だけに差し替え
                element.val(initialBody);
            }
        }

        // Jodit wysiwyg最低高さを保証するスタイルを注入（1回のみ）
        if (!document.getElementById('jodit-custom-styles')) {
            var joditStyle = document.createElement('style');
            joditStyle.id = 'jodit-custom-styles';
            joditStyle.textContent = [
                '.jodit-container:not(.jodit_inline) { height: auto !important; min-height: 0 !important; }',
                // jodit-wysiwyg_iframe ルールはiframeモード廃止に伴い削除（div モードでは .jodit-wysiwyg が対象）
                '@media (max-width: 767px) {',
                '  .jodit-container:not(.jodit_inline) { width: 100% !important; max-width: 100% !important; min-width: 0 !important; }',
                '  .fieldBlockContainer > table.table:not(#lineItemResult) { table-layout: fixed !important; width: 100% !important; }',
                '  .createDocumentContent > table.table { table-layout: fixed !important; width: 100% !important; }',
                '  .createDocumentContent > table.table td { width: 100% !important; max-width: 100% !important; }',
                '  .jodit-toolbar-mobile-scroll { flex-wrap: nowrap !important; overflow-x: auto !important; -webkit-overflow-scrolling: touch; scrollbar-width: none; }',
                '  .jodit-toolbar-mobile-scroll > .jodit-ui-group { flex-wrap: nowrap !important; }',
                '  .jodit-toolbar-mobile-scroll::-webkit-scrollbar { display: none; }',
                '}',
                '.jodit-toolbar-wrapper { position: relative; min-width: 0; overflow: hidden; }',
                '.jodit-toolbar-fade { position: absolute; top: 0; bottom: 0; width: 8px; pointer-events: none; z-index: 1; }',
                '.jodit-toolbar-fade--left { left: 0; background: linear-gradient(to right, rgba(0,0,0,0.25), rgba(0,0,0,0.08) 40%, transparent); }',
                '.jodit-toolbar-fade--right { right: 0; background: linear-gradient(to left, rgba(0,0,0,0.25), rgba(0,0,0,0.08) 40%, transparent); }',
                '.jodit-status-bar a.jodit-status-bar-link { pointer-events: none; cursor: default; }',
                '.jodit-workplace { padding-bottom: 20px; }',
                '.jodit-wysiwyg table tr td, .jodit-wysiwyg table tr th { border: 1px solid #dadada !important; }'
            ].join('\n');
            document.head.appendChild(joditStyle);
        }

        // Jodit インスタンス生成（PoC で同期完了確認済み）
        var self = this;
        var editor = new Jodit(element.get(0), joditConfig);
        if (initialBody !== '' && Vtiger_Jodit_Js._isEmptyHtml(editor.value)) {
            editor.value = initialBody;
        }
        element.hide();
        this.jodit = editor;

        // モバイルツールバー横スクロール: グラデーション表示制御
        // new Jodit() 直後に同期実行する（afterInit は構築中に発火するため後から on 登録では届かない）
        (function (ed) {
            if (!ed.container) { return; }

            // Jodit 4.x: ボタン行は .jodit-ui-group_line_true（コンテナ直下）
            var toolbarEl = ed.container.querySelector('.jodit-toolbar-editor-collection .jodit-ui-group_line_true');
            if (!toolbarEl) {
                // フォールバック: コンテナセレクタが変わった場合に備えて
                toolbarEl = ed.container.querySelector('.jodit-ui-group_line_true');
            }
            if (!toolbarEl) { return; }

            // 冪等性ガード（再実行時の二重ラップ防止）
            if (toolbarEl.classList.contains('jodit-toolbar-mobile-scroll')) { return; }

            // CSS はクラス付与で適用（DOM構造に依存しない）
            toolbarEl.classList.add('jodit-toolbar-mobile-scroll');

            var wrapperEl = document.createElement('div');
            wrapperEl.className = 'jodit-toolbar-wrapper';
            toolbarEl.parentNode.insertBefore(wrapperEl, toolbarEl);
            wrapperEl.appendChild(toolbarEl);

            var fadeLeft = document.createElement('div');
            fadeLeft.className = 'jodit-toolbar-fade jodit-toolbar-fade--left';
            fadeLeft.style.display = 'none';
            var fadeRight = document.createElement('div');
            fadeRight.className = 'jodit-toolbar-fade jodit-toolbar-fade--right';
            fadeRight.style.display = 'none';
            wrapperEl.appendChild(fadeLeft);
            wrapperEl.appendChild(fadeRight);

            function isMobile() {
                return window.innerWidth < 768;
            }

            function handleScroll() {
                var sl = toolbarEl.scrollLeft;
                var max = toolbarEl.scrollWidth - toolbarEl.clientWidth;
                fadeLeft.style.display = sl > 0 ? 'block' : 'none';
                // サブピクセルレンダリング誤差を吸収するため 1px のマージンを設ける
                fadeRight.style.display = sl < max - 1 ? 'block' : 'none';
            }

            var resizeTimer;
            function onResize() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    if (isMobile()) {
                        handleScroll();
                    } else {
                        fadeLeft.style.display = 'none';
                        fadeRight.style.display = 'none';
                    }
                }, 150);
            }

            if (isMobile()) {
                handleScroll();
            }
            toolbarEl.addEventListener('scroll', handleScroll);
            window.addEventListener('resize', onResize);

            ed.events.on('beforeDestruct', function () {
                toolbarEl.removeEventListener('scroll', handleScroll);
                window.removeEventListener('resize', onResize);
            });
        })(editor);

        // workplace 高さ設定・リサイズ時の再適用
        // jodit-workplace に直接 min/max-height を設定する（Jodit の toolbar 高さ減算を回避）
        // overflow は Jodit 標準CSSで hidden のため、ここでスクロール可能に上書きする
        // ※ Jodit は container の minHeight を元に workplace の minHeight を算出して上書きするため、
        //   setTimeout(0) で同期処理完了後に applyHeight() を実行して確実に上書きする
        (function (ed, isQC) {
            function applyHeight() {
                var wp = ed.workplace;
                if (!wp) { return; }
                var editorArea = ed.editor || ed.container.querySelector('.jodit-wysiwyg');
                var source = ed.container.querySelector('.jodit-source');
                var minH;
                var maxH;

                // Jodit は workplace の height を CSS変数(--jd-jodit-workplace-height)で管理するため、
                // max-height のみ設定してもその height が max-height より大きい場合に
                // jodit-workplace 内のスクロールバーが表示されない。
                // height: auto でCSS変数による管理を無効化し、min/max-height による制御に切り替える。
                wp.style.height = 'auto';
                wp.style.overflowY = 'auto';
                wp.style.overflowX = 'auto';
                if (isDocumentsQuickCreate && window.innerWidth < 768) {
                    minH = qcMobileDocumentMinH;
                    maxH = qcMobileDocumentMaxH;
                } else if (isDocumentsNotecontent && window.innerWidth < 768) {
                    minH = mobileDocumentMinH;
                    maxH = mobileDocumentMaxH;
                } else if (isDocumentsQuickCreate) {
                    minH = qcDocumentMinH;
                    maxH = qcDocumentMaxH;
                } else if (isDocumentsNotecontent) {
                    minH = pcNormalDocumentMinH;
                    maxH = pcNormalDocumentMaxH;
                } else if (window.innerWidth < 768) {
                    minH = mobileMinH;
                    maxH = mobileMaxH;
                } else if (isQC) {
                    minH = qcMinH;
                    maxH = qcMaxH;
                } else {
                    minH = pcNormalMinH;
                    maxH = pcNormalMaxH;
                }

                wp.style.minHeight = minH;
                wp.style.maxHeight = maxH;

                // editorArea（div モードでは .jodit-wysiwyg contenteditable div）を
                // workplace 全体に広げることで、空白行をクリックしてもカーソルが入るようになる。
                // かつて iframe モード時はこの設定を省略していたが（body.offsetHeight 計測と干渉するため）、
                // iframe モード廃止により全エディタで無条件に適用する。
                if (editorArea) {
                    editorArea.style.minHeight = '100%';
                    editorArea.style.height = '100%';
                    editorArea.style.maxHeight = '100%';
                    editorArea.style.overflowY = 'auto';
                    editorArea.style.overflowX = 'auto';
                }

                if (source) {
                    source.style.minHeight = '100%';
                    source.style.height = '100%';
                    source.style.maxHeight = '100%';
                }
            }
            // setTimeout(0) でイベントループの次サイクルに実行し、
            // Jodit の同期的な minHeight 上書き処理が完了した後に確実に適用する
            setTimeout(applyHeight, 0);

            // タイミング競合への保険としてafterInitフック追加
            editor.events.on('afterInit', function(){
                applyHeight();
                // iframe モード廃止により初期iframe高さ確定処理は不要
            });

            var resizeTimer;
            function onResize() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(applyHeight, 150);
            }
            window.addEventListener('resize', onResize);
            ed.events.on('beforeDestruct', function () {
                window.removeEventListener('resize', onResize);
            });
        })(editor, isQuickCreate);

        // クロージャ参照用に退避データを保持
        var stashedPartsRef = this.stashedParts;
        var initialData = stashedPartsRef
            ? Vtiger_Jodit_Js._restoreParts(initialBody, stashedPartsRef)
            : initialBody;

        // JoditWrapper 定義（getInstance() が返すオブジェクト）
        var wrapper = {
            jodit: editor,
            elementName: elementName,
            stashedParts: stashedPartsRef,
            lastKnownEditorValue: initialBody,
            lastKnownData: initialData,
            hasUserEdited: false,

            /**
             * 現在のエディタ内容を取得する。
             *   - 退避情報がある場合は外皮を復元して full document HTML を返す
             *   - headless-chrome 向け注入 <style id="jodit-injected-font"> は除去
             */
            getData: function () {
                var body = this.jodit.value;
                if (Vtiger_Jodit_Js._isEmptyHtml(body)
                    && !this.hasUserEdited
                    && !Vtiger_Jodit_Js._isEmptyHtml(this.lastKnownData)) {
                    return this.lastKnownData;
                }
                if (this.stashedParts) {
                    body = Vtiger_Jodit_Js._restoreParts(body, this.stashedParts);
                }
                // PDFTemplates headless-chrome 向けに注入した表示専用styleは
                // DB保存すべきでないため getData 時点で除去
                body = body.replace(
                    /<style[^>]*id=["']jodit-injected-font["'][^>]*>[\s\S]*?<\/style>/gi,
                    ''
                );
                if (!Vtiger_Jodit_Js._isEmptyHtml(body) || this.hasUserEdited) {
                    this.lastKnownEditorValue = this.jodit.value;
                    this.lastKnownData = body;
                }
                return body;
            },

            /**
             * エディタ内容を差し替える。
             *   - PDFTemplates フルHTML文書モード時は入力 HTML から再度外皮を退避し直し、
             *     body innerHTML だけを Jodit に渡す
             */
            setData: function (html) {
                html = (typeof html === 'string') ? html : '';
                if (useExtractRestore) {
                    var extracted = Vtiger_Jodit_Js._extractParts(html);
                    if (extracted && extracted.parts) {
                        this.stashedParts = extracted.parts;
                        this.jodit.value = extracted.bodyInner;
                        this.lastKnownEditorValue = extracted.bodyInner;
                        this.lastKnownData = Vtiger_Jodit_Js._restoreParts(extracted.bodyInner, extracted.parts);
                        this.hasUserEdited = false;
                        return;
                    }
                }
                this.jodit.value = html;
                this.lastKnownEditorValue = html;
                this.lastKnownData = html;
                this.hasUserEdited = false;
            },

            /**
             * エディタイベントを購読する（後方互換のためメソッド名を維持）。
             */
            on: function (event, handler) {
                this.jodit.events.on(event, handler);
            },

            /**
             * 現在のカーソル位置にHTMLを差し込む（差し込みタグ挿入用）。
             */
            insertHtml: function (html) {
                this.hasUserEdited = true;
                this.jodit.selection.insertHTML(html);
            },

            restoreLastKnownContentIfNeeded: function () {
                if (!this.hasUserEdited
                    && Vtiger_Jodit_Js._isEmptyHtml(this.jodit.value)
                    && !Vtiger_Jodit_Js._isEmptyHtml(this.lastKnownEditorValue)) {
                    this.jodit.value = this.lastKnownEditorValue;
                }
            },

            /**
             * textarea へ値を同期する（submit前呼出し必須）。
             *   退避・復元層を通した完全HTML（<!DOCTYPE>/<html>/<head>/<body> 含む）を
             *   textarea に書き込む。Jodit 内部の synchronizeValues() のみでは body innerHTML
             *   しか渡らず PDFTemplates 保存時に外皮が消失するため、getData() の結果を
             *   明示的に element.val() へ反映する。
             */
            updateElement: function () {
                // getData() が退避・復元層を通した完全HTML（DOCTYPE/html/head/body含む）を返す。
                // synchronizeValues() はbody innerHTMLのみを書き戻すため削除。
                if (self.element && self.element.length > 0) {
                    self.element.val(this.getData());
                }
            },

            /**
             * Jodit インスタンスを破棄する。
             *   順序: synchronizeValues() → destruct() → registry delete
             */
            destroy: function () {
                try {
                    this.updateElement();
                } catch (e) {
                    // destroy 続行（同期失敗でもリソース解放は行う）
                }
                try {
                    if (this.jodit && typeof this.jodit.destruct === 'function') {
                        this.jodit.destruct();
                    }
                } catch (e) {
                    // 破棄失敗でも registry からは削除する
                }
                delete Vtiger_Jodit_Js.instances[this.elementName];
            },

            /**
             * エディタ内容のプレーンテキストを取得する。
             */
            getPlainText: function () {
                // div モードでは ed.editor が contenteditable div そのものであるため innerText で取得する。
                // iframeモード廃止前は editorDocument.body.innerText を優先していたが、
                // div モードでは editorDocument が main document を指すため誤ったページ全体のテキストを返す。
                if (this.jodit && this.jodit.editor
                    && typeof this.jodit.editor.innerText === 'string') {
                    return this.jodit.editor.innerText;
                }
                if (this.jodit && typeof this.jodit.getEditorText === 'function') {
                    return this.jodit.getEditorText();
                }
                return '';
            },

            /**
             * 後方互換API: editor.document.getBody().getText() 形式の呼び出しを維持する。
             *   getPlainText() と同等の結果を返す。
             */
            document: {
                getBody: function () {
                    return {
                        getText: function () {
                            return wrapper.getPlainText();
                        }
                    };
                }
            }
        };

        var markUserEdited = function () {
            wrapper.hasUserEdited = true;
        };
        editor.events.on('keydown', markUserEdited);
        editor.events.on('paste', markUserEdited);
        editor.events.on('cut', markUserEdited);
        editor.events.on('drop', markUserEdited);
        editor.events.on('change', function () {
            if (!Vtiger_Jodit_Js._isEmptyHtml(editor.value) || wrapper.hasUserEdited) {
                var bodyVal = editor.value;
                // getData() を呼ぶとlastKnownEditorValue/lastKnownDataへの二重書き込みと
                // 将来的な無限ループのリスクがあるため、_restoreParts を直接呼ぶ
                var fullHtml = Vtiger_Jodit_Js._restoreParts(bodyVal, wrapper.stashedParts);
                fullHtml = fullHtml.replace(
                    /<style[^>]*id=["']jodit-injected-font["'][^>]*>[\s\S]*?<\/style>/gi,
                    ''
                );
                wrapper.lastKnownEditorValue = bodyVal;
                wrapper.lastKnownData = fullHtml;
            }
        });
        var restoreLastKnownContent = function () {
            // Joditはfocusイベント後に内部処理でeditor.valueをクリアすることがあるため
            // 2段階setTimeoutで確実に復元する（0ms: 同期クリア対応, 50ms: 非同期クリア対応）
            setTimeout(function () {
                wrapper.restoreLastKnownContentIfNeeded();
            }, 0);
            setTimeout(function () {
                wrapper.restoreLastKnownContentIfNeeded();
            }, 50);
        };
        editor.events.on('focus', restoreLastKnownContent);

        // registry へ同期登録（new Jodit() 直後、afterInit 待機不要）
        Vtiger_Jodit_Js.instances[elementName] = wrapper;

        return this;
    },

    /**
     * textarea へ指定HTMLをロードする。
     */
    loadContentsInJoditEditor: function (contents) {
        var wrapper = this.getJoditInstanceFromName();
        if (wrapper) {
            wrapper.setData(contents);
        }
    },

    /**
     * エディタインスタンスを破棄する。
     */
    removeJoditEditor: function () {
        if (this.getElement()) {
            var wrapper = this.getJoditInstanceFromName();
            if (wrapper) {
                wrapper.destroy();
            }
        }
    }

});

/*-------------------------------------------------------------------------------------
 * 退避・復元層ユーティリティ（静的）
 *
 *   PDFTemplates はフルHTML文書（<!DOCTYPE>/<html>/<head>/<body> 付き）を保存するが、
 *   Jodit div モードは body innerHTML のみを扱うため、外皮をラッパーで退避・復元する。
 *   iframeモードを廃止した現在もこの層は引き続き必要。
 *
 *   実装方針:
 *     - DOMParser による安全なパース（正規表現ではない）
 *     - DOCTYPE のみ DOMParser では消えるため入力文字列冒頭から別途抽出
 *-------------------------------------------------------------------------------------*/

/**
 * 入力HTMLから外皮4点（DOCTYPE / html属性 / head全体 / body属性）を抽出する。
 *
 * @param {string} html 入力 full document HTML
 * @returns {{parts: {doctype: ?string, htmlAttrs: ?string, headInner: ?string, bodyAttrs: ?string}, bodyInner: string}}
 */
Vtiger_Jodit_Js._extractParts = function (html) {
    var parts = {
        doctype: null,
        htmlAttrs: null,
        headInner: null,
        bodyAttrs: null
    };
    var bodyInner = html;

    if (typeof html !== 'string' || html.length === 0) {
        return { parts: parts, bodyInner: '' };
    }

    // DOCTYPE は DOMParser で破棄されるため冒頭から文字列抽出
    var doctypeMatch = html.match(/^\s*<!DOCTYPE[^>]*>/i);
    if (doctypeMatch) {
        parts.doctype = doctypeMatch[0].replace(/^\s+/, '');
    }

    // DOMParser で安全にパース
    try {
        var doc = new DOMParser().parseFromString(html, 'text/html');

        if (doc && doc.documentElement
            && doc.documentElement.tagName
            && doc.documentElement.tagName.toLowerCase() === 'html') {

            // html 開始タグの属性を再構築
            var htmlAttrs = '';
            var htmlAttrList = doc.documentElement.attributes;
            if (htmlAttrList && htmlAttrList.length > 0) {
                for (var i = 0; i < htmlAttrList.length; i++) {
                    var a = htmlAttrList[i];
                    htmlAttrs += ' ' + a.name + '="' + String(a.value).replace(/"/g, '&quot;') + '"';
                }
            }
            parts.htmlAttrs = htmlAttrs;
        }

        if (doc && doc.head) {
            parts.headInner = doc.head.innerHTML;
        }

        if (doc && doc.body) {
            var bodyAttrs = '';
            var bodyAttrList = doc.body.attributes;
            if (bodyAttrList && bodyAttrList.length > 0) {
                for (var j = 0; j < bodyAttrList.length; j++) {
                    var b = bodyAttrList[j];
                    bodyAttrs += ' ' + b.name + '="' + String(b.value).replace(/"/g, '&quot;') + '"';
                }
            }
            parts.bodyAttrs = bodyAttrs;
            bodyInner = doc.body.innerHTML;
        }
    } catch (e) {
        // DOMParser 失敗時は退避せず原文を通過させる
        if (typeof console !== 'undefined' && console.warn) {
            console.warn('Vtiger_Jodit_Js._extractParts: DOMParser failed, passthrough', e);
        }
        return {
            parts: { doctype: null, htmlAttrs: null, headInner: null, bodyAttrs: null },
            bodyInner: html
        };
    }

    // <html>/<head>/<body> いずれも入力側に存在しない完全な body 断片の場合、
    // 退避対象なしとして null parts で返す（復元層を通さず原文通過させる）
    var hasOuter = /<html[\s>]/i.test(html)
        || /<head[\s>]/i.test(html)
        || /<body[\s>]/i.test(html)
        || parts.doctype !== null;
    if (!hasOuter) {
        return {
            parts: { doctype: null, htmlAttrs: null, headInner: null, bodyAttrs: null },
            bodyInner: html
        };
    }

    return { parts: parts, bodyInner: bodyInner };
};

/**
 * body innerHTML と退避した外皮情報を再結合して full document HTML を返す。
 *
 * @param {string} bodyInner 編集後の body 内HTML
 * @param {object} parts     _extractParts が返した parts オブジェクト
 * @returns {string}
 */
Vtiger_Jodit_Js._restoreParts = function (bodyInner, parts) {
    if (!parts) {
        return bodyInner;
    }
    // 退避要素が全て null の場合は原文通過
    if (parts.doctype === null
        && parts.htmlAttrs === null
        && parts.headInner === null
        && parts.bodyAttrs === null) {
        return bodyInner;
    }

    var result = '';
    if (parts.doctype) {
        result += parts.doctype + '\n';
    }
    result += '<html' + (parts.htmlAttrs || '') + '>\n';
    result += '<head>' + (parts.headInner || '') + '</head>\n';
    result += '<body' + (parts.bodyAttrs || '') + '>\n';
    result += bodyInner;
    result += '\n</body>\n</html>';
    return result;
};

/**
 * Jodit が空エディタとして返す代表的なHTMLを空として扱う。
 *
 * @param {?string} html
 * @returns {boolean}
 */
Vtiger_Jodit_Js._isEmptyHtml = function (html) {
    if (typeof html !== 'string') {
        return true;
    }

    var normalized = html
        .replace(/<style[^>]*id=["']jodit-injected-font["'][^>]*>[\s\S]*?<\/style>/gi, '')
        .replace(/<!--[\s\S]*?-->/g, '')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\u00a0/g, ' ')
        .replace(/\s+/g, '')
        .toLowerCase();

    return normalized === ''
        || normalized === '<br>'
        || normalized === '<br/>'
        || normalized === '<p></p>'
        || normalized === '<p><br></p>'
        || normalized === '<p><br/></p>'
        || normalized === '<div></div>'
        || normalized === '<div><br></div>';
};

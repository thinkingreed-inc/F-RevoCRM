<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Vtiger_PDF_TCPDF extends TCPDF {

	protected $FontFamily;

	/** システム変数 PDF_DEFAULT_FONT が使えないときのフォント */
	const FALLBACK_FONT = 'ume-tgo4';

	/** getDefaultFont() で決めたフォント名（同じリクエスト内で使い回す） */
	private static $defaultFont = null;

	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8') {
		parent::__construct($orientation, $unit, $format, $unicode, $encoding);
		$font = self::getDefaultFont();
		$this->SetFont($font,'',10);
		$this->setFontFamily($font);
	}

	/**
	 * PDF の既定フォントを返す
	 * システム変数 PDF_DEFAULT_FONT の値を使い、未設定・空・libraries/tcpdf/fonts/ にフォントがない場合は ume-tgo4 を返す
	 * SetFont() のたびに呼ばれるため、決めたフォント名はリクエスト内で使い回す
	 * （システム変数の照会、フォントの確認、警告ログの出力を 1 回にする）
	 * @return string
	 */
	public static function getDefaultFont() {
		if (self::$defaultFont === null) {
			self::$defaultFont = self::resolveFont(static::fetchDefaultFontParameter());
		}
		return self::$defaultFont;
	}

	/**
	 * getDefaultFont() が使い回しているフォント名を破棄する（テスト用）
	 */
	public static function clearDefaultFontCache() {
		self::$defaultFont = null;
	}

	/**
	 * システム変数 PDF_DEFAULT_FONT の値を取得する
	 * @return string|null
	 */
	protected static function fetchDefaultFontParameter() {
		if (!class_exists('Settings_Parameters_Record_Model')) {
			return null;
		}
		return Settings_Parameters_Record_Model::getParameterValue('PDF_DEFAULT_FONT', self::FALLBACK_FONT);
	}

	/**
	 * システム変数の値から使用するフォント名を決める
	 * 空・フォントがない場合は ume-tgo4 を返す
	 * @param string|null $font
	 * @return string
	 */
	public static function resolveFont($font) {
		// TCPDF はフォント名を小文字で扱う
		$font = strtolower(trim((string)$font));
		if ($font === '') {
			return self::FALLBACK_FONT;
		}
		if (!self::isAvailableFont($font)) {
			global $log;
			if ($log) {
				// 値は DB を直接書き換えられた場合など任意の文字列になり得るため、ログの行を崩さないよう制御文字を除く
				$logFont = preg_replace('/[\x00-\x1f\x7f]/', '', $font);
				$log->warn("PDF_DEFAULT_FONT のフォント '$logFont' が見つからないため " . self::FALLBACK_FONT . " を使用します");
			}
			return self::FALLBACK_FONT;
		}
		return $font;
	}

	/**
	 * TCPDF のフォント定義ファイルを置くディレクトリ（末尾に / を付けて返す）
	 * @return string
	 */
	public static function getFontDirectory() {
		$fontPath = defined('K_PATH_FONTS') ? K_PATH_FONTS : dirname(__FILE__) . '/../../../libraries/tcpdf/fonts/';
		return rtrim($fontPath, '/') . '/';
	}

	/**
	 * PDF_DEFAULT_FONT に指定できるフォントか（libraries/tcpdf/fonts/<フォント名>.php がフォント定義か）を返す
	 * @param string $font
	 * @param string|null $fontDir フォントのディレクトリ（省略時は getFontDirectory()）
	 * @return boolean
	 */
	public static function isAvailableFont($font, $fontDir = null) {
		// パスの組み立てに使うため、フォント名に使える文字だけを許可する
		if (!is_string($font) || !preg_match('/^[a-z0-9_\-]+$/', $font)) {
			return false;
		}
		$fontDir = $fontDir === null ? self::getFontDirectory() : rtrim($fontDir, '/') . '/';
		return self::isFontDefinitionFile($fontDir . $font . '.php');
	}

	/**
	 * PDF_DEFAULT_FONT に指定できるフォント名の一覧を返す
	 * 太字・斜体用のファイル（<フォント名>b / i / bi）は、元のフォントがあれば一覧に含めない
	 * （TCPDF が太字・斜体のときに自動で使うため、単独で選ぶものではない）
	 * @param string|null $fontDir フォントのディレクトリ（省略時は getFontDirectory()）
	 * @return string[]
	 */
	public static function getAvailableFonts($fontDir = null) {
		$fontDir = $fontDir === null ? self::getFontDirectory() : rtrim($fontDir, '/') . '/';
		$fonts = array();
		foreach ((array)glob($fontDir . '*.php') as $file) {
			$font = basename($file, '.php');
			if (self::isAvailableFont($font, $fontDir)) {
				$fonts[$font] = true;
			}
		}
		$result = array();
		foreach (array_keys($fonts) as $font) {
			if (preg_match('/^(.+?)(bi|b|i)$/', $font, $matches) && isset($fonts[$matches[1]])) {
				continue;
			}
			$result[] = $font;
		}
		sort($result);
		return $result;
	}

	/**
	 * TCPDF のフォント定義ファイルか（$type の定義があるか）を返す
	 * 同じディレクトリには文字コードの対応表（uni2cid_*.php）など、フォントではない PHP ファイルもあるため区別する。
	 * ファイルを include せず、先頭部分の文字列だけで判定する。
	 * @param string $path
	 * @return boolean
	 */
	private static function isFontDefinitionFile($path) {
		if (!is_file($path) || !is_readable($path)) {
			return false;
		}
		$head = file_get_contents($path, false, null, 0, 4096);
		return is_string($head) && preg_match('/^\$type\s*=/m', $head) === 1;
	}

	function getFontSize() {
		return $this->FontSizePt;
	}

	function setFontFamily($family) {
		$this->FontFamily = $family;
	}

	function GetStringHeight($sa,$w,$reseth = false, $autopadding = true, $cellpadding = NULL, $border = 0) {
		if(empty($sa)) return 0;
		
		$sa = str_replace("\r","",$sa);
		// remove the last newline
		if (substr($sa,-1) == "\n")
		$sa = substr($sa,0,-1);

		$blocks = explode("\n",$sa);
		$wmax = $w - (2 * $this->cMargin);

		$lines = 0;
		$spacesize = $this->GetCharWidth(32);
		foreach ($blocks as $block) {
			if (!empty($block)) {
				$words = explode(" ",$block);

				$cw = 0;
				for ($i = 0;$i < php7_count($words);$i++) {
					if ($i != 0) $cw += $spacesize;

					$wordwidth = $this->GetStringWidth($words[$i]);
					$cw += $wordwidth;

					if ($cw > $wmax) { // linebreak
						$cw = $wordwidth;
						$lines++;
					}
				}
			}

			$lines++;
		}

		return ($lines * ($this->FontSize * $this->cell_height_ratio)) + 2;
	}

	function SetFont($family, $style='', $size=0, $fontfile='', $subset = 'default', $out = true) {
		if($family == '') {
			$family = $this->FontFamily;
		}
		//Select a font; size given in points
		if ($size == 0) {
			$size = $this->FontSizePt;
		}
		// try to add font (if not already added)
		$fontdata =  $this->AddFont($family, $style);
		$this->FontFamily = $fontdata['family'];
		$this->FontStyle = $fontdata['style'];
		$this->CurrentFont = &$this->fonts[$fontdata['fontkey']];
		$this->SetFontSize($size);
	}

	public function Output($name='doc.pdf', $dest='I') {
		/* Enforce local-file scheme if not specified */
		if (strpos($name, "://") === false) {
			global $root_directory;
			$name = "file://". $root_directory . "/" . $name;
		}
		return parent::Output($name, $dest);
	}
}
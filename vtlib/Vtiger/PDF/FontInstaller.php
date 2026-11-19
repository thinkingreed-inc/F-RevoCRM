<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

include_once dirname(__FILE__) . '/TCPDF.php';

/**
 * フォントの追加に失敗したときの例外
 * getMessage() は翻訳キー（languages/<言語>/Settings/Parameters.php）を返す
 */
class Vtiger_PDF_FontInstallException extends Exception {
}

/**
 * TrueType フォント（.ttf）を TCPDF で使える形式に変換して libraries/tcpdf/fonts/ に置く（thinkingreed-inc#1901）
 *
 * 変換は TCPDF に同梱の変換ツール（tcpdf_addfont.php）と同じ TCPDF_FONTS::addTTFfont() で行い、
 * <フォント名>.php / <フォント名>.z / <フォント名>.ctg.z を作る。
 * フォント名は TCPDF と同じ規則でファイル名から決める（例: LINESeedJP-Regular.ttf → lineseedjp）。
 */
class Vtiger_PDF_FontInstaller {

	/** アップロードできるファイルの最大サイズ（バイト） */
	const MAX_FILE_SIZE = 31457280; // 30MB

	/** 変換中の排他に使うロックファイル（フォントのディレクトリに置く） */
	const LOCK_FILE = '.font_install.lock';

	/** 変換で作られるファイルの拡張子 */
	private static $outputExtensions = array('.php', '.z', '.ctg.z');

	/** @var string 変換したフォントを置くディレクトリ（末尾 /） */
	private $fontDir;

	/**
	 * @param string|null $fontDir 省略時は libraries/tcpdf/fonts/
	 */
	public function __construct($fontDir = null) {
		$this->fontDir = $fontDir === null
			? Vtiger_PDF_TCPDF::getFontDirectory()
			: rtrim($fontDir, '/') . '/';
	}

	/**
	 * TCPDF と同じ規則で、元のファイル名からフォント名を決める
	 * 小文字にして英数字と _ 以外を除き、bold / oblique / italic / regular を b / i / i / 空にする。
	 * （例: LINESeedJP-Regular.ttf → lineseedjp、LINESeedJP-Bold.ttf → lineseedjpb）
	 * @param string $fileName
	 * @return string 決められない場合は空文字
	 */
	public static function getFontNameFromFileName($fileName) {
		$name = strtolower(pathinfo((string)$fileName, PATHINFO_FILENAME));
		$name = preg_replace('/[^a-z0-9_]/', '', $name);
		return str_replace(array('bold', 'oblique', 'italic', 'regular'), array('b', 'i', 'i', ''), $name);
	}

	/**
	 * TrueType フォントを変換して置く
	 * @param string $sourcePath 変換元のファイル（アップロードされた一時ファイル）
	 * @param string $originalFileName 元のファイル名（フォント名と拡張子の判定に使う）
	 * @return string 追加したフォント名
	 * @throws Vtiger_PDF_FontInstallException
	 */
	public function install($sourcePath, $originalFileName) {
		if (strtolower(pathinfo((string)$originalFileName, PATHINFO_EXTENSION)) !== 'ttf') {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_INVALID_EXTENSION');
		}
		if (!is_file($sourcePath) || !is_readable($sourcePath)) {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_FAILED');
		}
		$size = filesize($sourcePath);
		if ($size === false || $size === 0) {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_INVALID_FILE');
		}
		if ($size > self::MAX_FILE_SIZE) {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_TOO_LARGE');
		}
		if (!self::isTrueTypeFile($sourcePath)) {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_INVALID_FILE');
		}

		$fontName = self::getFontNameFromFileName($originalFileName);
		if ($fontName === '') {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_INVALID_NAME');
		}
		if (!is_dir($this->fontDir) || !is_writable($this->fontDir)) {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_DIR_NOT_WRITABLE');
		}

		// 同時に同じフォントを追加したときに、変換途中のファイルを互いに壊さないよう排他する。
		// ロックファイルはフォントのディレクトリに置く。共有の一時ディレクトリ（/tmp）に置くと、
		// 別のユーザーが作ったファイルを開けず（sticky ビットと fs.protected_regular）、以後追加できなくなるため。
		$lock = fopen($this->fontDir . self::LOCK_FILE, 'c');
		if ($lock === false || !flock($lock, LOCK_EX)) {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_FAILED');
		}
		$workDir = null;
		try {
			// TCPDF は同名のフォントがあると変換せずにその名前を返すため、上書きや取り違えに気づけない。
			// 既存のフォント（同梱の helvetica など）を置き換えられないよう、同名のファイルがあれば断る。
			foreach (self::$outputExtensions as $extension) {
				if (file_exists($this->fontDir . $fontName . $extension)) {
					throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_ALREADY_EXISTS');
				}
			}

			// addTTFfont() はファイル名からフォント名を決めるため、決めた名前の一時ファイルにして渡す
			$workDir = $this->createWorkDirectory();
			$workFile = $workDir . '/' . $fontName . '.ttf';
			if (!copy($sourcePath, $workFile)) {
				throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_FAILED');
			}

			$this->convert($workFile, $fontName);
			return $fontName;
		} finally {
			if ($workDir !== null) {
				$this->removeWorkDirectory($workDir);
			}
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	/**
	 * TrueType 形式か（先頭 4 バイトが 0x00010000 または "true"）を返す
	 * OpenType（CFF 形式、"OTTO"）やフォントコレクション（"ttcf"）は TCPDF で正しく変換できないため受け付けない。
	 * @param string $path
	 * @return boolean
	 */
	public static function isTrueTypeFile($path) {
		$head = file_get_contents($path, false, null, 0, 4);
		return $head === "\x00\x01\x00\x00" || $head === 'true';
	}

	/**
	 * 変換する。失敗した場合は途中まで作られたファイルを消す
	 * @param string $workFile
	 * @param string $fontName
	 * @throws Vtiger_PDF_FontInstallException
	 */
	private function convert($workFile, $fontName) {
		// 壊れたファイルでは addTTFfont() が警告を出しながら途中のファイルを残すため、警告も失敗として扱う
		set_error_handler(function ($severity, $message) {
			if (!(error_reporting() & $severity) || in_array($severity, array(E_DEPRECATED, E_USER_DEPRECATED), true)) {
				return false;
			}
			throw new ErrorException($message, 0, $severity);
		});
		try {
			$result = TCPDF_FONTS::addTTFfont($workFile, 'TrueTypeUnicode', '', 32, $this->fontDir);
		} catch (Throwable $e) {
			$result = false;
			global $log;
			if ($log) {
				$log->error('PDF フォントの変換に失敗しました: ' . $e->getMessage());
			}
		} finally {
			restore_error_handler();
		}

		if ($result !== $fontName || !Vtiger_PDF_TCPDF::isAvailableFont($fontName, $this->fontDir)) {
			foreach (self::$outputExtensions as $extension) {
				$file = $this->fontDir . $fontName . $extension;
				if (file_exists($file)) {
					unlink($file);
				}
			}
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_INVALID_FILE');
		}
	}

	/**
	 * 変換用の一時ディレクトリを作る
	 * @return string
	 * @throws Vtiger_PDF_FontInstallException
	 */
	private function createWorkDirectory() {
		$workDir = sys_get_temp_dir() . '/frevocrm_pdf_font_' . bin2hex(random_bytes(8));
		if (!mkdir($workDir, 0700)) {
			throw new Vtiger_PDF_FontInstallException('LBL_FONT_UPLOAD_FAILED');
		}
		return $workDir;
	}

	/**
	 * 変換用の一時ディレクトリを消す
	 * @param string $workDir
	 */
	private function removeWorkDirectory($workDir) {
		foreach ((array)glob($workDir . '/*') as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		if (is_dir($workDir)) {
			rmdir($workDir);
		}
	}
}

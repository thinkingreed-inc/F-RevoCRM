<?php
/**
 * log4php is a PHP port of the log4j java logging package.
 *
 * <p>This framework is based on log4j (see {@link http://jakarta.apache.org/log4j log4j} for details).</p>
 * <p>Design, strategies and part of the methods documentation are developed by log4j team
 * (Ceki G�lc� as log4j project founder and
 * {@link http://jakarta.apache.org/log4j/docs/contributors.html contributors}).</p>
 *
 * <p>PHP port, extensions and modifications by VxR. All rights reserved.<br>
 * For more information, please see {@link http://www.vxr.it/log4php/}.</p>
 *
 * <p>This software is published under the terms of the LGPL License
 * a copy of which has been included with this distribution in the LICENSE file.</p>
 *
 * @package log4php
 * @subpackage appenders
 */

/**
 * @ignore
 */
if (!defined('LOG4PHP_DIR')) define('LOG4PHP_DIR', dirname(__FILE__) . '/..');

require_once(LOG4PHP_DIR . '/appenders/LoggerAppenderFile.php');

/**
 * LoggerAppenderRollingFile extends LoggerAppenderFile to backup the log files
 * when they reach a certain size.
 *
 * <p>Parameters are {@link $maxFileSize}, {@link $maxBackupIndex}.</p>
 *
 * <p>Contributors: Sergio Strampelli.</p>
 *
 * @author VxR <vxr@vxr.it>
 * @version $Revision: 1.14 $
 * @package log4php
 * @subpackage appenders
 */
class LoggerAppenderCustomRule extends LoggerAppenderFile {

    var $maxBackupIndex  = 1;
    var $rollDateformat = 'Ymd';

    /**
     * @var string the filename expanded
     * @access private
     */
    var $expandedFileName = null;

    /**
     * Constructor.
     *
     * @param string $name appender name
     */
    function __construct($name)
    {
        parent::__construct($name);
    }

    /**
     * Returns the value of the MaxBackupIndex option.
     * @return integer
     */
    function getExpandedFileName() {
        return $this->expandedFileName;
    }

    /**
     * Returns the value of the MaxBackupIndex option.
     * @return integer
     */
    function getMaxBackupIndex() {
        return $this->maxBackupIndex;
    }

    function getRollDateformat() {
        return $this->rollDateformat;
    }

    /**
     * Implements the usual roll over behaviour.
     *
     * <p>If MaxBackupIndex is positive, then files File.1, ..., File.MaxBackupIndex -1 are renamed to File.2, ..., File.MaxBackupIndex.
     * Moreover, File is renamed File.1 and closed. A new File is created to receive further log output.
     *
     * <p>If MaxBackupIndex is equal to zero, then the File is truncated with no backup files created.
     */
    function rollOver()
    {
        $fileName = $this->createFilename();

        $this->setFile($fileName, false);
        unset($this->fp);
        $this->activateOptions();
    }

    function activateOptions() {
        parent::activateOptions();

        $files = scandir(dirname($this->expandedFileName), 1);
        // $output = print_r($files, true);
        // file_put_contents("test.txt", $output);
        $basename = basename($this->expandedFileName);
        $counter = 0;
        foreach($files as $f) {
            if(strpos($f, $basename) !== false) {
                if($counter > $this->maxBackupIndex) {
                    unlink(dirname($this->expandedFileName).'/'.$f);
                }
                $counter++;
            }
        }
    }

    function setFileName($fileName)
    {
        $this->fileName = $this->createFilename($fileName);
        LoggerLog::debug("LoggerAppenderRollingFile::setFileName():filename=[{$fileName}]:expandedFileName=[{$this->expandedFileName}]");
    }

    /**
     * Set the maximum number of backup files to keep around.
     *
     * <p>The <b>MaxBackupIndex</b> option determines how many backup
     * files are kept before the oldest is erased. This option takes
     * a positive integer value. If set to zero, then there will be no
     * backup files and the log file will be truncated when it reaches
     * MaxFileSize.
     *
     * @param mixed $maxBackups
     */
    function setMaxBackupIndex($maxBackups)
    {
        if (is_numeric($maxBackups))
            $this->maxBackupIndex = abs((int)$maxBackups);
    }

    function setRollDateformat($value)
    {
        $this->rollDateformat = $value;
    }

    function createFilename($fileName = null) {
//        $currentTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Tokyo');
        if($this->expandedFileName == null) {
            $this->expandedFileName = $fileName;
        }
        $fileName = $this->getExpandedFileName().'.'.date($this->getRollDateformat()).'.txt';
//        date_default_timezone_set($currentTimezone);
        return $fileName;
    }

    /**
     * @param LoggerLoggingEvent $event
     */
    function append($event)
    {
        if ($this->fp) {
            parent::append($event);
            if ($this->fileName != $this->createFilename($this->fileName))
                $this->rollOver();
        }
    }
}
?>

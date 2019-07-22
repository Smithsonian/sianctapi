<?php
/**
 * Class Logger
 *
 * A generic logger for writing log files.
 */

class Logger {

  // The log levels.
  const LEVEL_EMERGENCY = 0;
  const LEVEL_ALERT = 1;
  const LEVEL_CRITICAL = 2;
  const LEVEL_ERROR = 3;
  const LEVEL_WARNING = 4;
  const LEVEL_NOTICE = 5;
  const LEVEL_INFO = 6;
  const LEVEL_DEBUG = 7;

  // The file handler for writing logs.
  private $file;

  // The log level at which to write logs.
  private $level = self::LEVEL_NOTICE;

  /**
   * Logger constructor.
   *
   * @param $path
   * @param $filename
   */
  function __construct($path, $filename) {
    if (!empty($path) && is_writeable($path)) {
      $file = rtrim($path, '/') . '/' . $filename;
      $handler = fopen($file, 'a');
      if ($handler !== FALSE) {
        fclose($handler);
        $this->file = $file;
      }
    }
  }

  /**
   * Log a message at the emergency level.
   *
   * @param String $message The emergency message to log.
   */
  function emergency($message) {
    $this->log($message, self::LEVEL_EMERGENCY);
  }

  /**
   * Log a message at the alert level.
   *
   * @param String $message The alert message to log.
   */
  function alert($message) {
    $this->log($message, self::LEVEL_ALERT);
  }

  /**
   * Log a message at the critical level.
   *
   * @param String $message The critical message to log.
   */
  function critical($message) {
    $this->log($message, self::LEVEL_CRITICAL);
  }

  /**
   * Log a message at the error level.
   *
   * @param String $message The error message to log.
   */
  function error($message) {
    $this->log($message, self::LEVEL_ERROR);
  }

  /**
   * Log a message at the warning level.
   *
   * @param String $message The warning message to log.
   */
  function warning($message) {
    $this->log($message, self::LEVEL_WARNING);
  }

  /**
   * Log a message at the notice level.
   *
   * @param String $message The notice message to log.
   */
  function notice($message) {
    $this->log($message, self::LEVEL_NOTICE);
  }

  /**
   * Log a message at the info level.
   *
   * @param String $message The info message to log.
   */
  function info($message) {
    $this->log($message, self::LEVEL_INFO);
  }

  /**
   * Log a message at the debug level.
   *
   * @param String $message The debug message to log.
   */
  function debug($message) {
    $this->log($message, self::LEVEL_DEBUG);
  }

  /**
   * Write a message to the log file.
   *
   * @param $message String The message to write to the log.
   */
  private function log($message, $level) {
    // If no file initialized, no-op.
    if (!$this->file) {
      return;
    }

    // If the message's level is lower than the configured level, no-op.
    if ($this->level < $level) {
      return;
    }

    $handler = fopen($this->file, 'a');
    if ($handler) {
      fwrite($handler, $this->getCurrentTime() . ': ' . $message . "\n");
      fclose($handler);
    }
  }

  /**
   * Get the current time in a log-friendly format.
   *
   * @return String The formatted timestamp for the logs.
   */
  private function getCurrentTime() {
    list($msec, $time) = explode(" ", microtime());
    return date("Y-m-d H:i:s", $time) . '.' . substr($msec, 2, 4);
  }

  /**
   * @return int
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * @param int $level
   */
  public function setLevel($level) {
    // Require an integer.
    if (is_int($level)) {
      return;
    }

    // Verify that the level is within the limits.
    if ($level < self::LEVEL_EMERGENCY || $level > self::LEVEL_DEBUG) {
      return;
    }

    $this->level = $level;
  }
}
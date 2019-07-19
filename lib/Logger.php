<?php
/**
 * Class Logger
 *
 * A generic logger for writing log files.
 */

class Logger {
  // The file handler.
  private $file;

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
   * Write a message to the log file.
   *
   * @param $message String: the message to write to the log.
   */
  function writeLog($message) {
    // If no file initialize, no-op.
    if (!$this->file) {
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
   */
  private function getCurrentTime() {
    list($msec, $time) = explode(" ", microtime());
    return date("Y-m-d H:i:s", $time) . '.' . substr($msec, 2, 4);
  }
}
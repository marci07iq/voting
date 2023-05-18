<?php

class Messages
{
  public static $messages = [];
  public static $warnings = [];
  public static $errors = [];

  public static function message($msg, $where = "")
  {
    Messages::$messages[] = $msg . ($where == "" ? "" : "[in $where]");
  }
  public static function warning($msg, $where = "")
  {
    Messages::$warnings[] = $msg . ($where == "" ? "" : "[in $where]");
  }
  public static function error($msg, $where = "")
  {
    Messages::$errors[] = $msg . ($where == "" ? "" : "[in $where]");
  }
}

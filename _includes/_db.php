<?php
require_once __DIR__ . "/_message.php";

function randomBase64($length)
{
  //URL, path-safe base64
  $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
  $str = "";

  for ($i = 0; $i < $length; $i++) {
    $str .= $chars[mt_rand(0, strlen($chars) - 1)];
  }

  return $str;
}

function randomBase16($length)
{
  $chars = "0123456789ABCDEF";
  $str = "";

  for ($i = 0; $i < $length; $i++) {
    $str .= $chars[mt_rand(0, strlen($chars) - 1)];
  }

  return $str;
}

function randomBase10($length)
{
  $chars = "0123456789";
  $str = "";

  for ($i = 0; $i < $length; $i++) {
    $str .= $chars[mt_rand(0, strlen($chars) - 1)];
  }

  return $str;
}

class DB
{
  public static $dbh = null;

  public static function connect()
  {
    try {
      $c = array(
        "server"=>"",
        "dbname"=>"",
        "user"=>"",
        "pw"=>"");
      DB::$dbh = new PDO("mysql:host=" . $c["server"] . ";dbname=" . $c["dbname"], $c["user"], $c["pw"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") );
      // set the PDO error mode to exception
      DB::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      Messages::error($e->getMessage(), "DB::connect()");
      //echo "Connection failed: " . $e->getMessage();
    }
  }

  public static function last_insert_id()
  {
    return DB::$dbh->lastInsertID();
  }

  public static function query($t, $binds = [])
  {
    //Messages::message($t);
    //Messages::message(serialize($binds));
    try {
      if (!isset(DB::$dbh) || DB::$dbh === null) {
        DB::connect();
      }

      $stmt = DB::$dbh->prepare($t);
      foreach ($binds as $k => $v) {
        $data_type = PDO::PARAM_STR;
        if (gettype($v) == "string") {
          $data_type = PDO::PARAM_STR;
        } else if (gettype($v) == "boolean") {
          $data_type = PDO::PARAM_BOOL;
        } else if (gettype($v) == "integer") {
          $data_type = PDO::PARAM_INT;
        } else {
          webpage::return_error("Bad data type for argument $k: " . gettype($v), "DB::query(cmd,binds)");
          return;
        }
        $stmt->bindValue($k, $v, $data_type);
      }
      $stmt->execute();
      return $stmt;
    } catch (PDOException $e) {
      Messages::error($e->getMessage(), "DB::query()");
      return FALSE;
    }
    return true;
  }

  public static function search_like($str) {
    if(is_string($str)) {
      return "%" . str_replace("%", "\\%", $str) . "%";
    }
  }
}

<?php
class Captcha
{
  private static $public_key = Config::CAPTCHA_PUBLIC;
  private static $secret_key = Config::CAPTCHA_PRIVATE;

  public static function verify()
  {
    /*if (Config::$debug) {
      return true;
    }*/

    $response = $_REQUEST["g-recaptcha-response"];
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = array(
      "secret" => Captcha::$secret_key,
      "response" => $response,
    );
    $options = array(
      "http" => array(
        "method" => "POST",
        "content" => http_build_query($data),
      ),
    );
    $context = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $captcha_success = json_decode($verify);
    if ($captcha_success->success == false) {
      return false;
    } else if ($captcha_success->success == true) {
      return true;
    }
    return false;
  }
  public static function print()
  {
  
    print("<script src=\"https://www.google.com/recaptcha/api.js\" async defer></script>");
    print("<div class=\"g-recaptcha\" data-sitekey=\"" . Captcha::$public_key . "\"></div>");
  
  }
}

<?php

require_once __DIR__ . "/_config.php";
require_once __DIR__ . "/_message.php";

//from_name: Part before @ (not included)
function sendMail($to, $subject, $text)
{
  $from_account = Config::MAIL_ACCOUNT;
  $from_display = Config::MAIL_NAME;

  //Just in case user gives invalid address
  $full_from = (explode("@", $from_account)[0]) . "@" . Config::MAIL_DOMAIN;

  switch (Config::MAIL_BACKEND) {
    case "sendmail":
      $headers = "From: " . $from_display . " <" . $full_from . ">\r\n";
      $headers .= "Reply-To: jcr.it@merton.ox.ac.uk\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";

      return mail($to, $subject, $text, $headers);
      break;
    case "mailgun":
      $array_data = array(
        'from' => $from_display . '<' . $full_from . '>',
        'to' => $to,
        'subject' => $subject,
        'text' => $text,
        'h:Reply-To' => Config::WEBMASTER_EMAIL
      );

      $session = curl_init(Config::MAIL_MAILGUN_APINAME . '/messages');
      curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($session, CURLOPT_USERPWD, 'api:' . Config::MAIL_MAILGUN_APIKEY);
      curl_setopt($session, CURLOPT_POST, true);
      curl_setopt($session, CURLOPT_POSTFIELDS, $array_data);
      curl_setopt($session, CURLOPT_HEADER, false);
      curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
      curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);

      $response = curl_exec($session);
      if($response !== True) {
        Messages::error("Call failed", "sendMail()");
        return False;
      }
      $http_status = curl_getinfo($session, CURLINFO_RESPONSE_CODE);
      if($http_status !== 200) {
        Messages::error("API error " . $http_status, "sendMail()");
        return False;
      }
      curl_close($session);
      return True;
      break;
    case "dev-message":
      Messages::message("Email From: " . $from_display . " <" . $full_from . ">");
      Messages::message("To: " . $to);
      Messages::message("Subject: " . $subject);
      Messages::message("Body: " . $text);
      return False;
    default:
      Messages::error("Unknown backend", "sendMail()");
      return False;
      break;
  }
}

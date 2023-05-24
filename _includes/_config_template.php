<?php
class Config {
    const SITE_NAME = "Voting System";
    const SITE_URL = "voting.example.com";
    const WEBMASTER_NAME = "Webmaster Name";
    const WEBMASTER_EMAIL = "email@example.com";
    
    const DB_ADDRESS = "localhost";
    const DB_USERNAME = "root";
    const DB_PASSWORD = "password";
    const DB_DATABASE = "demo";

    const MAIL_DOMAIN = "voting.example.com";
    const MAIL_ACCOUNT = "noreply";
    const MAIL_NAME = "Election System";
    const MAIL_BACKEND = "sendmail"; //"sendmail", "mailgun", "dev-message"

    const MAIL_REGISTER_SUBJECT = "Election voting key";
    const MAIL_REGISTER_BODY =
        "Dear voter,\r\r" .
        "Your voting link for the upcoming election is: https://" . Config::SITE_URL . "/?token=%1\$s\r" .
        "If you have any concerns, please contact " . Config::WEBMASTER_EMAIL . " for information about voting.\r\r" .
        "Happy voting,\r" .
        "Your organisation";

    const MAIL_MAILGUN_APIKEY = "api:####";
    const MAIL_MAILGUN_APINAME = "https://api.mailgun.net/v3/####";
}
?>
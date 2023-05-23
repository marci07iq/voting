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

    const MAIL_MAILGUN_APIKEY = "api:####";
    const MAIL_MAILGUN_APINAME = "https://api.mailgun.net/v3/####";
}
?>
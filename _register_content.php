<?php
require_once __DIR__ . "/_includes/_message.php";
require_once __DIR__ . "/_includes/_db.php";
require_once __DIR__ . "/_includes/_mail.php";
require_once __DIR__ . "/_includes/_recaptcha.php";

function send_token() {
  $res = DB::query("SELECT regkeys.token AS token, regkeys.email AS email FROM regkeys WHERE regkeys.email = :email AND regkeys.reg_key = :key", array(":email" => $_POST["email"], ":key" => $_POST["register_key"]));
  if($res) {
    if($res->rowCount() === 1) {
      $r = $res->fetch(PDO::FETCH_ASSOC);
      if(!sendMail($r["email"], "OGM Voting credentials", "Your token for the OGM Vote:\r\nhttps://voting.mertonjcr.org?token=" . urlencode($r["token"]) . "\r\n\r\nPlease keep this private.\r\nIf you have any questions, please contact the Returning Officer, marcell.szakaly@merton.ox.ac.uk\r\n")) {
        return array("ok"=>FALSE, "msg"=>"Email sending error");
      }
    }
    //Dont disclose of valid
    return array("ok"=>TRUE);
  }
  return array("ok"=>FALSE, "msg"=>"Database error");
}

if(isset($_REQUEST["register_key"]) && is_string($_REQUEST["register_key"])) {
  if(isset($_POST["email"]) && is_string($_POST["email"])) {
    if(!Captcha::verify()) {
      Messages::error("Invalid captcha");
      return;
    }
    $send_res = send_token();
    if($send_res["ok"] !== TRUE) {
      Messages::error($send_res["msg"]);
    } else {
      ?>
      If that is valid, we sent a link.
      <?php
    }
  } else {
    ?>
    <p>OGM Vote registration</p>
  <form method="POST" action="index.php" id="reg_key">
    <input hidden name="register_key" value="<?php echo htmlspecialchars($_REQUEST["register_key"]);?>"/>
    <input type="email" name="email" placeholder="Your Merton email"/></br>
    <?php Captcha::print(); ?>
    <input type="submit" value="Register"/>
  </form>
    <?php
  }
}
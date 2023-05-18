<?php

require_once __DIR__ . "/_voting.php";

$token_check = check_token();

function read_ballot($token_check) {
  $json = json_decode($token_check["json"]);
  if($json === NULL) {
    return array("ok" => FALSE, "msg" => "Election data invalid: " . json_last_error_msg());
  }
  
  $res = array();

  foreach($json as $candidate) {
    if(!isset($_POST["vote"][$candidate->id])) {
      return array("ok" => FALSE, "msg" => "Vote for candidate " . $candidate->name . " is missing.");
    } else {
      $val = $_POST["vote"][$candidate->id];
      if(is_string($val)) {
        $res[$candidate->id] = $val;
      } else {
        return array("ok" => FALSE, "msg" => "Vote for candidate " . $candidate->name . " is not a string.");
      }
    }
  }
  return array("ok" => TRUE, "res"=>json_encode($res));
}

if(!isset($token_check["ok"]) || $token_check["ok"] !== TRUE) {
  if(isset($token_check["msg"]) && is_string($token_check["msg"])) {
    Messages::error($token_check["msg"]);
  } else {
    Messages::error("Unknown error in check token");
  }
} else {
  //Valid token (But I'll check it again later)
  $ballot = read_ballot($token_check);

  if(!isset($ballot["ok"]) || $ballot["ok"] !== TRUE) {
    if(isset($ballot["msg"]) && is_string($ballot["msg"])) {
      Messages::error($ballot["msg"]);
    } else {
      Messages::error("Unknown error in read ballot");
    }
  } else {
    if(!isset($ballot["res"])) {
      Messages::error("Unknown error in read ballot");
    } else {
      //Ballot read
      $cast_result = cast_vote($ballot["res"]);

      if(!isset($cast_result["ok"]) || $cast_result["ok"] !== TRUE) {
        if(isset($cast_result["msg"]) && is_string($cast_result["msg"])) {
          Messages::error($cast_result["msg"]);
        } else {
          Messages::error("Unknown error in cast vote");
        }
      } else {
        //Vote cast

        ?>
        Your vote was successfully recorded.</br>
        <?php
        //Format vote
        if(!isset($token_check["json"])) {
          return array("ok" => FALSE, "msg" => "Election JSON missing");
        }
        $election_json = json_decode($token_check["json"]);
        if($token_check === NULL) {
          return array("ok" => FALSE, "msg" => "Election JSON is invalid: " . htmlspecialchars(json_last_error_msg()));
        }

        $parse_vote = parse_vote($ballot["res"], $election_json);
        
        if(isset($parse_vote["ok"])) {
          echo "<p>";
          echo $parse_vote["html"];
          echo "</p>";
          echo htmlspecialchars($parse_vote["msg"]);
        }
        ?></br>
        </br>
        Your unique vote tracker is:</br>
        <?php echo htmlspecialchars($cast_result["proof"]); ?></br>
        Please write it down, you can later use it to verify that your vote is in the ballot box.</br>
        <a href="/index.php?election=<?php echo urlencode($_REQUEST["election"]); ?>&ntoken=<?php echo urlencode($_REQUEST["token"])?>">Click here to view the election</a></br>
        <a href="/index.php?token=<?php echo urlencode($_REQUEST["token"])?>">Click here to vote in other elections</a></br>
        <?php
      }
    }
  }
}
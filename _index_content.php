<?php
require_once __DIR__ . "/_voting.php";
require_once __DIR__ . "/_includes/_message.php";

if(isset($_REQUEST["election"]) && isset($_REQUEST["token"])) {
  //Election + token: Voting booth
  $check_token = check_token();

  if(!isset($check_token["ok"]) || $check_token["ok"] !== TRUE) {
    if(isset($check_token["msg"]) && is_string($check_token["msg"])) {
      Messages::error($check_token["msg"]);
    } else {
      Messages::error("Unknown error in check token");
    }
  } else {
    ?>
    <p><a href="/index.php?election=<?php echo urlencode($_REQUEST["election"]); ?>&ntoken=<?php echo urlencode($_REQUEST["token"]); ?>">View this election</a></p>
    <?php

    //Token OK

    if ($check_token["archived"] !== FALSE) {
      Messages::error("Election is archived");
    } elseif ($check_token["open"] !== TRUE) {
      Messages::error("Polling is not open");
    } elseif($check_token["used"] !== FALSE) {
      Messages::error("You have already voted in this election");
    } else {
      //Token unused. Print voting booth
      require_once __DIR__ . "/_booth.php";
      $booth_print = print_booth($check_token, $check_token["ranked"]);

      if(!isset($booth_print["ok"]) || $booth_print["ok"] !== TRUE) {
        if(isset($booth_print["msg"]) && is_string($booth_print["msg"])) {
          Messages::error($booth_print["msg"]);
        } else {
          Messages::error("Unknown error in check token");
        }
      }
    }
  }
} elseif(isset($_REQUEST["election"])) {
  //Election only: results/proof overview
  $ntoken_check = check_ntoken();

  $election = get_election();

  if(!isset($election["ok"]) || $election["ok"] !== TRUE) {
    if(isset($election["msg"]) && is_string($election["msg"])) {
      Messages::error($election["msg"]);
    } else {
      Messages::error("Unknown error in get election");
    }
  } else {
    $election_archived = (!isset($election["archived"])) || ($election["archived"] !== FALSE);
    $election_voteable = isset($election["open"]) && ($election["open"] === TRUE) && (!$election_archived);

    $proofs = NULL;
    if($election_archived) {
      $proofs = get_proofs_and_votes($election);
    } else {
      $proofs = get_proofs();
    }

    if(!isset($proofs["ok"]) || $proofs["ok"] !== TRUE) {
      if(isset($proofs["msg"]) && is_string($proofs["msg"])) {
        Messages::error($proofs["msg"]);
      } else {
        Messages::error("Unknown error in get proofs");
      }
    } else {
      //We have proofs
      ?>

      <h2><?php echo htmlspecialchars($election["name"]) ?></h2>

      <p><?php echo htmlspecialchars($election["description"]) ?></p>

      <h4>Eligibility</h4>

      <?php
      
      if($election_archived) {
        echo "<p>Election has been archived.</p>";
      } elseif($election_voteable) {
        echo "<p>Polling is currently open.</p>";
      } else {
        echo "<p>Polling has not yet opened.</p>";
      }

      if(isset($_REQUEST["ntoken"]) && is_string($_REQUEST["ntoken"])) {
        if(isset($ntoken_check["ok"]) && $ntoken_check["ok"] === TRUE && isset($ntoken_check["used"])) {
          if($ntoken_check["used"] === FALSE) {
            if($election_voteable) {
              ?>
              <p><a href="/index.php?token=<?php echo urlencode($_REQUEST["ntoken"]); ?>&election=<?php echo urlencode($election["id"]); ?>">Click here to vote in this election</a></p>
              <?php
            } else {
              echo "<p>You have not voted in this election.</p>";
            }
          } else {
            if($election_archived) {
              echo "<p>You have already voted in this election. You should find your voting proof and ballot listed below.</p>";
            } else {
              echo "<p>You have already voted in this election. You should find your voting proof listed below.</p>";
            }
          }
        } else {
          echo "<p>You are not eligible to vote in this election with your current token.</p>";
        }
      } else {
        echo "<p>You are not logged in with a token.</p>";
      }
      ?>
      <h4>Results</h4>
      <p><?php
      if($election_voteable) {
        echo ((count($proofs["res"]) > 0) ? count($proofs["res"]) : "No") . " votes have been cast so far.";
      } else {
        echo ((count($proofs["res"]) > 0) ? count($proofs["res"]) : "No") . " votes have been cast.";
      }
      ?></p>
      <table>
      <?php
      foreach($proofs["res"] as $proof) {
        if($election_archived) {
          ?>
          <tr><td><?php echo htmlspecialchars($proof["proof"]) ?></td><td><?php echo "<p>"; echo $proof["html"]; echo "</p>"; echo htmlspecialchars($proof["msg"]); ?></td></tr>
          <?php
        } else {
          ?>
          <tr><td><?php echo htmlspecialchars($proof["proof"]) ?></td></tr>
          <?php
        }
      }
      ?>
      </table>
      <?php
    }
  }
} elseif(isset($_REQUEST["token"]) && is_string($_REQUEST["token"])) {
  //Token only: My elections overview
  
  $elections = list_my_elections();

  if(!isset($elections["ok"]) || $elections["ok"] !== TRUE) {
    if(isset($elections["msg"]) && is_string($elections["msg"])) {
      Messages::error($elections["msg"]);
    } else {
      Messages::error("Unknown error in list elections");
    }
  } else {
    //We have proofs

    $nonarchived_elections = array();
    $archived_elections = array();
    foreach($elections["res"] as $election) {
      if($election["archived"] !== FALSE) {
        $archived_elections[] = $election;
      } else {
        $nonarchived_elections[] = $election;
      }
    }
    ?>
    Here are the elections you can vote in:
    <table class="sparse">
    <?php
    foreach($nonarchived_elections as $election) {
      ?>
      <tr><td><a href="/index.php?election=<?php echo urlencode($election["id"]); ?>&ntoken=<?php echo urlencode($_REQUEST["token"]); ?>"><?php echo htmlspecialchars($election["name"]) ?></a></td><td><?php 
      if($election["used"] === FALSE) {
        if($election["open"] === TRUE) {
          ?>
          <a href="/index.php?token=<?php echo urlencode($_REQUEST["token"]); ?>&election=<?php echo urlencode($election["id"]); ?>">Click here to vote</a>
          <?php
        } else {
          echo "Polling is not open";
        }
      } else {
        echo "You have already voted in this election";
      }?></td></tr>
      <?php
    }
    ?>
    </table>
    Here are the archived elections you were eligible for:
    <table class="sparse">
    <?php
    foreach($archived_elections as $election) {
      ?>
      <tr><td><a href="/index.php?election=<?php echo urlencode($election["id"]); ?>&ntoken=<?php echo urlencode($_REQUEST["token"]); ?>"><?php echo htmlspecialchars($election["name"]) ?></a></td><td><?php 
      if($election["used"] === FALSE) {
        echo "You didn't vote in this election";
      } else {
        echo "You voted in this election";
      }?></td></tr>
      <?php
    }
    ?>
    </table>
    <?php
  }
} else {
  //None: Election overview + enter token
  $all_elections = list_all_elections();

  if(!isset($all_elections["ok"]) || $all_elections["ok"] !== TRUE) {
    if(isset($all_elections["msg"]) && is_string($all_elections["msg"])) {
      Messages::error($all_elections["msg"]);
    } else {
      Messages::error("Unknown error in list all elections");
    }
  } else {
    $nonarchived_elections = array();
    $archived_elections = array();
    foreach($all_elections["res"] as $election) {
      if($election["archived"] !== FALSE) {
        $archived_elections[] = $election;
      } else {
        $nonarchived_elections[] = $election;
      }
    }
    ?>
    Here are the elections currently running:
    <table class="sparse">
    <?php
    foreach($nonarchived_elections as $election) {
      ?>
      <tr><td><a href="/index.php?election=<?php echo urlencode($election["id"]);
      if(isset($_REQUEST["ntoken"]) && is_string($_REQUEST["ntoken"])) {
        echo "&ntoken=" . htmlspecialchars(urlencode($_REQUEST["ntoken"]));
      }?>"><?php echo htmlspecialchars($election["name"]) ?></a></td><td><?php echo (($election["open"] === TRUE) ? "Polling is open" : "Polling is closed"); ?></td></tr>
      <?php
    }
    ?>
    </table>
    Here are the archived elections:
    <table class="sparse">
    <?php
    foreach($archived_elections as $election) {
      ?>
      <tr><td><a href="/index.php?election=<?php echo urlencode($election["id"]);
      if(isset($_REQUEST["ntoken"]) && is_string($_REQUEST["ntoken"])) {
        echo "&ntoken=" . htmlspecialchars(urlencode($_REQUEST["ntoken"]));
      }?>"><?php echo htmlspecialchars($election["name"]) ?></a></td><td>Election has been archived</td></tr>
      <?php
    }
    ?>
    </table>
    <?php
  }
}
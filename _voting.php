<?php

require_once __DIR__ . "/_includes/_db.php";
require_once __DIR__ . "/_includes/_mail.php";

function list_elections() {
  $ret = array();
  if(isset($_REQUEST["token"]) && is_string($_REQUEST["token"])) {
    $token = $_REQUEST["token"];
    $res = DB::query("SELECT elections.id AS id, elections.name AS name, elections.open AS open, elections.archived AS archived, tokens.used AS used FROM tokens INNER JOIN elections ON tokens.election = elections.sid WHERE tokens.token=:token ORDER BY elections.sid ASC", array(":token" => $token));
    if($res) {
      while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $ret[] = array("id" => $r["id"], "name" => $r["name"], "open" => ($r["open"] == 1), "archived" => ($r["archived"] == 1), "used" => (0 != $r["used"]));
      }
      return array("ok"=>TRUE, "res"=>$ret);
    } else {
      return array("ok"=>FALSE, "msg"=>"Database error");
    }
  }
  return array("ok"=>FALSE, "msg"=>"Invalid query");
}

function list_all_elections() {
  $ret = array();
  $res = DB::query("SELECT elections.id AS id, elections.name AS name, elections.open AS open, elections.archived AS archived FROM elections ORDER BY elections.sid ASC");
  if($res) {
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
      $ret[] = array("id" => $r["id"], "name" => $r["name"], "open" => ($r["open"] == 1), "archived" => ($r["archived"] == 1));
    }
    return array("ok"=>TRUE, "res"=>$ret);
  }
  return array("ok"=>FALSE, "msg"=>"Database error");
}

function check_token_variable($token, $election) {
  if(is_string($token) && is_string($election)) {
    $res = DB::query("SELECT elections.sid AS sid, elections.id AS id, elections.name AS name, elections.description AS description, elections.json AS json, elections.open AS open, elections.archived AS archived, tokens.used AS used FROM tokens INNER JOIN elections ON tokens.election = elections.sid WHERE tokens.token=:token AND elections.id = :election", array(":token" => $token, ":election" => $election));
    if($res) {
      if($res->rowCount() === 1) {
        $r = $res->fetch(PDO::FETCH_ASSOC);
        return array("ok" => TRUE, "sid" => $r["sid"], "id" => $r["id"], "name" => $r["name"], "description" => $r["description"], "json" => $r["json"], "open" => ($r["open"] == 1), "used" => (0 != $r["used"]), "archived" => ($r["archived"] == 1));
      }
      return array("ok"=>FALSE, "msg"=>"Invalid token");
    }
    return array("ok"=>FALSE, "msg"=>"Database error");
  }
  return array("ok"=>FALSE, "msg"=>"Invalid query");
}

function check_token() {
  if(isset($_REQUEST["token"]) && isset($_REQUEST["election"])) {
    $token = $_REQUEST["token"];
    $election = $_REQUEST["election"];
    
    return check_token_variable($token, $election);
  }
  return array("ok"=>FALSE, "msg"=>"Invalid query");
}

function check_ntoken() {
  if(isset($_REQUEST["ntoken"]) && isset($_REQUEST["election"])) {
    $token = $_REQUEST["ntoken"];
    $election = $_REQUEST["election"];
    
    return check_token_variable($token, $election);
  }
  return array("ok"=>FALSE, "msg"=>"Invalid query");
}

function exists_proof($proof) {
  $res = DB::query("SELECT COUNT(proof) FROM votes WHERE :proof = proof", array(":proof" => $proof));
  if($res) {
    if($res->rowCount() === 1) {
      return array("ok"=>TRUE, "exists"=>FALSE);
    }
    return array("ok"=>TRUE, "exists"=>TRUE);
  } else {
    return array("ok"=>FALSE, "msg"=>"Database error");
  }
}

//Assume votes table is locked
function unique_proof() {
  $some_proof = NULL;
  do {
    $some_proof = randomBase10(12);

    $proof_check = exists_proof($some_proof);
    if(!isset($proof_check["ok"]) || $proof_check["ok"] !== TRUE || !isset($proof_check["exists"])) {
      if(isset($proof_check["msg"]) && is_string($proof_check["msg"])) {
        return array("ok"=>FALSE, "msg"=>$proof_check["msg"]);
      }
      return array("ok"=>FALSE, "msg"=>"Unknown error in exists_proof");
    }
  } while ($proof_check["exists"] !== FALSE);

  return array("ok"=>TRUE, "proof"=>$some_proof);
}

function cast_vote($vote) {
  if(isset($_REQUEST["token"])) {
    $token = $_REQUEST["token"];

    if(is_string($vote) && strlen($vote) < 8192) {
      DB::query("LOCK TABLES tokens WRITE, votes WRITE, elections READ");
      $check = check_token();
      if(isset($check["ok"]) && $check["ok"] === TRUE && isset($check["used"]) && isset($check["used"])) {
        if($check["used"] === FALSE) {
          if($check["archived"] === FALSE) {
            if($check["open"] === TRUE) {
              //Everyting OK. Vote.

              //Generate proof                
              $proof = unique_proof();
              if(!isset($proof["ok"]) || $proof["ok"] !== TRUE || !isset($proof["proof"])) {
                //Error generating proof
                DB::query("UNLOCK TABLES");
                if(isset($proof["msg"]) && is_string($proof["msg"])) {
                  return array("ok"=>FALSE, "msg"=>$proof["msg"]);
                }
                return array("ok"=>FALSE, "msg"=>"Unknown error in exists_proof");
              }

              //Proof is valid. Get value:
              $proof_val = $proof["proof"];
              
              //Make them succeed together
              DB::$dbh->beginTransaction();

              $res_ins = DB::query("INSERT INTO votes (vote, proof, election) VALUES (:vote, :proof, :election)", array(":vote"=>$vote, ":proof"=>$proof_val, ":election"=>$check["sid"]));
              $res_upd = DB::query("UPDATE tokens SET used = TRUE WHERE tokens.token = :token AND tokens.election = :sid", array(":token"=>$token, ":sid"=>$check["sid"]));

              $vote_ok = $res_ins && $res_upd;

              if($vote_ok) {
                DB::$dbh->commit();
              } else {
                DB::$dbh->rollBack();
              }

              DB::query("UNLOCK TABLES");

              if($vote_ok) {
                return array("ok"=>TRUE, "msg"=>"Vote cast", "proof"=>$proof_val);
              }
              return array("ok"=>FALSE, "msg"=>"Database error");
            }
            DB::query("UNLOCK TABLES");

            return array("ok"=>FALSE, "msg"=>"Polling is closed");
          }
          DB::query("UNLOCK TABLES");

          return array("ok"=>FALSE, "msg"=>"Election is archived");
        }
        DB::query("UNLOCK TABLES");

        return array("ok"=>FALSE, "msg"=>"You have already voted in this election");
      }
      DB::query("UNLOCK TABLES");
      if(isset($check["msg"])) {
        return array("ok"=>FALSE, "msg"=>$check["msg"]);
      }
      return array("ok"=>FALSE, "msg"=>"Unknown error");
    }
    return array("ok"=>FALSE, "msg"=>"Vote data invalid");
  }
  return array("ok"=>FALSE, "msg"=>"Invalid query");
}

function get_election_by_id($election_id) {
  $res = DB::query("SELECT elections.sid AS sid, elections.id AS id, elections.name AS name, elections.description AS description, elections.json AS json, elections.open AS open, elections.archived AS archived FROM elections WHERE elections.id = :election", array(":election" => $election_id));
  if($res) {
    if($res->rowCount() === 1) {
      $r = $res->fetch(PDO::FETCH_ASSOC);
      return array("ok" => TRUE, "sid" => $r["sid"], "id" => $r["id"], "name" => $r["name"], "description" => $r["description"], "json" => $r["json"], "open" => ($r["open"] == 1), "archived" => ($r["archived"] == 1));
    }
    return array("ok"=>FALSE, "msg"=>"Invalid election");
  }
  return array("ok"=>FALSE, "msg"=>"Database error");
}

function get_election() {
  if(isset($_REQUEST["election"]) && is_string($_REQUEST["election"])) {
    $election_res = get_election_by_id($_REQUEST["election"]);

    if(!isset($election_res["ok"])) {
      return array("ok"=>FALSE, "msg"=>"Unknown error in get election by id");
    } else {
      return $election_res;
    }
  }
  return array("ok"=>FALSE, "msg"=>"Invalid query");
}

function get_proofs() {
  $ret = array();
  if(isset($_REQUEST["election"]) && is_string($_REQUEST["election"])) {
    $election = $_REQUEST["election"];
    $res = DB::query("SELECT votes.proof AS proof FROM votes INNER JOIN elections ON votes.election=elections.sid WHERE elections.id=:election ORDER BY votes.proof ASC", array(":election" => $election));
    if($res) {
      while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $ret[] = array("proof"=>$r["proof"]);
      }
      return array("ok"=>TRUE, "res"=>$ret);
    } else {
      return array("ok"=>FALSE, "msg"=>"Database error");
    }
  }
  return array("ok"=>FALSE, "msg"=>"Invalid query");
}

function get_proofs_and_votes($election) {
  $ret = array();
  if(isset($election["archived"]) && $election["archived"] === TRUE) {
    if(!isset($election["json"])) {
      return array("ok" => FALSE, "msg" => "Election JSON missing");
    }
    $election_json = json_decode($election["json"]);
    if($election_json === NULL) {
      return array("ok" => FALSE, "msg" => "Election JSON is invalid: " . htmlspecialchars(json_last_error_msg()));
    }

    if(isset($election["sid"])) {
      $election_sid = $election["sid"];
      $res = DB::query("SELECT votes.proof AS proof, votes.vote AS vote FROM votes WHERE votes.election=:election ORDER BY votes.proof ASC", array(":election" => $election_sid));
      if($res) {
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
          $vote_parse = parse_vote($r["vote"], $election_json);

          $new_ret = array("proof"=>$r["proof"]);

          if(isset($vote_parse["ok"])) {
            $new_ret["ok"] = $vote_parse["ok"];

            if(isset($vote_parse["msg"])) {
              $new_ret["msg"] = $vote_parse["msg"];
            } else {
              $new_ret["msg"] = "Unknown error in parse vote, message missing";
            }
            if(isset($vote_parse["html"])) {
              $new_ret["html"] = $vote_parse["html"];
            } else {
              $new_ret["html"] = "Unknown error in parse vote, html missing";
            }
            if(isset($vote_parse["raw"])) {
              $new_ret["raw"] = $vote_parse["raw"];
            } else {
              $new_ret["raw"] = "Unknown error in parse vote, raw missing";
            }

            if($vote_parse["ok"] === TRUE) {
              $new_ret["clean"] = $vote_parse["clean"];
            }
          } else {
            $new_ret["ok"] = FALSE;
            $new_ret["msg"] = "Unknown error in parse vote";
          }

          $ret[] = $new_ret;
        }
        return array("ok"=>TRUE, "res"=>$ret);
      } else {
        return array("ok"=>FALSE, "msg"=>"Database error");
      }
    }
    return array("ok"=>FALSE, "msg"=>"Invalid election passed to get proofs and votes");
  }
  return array("ok"=>FALSE, "msg"=>"Election is still running");
}

/**
 * parse_vote
 *
 * @param  string $vote_json_txt Vote to process as string
 * @param  array $election_json Array of candidates (candidates as objects)
 * @return array "ok": not spoilt, "msg": spoil checking verdict, "html": formatted vote, "clean"?: only for non-spoilt, parsed numeric values
 */
function parse_vote($vote_json_txt, $election_json) {
  //Parse vote
  $vote_json = json_decode($vote_json_txt, $assoc = TRUE);
  if($vote_json === NULL) {
    $spoil_reason = "Vote JSON is invalid: " . json_last_error_msg();
    return array("ok" => FALSE,
    "msg" => $spoil_reason,
    "html" => htmlspecialchars($vote_json_txt));
  }

  //Do not open table now, we want to add class at the end
  $res_html = "";

  //Cleaned numerical ballot
  $clean_res = array();

  $spoilt = FALSE;
  $spoil_reason = "";
  $max = 0;
  $cnt = array();

  //Check for votes for all candidates. Since JSON was generated by our code, there should be no other keys.
  foreach($election_json as $candidate) {
    $clean_res[$candidate->id] = 0;
    //Check of vote contains member
    if(isset($vote_json[$candidate->id])) {
      if(is_string($vote_json[$candidate->id])) {
        //We have a value, so it can go straight to output
        $vote_val = $vote_json[$candidate->id];
        $res_html .= "<tr><td><b>" . htmlspecialchars($candidate->name) . "</b></td><td>" . htmlspecialchars($vote_val) . "</td></tr>";
        //Pass numeric strings (+ empty)
        if(is_numeric($vote_val) || $vote_val==="") {
          //Get number value. Read as float to accept 1.0 as 1
          $vote_float = floatval($vote_val);
          $vote_int = floor($vote_float);
          $clean_res[$candidate->id] = $vote_int;
          //Fail non integers
          if($vote_float != $vote_int) {
            $spoilt = TRUE;
            $spoil_reason = "Vote for candidate " . $candidate->name . " is not an integer";
          } else {
            //To check make sure none are missed, get largest
            $max = max($max, $vote_int);
            //Reject negative
            if($vote_int < 0) {
              $spoilt = TRUE;
              $spoil_reason = "Vote for candidate " . $candidate->name . " less than 0";
            } else {
              //Duplicating 0 doesn't count as spoilt.
              //But other duplicates do
              if($vote_int > 0 && isset($cnt[$vote_int])) {
                $spoilt = TRUE;
                $spoil_reason = "Number " . $vote_int . " is repeated";
              } else {
                $cnt[$vote_int] = TRUE;
              }
            }
          }
        } else {
          $spoilt = TRUE;
          $spoil_reason = "Vote for candidate " . $candidate->name . " is not a number";
        }
      } else {
        $res_html .= "<tr><td><b>" . htmlspecialchars($candidate->name) . "</b></td><td>Vote not string</td></tr>";
      }
    } else {
      $res_html .= "<tr><td><b>" . htmlspecialchars($candidate->name) . "</b></td><td>Vote missing</td></tr>";
    }
  }

  //Check for no gaps (between 1 and max)

  if($max < 1) {
    $spoilt = TRUE;
    $spoil_reason = "Ballot is empty";
  }

  for($i = 1; $i <= $max; $i++) {
    if(!isset($cnt[$i])) {
      $spoilt = TRUE;
      $spoil_reason = "Number " . $i . " is missing";
      break;
    }
  }

  //Create table
  $res_html = (($spoilt) ? ("<table class=\"ballot ballot_spoilt\">") : ("<table>")) . $res_html . "</table>";

  //Only return clean for non-spoilt ballots
  if(!$spoilt) {
    return array("ok"=>TRUE, "msg" => "Vote is valid", "html" => $res_html, "raw" => $vote_json, "clean" => $clean_res);
  }
  return array("ok"=>FALSE, "msg" => $spoil_reason, "html" => $res_html, "raw" => $vote_json);
}
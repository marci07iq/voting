<?php 
require_once __DIR__ . "/_includes/_db.php";
require_once __DIR__ . "/_includes/_voting.php";

//All functions in this file trust eachother. No error checking.

const CANDIDATE_RUNNING = 0;
const CANDIDATE_WON = 1;
const CANDIDATE_ELIMINATED = -1;

function _divRoundUp($a, $b) {
  $res = 0;
  $res = intdiv($a, $b);
  if($res*$b != $a) {
    $res++;
  }
}

function _vote_top_choice($candidates, $vote) {
  $top_id = NULL;
  $top_val = NULL;

  foreach($vote as $candidate_id => $candidate_val) {
    if(($top_id === NULL || (0 < $candidate_val && $candidate_val < $top_val)) && $candidates[$candidate_id] === CANDIDATE_RUNNING) {
      $top_id = $candidate_id;
      $top_val = $candidate_val;
    }
  }

  return $top_id;
}

function tally_STV($votes, $election_json, $winners = 1) {
  //All votes are stored times 100, to be integer

  //Initialize candidates
  //Convert to associative arrays
  $candidate_names = array();
  $candidates = array();
  $candidate_votes = array();
  foreach($election_json as $candidate) {
    $candidate_names[$candidate->id] = $candidate->name;
    $candidates[$candidate->id] = CANDIDATE_RUNNING;
    $candidate_votes[$candidate->id] = 0;
  }
  
  $good_votes = array();
  $total_valid_vote = 0;

  //FILTER (Remove spoilt)
  $filter_stage = array(
    "accepted"=>array(),
    "spoilt"=>array()
  );

  //F 2 1 2 (Partially)
  foreach($votes as $vote) {
    //Vote is valid
    if($vote["ok"] === TRUE && isset($vote["clean"])) {
      $filter_stage["accepted"][] = $vote["proof"];
      $good_votes[$vote["proof"]] = $vote["clean"];
      $total_valid_vote+=100;
    } else {
      $filter_stage["spoilt"][] = $vote["proof"];
    }
  }

  //F 2 1 6
  $quota = _divRoundUp($total_valid_vote, $winners+1);

  //FIRST STAGE
  foreach($good_votes as $proof => $gvote) {
    $top_id = _vote_top_choice($candidates, $gvote);
  }
}

//Produce HTML election summary
function tally_election($election_id, $winners = 1) {
  $election_res = get_election_by_id($election_id);

  if(isset($election_res["ok"]) && $election_res["ok"] === TRUE) {
    $proofs_and_votes = get_proofs_and_votes($election_res);

    if(isset($proofs_and_votes["ok"]) && $proofs_and_votes["ok"] === TRUE) {

      if(!isset($election_res["json"])) {
        return array("ok" => FALSE, "msg" => "Election JSON missing");
      }
      $election_json = json_decode($election_res["json"]);
      if($election_res === NULL) {
        return array("ok" => FALSE, "msg" => "Election JSON is invalid: " . htmlspecialchars(json_last_error_msg()));
      }

      tally_STV($proofs_and_votes["res"], $election_json, $winners);
      
    }
  }
  return array("ok"=>FALSE, "msg"=>"Invalid election passed to get proofs and votes");
}
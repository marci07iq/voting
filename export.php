<?php
require_once __DIR__ . "/_voting.php";

function export_votes($election_id) {
  

  $election_res = get_election_by_id($election_id);

  if(isset($election_res["ok"]) && $election_res["ok"] === TRUE && $election_res["archived"] === TRUE) {
    $proofs_and_votes = get_proofs_and_votes($election_res);

    if(isset($proofs_and_votes["ok"]) && $proofs_and_votes["ok"] === TRUE) {
      $res = array();
      foreach($proofs_and_votes["res"] as $vote) {
        $res[] = $vote["raw"];
      }
      return $res;
    }
  }
  return array();
}

if(isset($_REQUEST["election"]) && is_string($_REQUEST["election"])) {
  print(json_encode(export_votes($_REQUEST["election"])));
}
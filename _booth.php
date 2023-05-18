<?php
function print_booth($token_check) {
  $json = json_decode($token_check["json"]);
  //echo $token_check["json"];
  if($json === NULL) {
    return array("ok" => FALSE, "msg" => "Election data invalid: " . json_last_error_msg());
  }

  ?>

  <p>
    You must rank candidates, using increasing integers starting from 1.</br>
    So 1 is your most preferred candidate, 2 is your second choice, ... etc.</br>
    You don't need to rank all candidates, if you are indifferent towards the remaining candidates, leave them empty, or use a 0.</br>
    You must not skip or repeat numbers except for 0.</br>
    </br>
    Numbers should be written as unsigned decimal integers, however other representations where the intent is clear may be accepted</br>
    </br>
    You are able to cast a ballot that does not follow these rules, however during counting they will be marked as spoilt, and not counted.</br>
    </br>
  </p>

  <form method="POST" action="index.php" id="votingbooth" onsubmit='return booth_submit();'>
    <h2><?php echo htmlspecialchars($token_check["name"]) ?></h2>
    <p><?php echo htmlspecialchars($token_check["description"]) ?></p>
    <input type="hidden" name="election" value="<?php echo htmlspecialchars($token_check["id"]) ?>"/>
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_REQUEST["token"]) ?>"/>

    <div id="candidates">
    <?php foreach($json as $candidate) {
      ?>
      <div class="candidate">
        <input class="preference" type="number" step="1" min="0" name="vote[<?php echo htmlspecialchars($candidate->id) ?>]" id="vote_<?php echo htmlspecialchars($candidate->id) ?>"/>
        <label for="vote_<?php echo htmlspecialchars($candidate->id) ?>" class="name"><?php echo htmlspecialchars($candidate->name)?></label>
      </div>
      <?php
    }
    ?>
    </div>

    <script>
      function booth_submit() {
        let res = false;
        try {
          res = confirm(check_vote() + "\nAre you sure you want to cast your vote?");
        } catch(err) {
          res = confirm("We were unable to check your ballot.\nAre you sure you want to cast your vote?");
        }
        return res;
      }

      function check_vote() {
        let prefs = document.querySelectorAll("#votingbooth input.preference");

        let pref_cnt = {};

        let vote_cnt = 0;

        let max = 0;

        let soft_msg = "Your ballot appears to be valid";

        for(let pi = 0; pi < prefs.length; pi++) {
          let pref = prefs[pi];
          let pref_val = Number(pref.value);
          if(pref_val != NaN) {
            if(!(RegExp("^([1-9]\\d*)?\\d?$").test(pref.value))) {
              soft_msg = "Note: Vote for " + pref.labels[0].innerText + " is not in a standard format, but appears to be valid";
            }
            max = Math.max(max, pref_val);

            if(!Number.isInteger(pref_val)) {
              return "Vote for " + pref.labels[0].innerText + " is not an integer";
            }
            if(pref_val < 0) {
              return "Vote for " + pref.labels[0].innerText + " is less than 0";
            }
            if(pref_val in pref_cnt) {
              if(pref_val > 0) {
                return "Number " + pref_val + " is repeated";
              }
            } else {
              pref_cnt[pref_val]=1;
            }
            if(pref_val > 0) {
              ++vote_cnt;
            }
          } else {
            return "Vote for " + pref.labels[0].innerText + " is not a number";
          }
        }

        for(let i = 1; i <= max; i++) {
          if(!(i in pref_cnt)) {
            return "Number " + i + " is skipped";
          }
        }

        if(vote_cnt == 0) {
          soft_msg = "Note: Your ballot paper doesn't contain any votes";
        }

        return soft_msg;
      }
    </script>

    </br>
    <input type="hidden" name="submit"/>
    <noscript>Warning: Your browser doesn't support JavaScript. You can still cast your vote, however we can't help you check if it is valid.</br></noscript>
    <input type="submit" value="Cast my vote"/>
  </form>

  <?php

  return array("ok" => TRUE);
}
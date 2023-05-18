<?php
include __DIR__ . "/_includes/_config.php";
?>

<h2>FAQ</h2>

<h3>Where do I vote?</h3>

<?php
if(isset($_REQUEST["token"]) || isset($_REQUEST["ntoken"])) {
  $token = ((isset($_REQUEST["token"])) ? ($_REQUEST["token"]) : ((isset($_REQUEST["ntoken"])) ? ($_REQUEST["ntoken"]) : ""));
  ?>
<a href="/?token=<?php echo htmlspecialchars(urlencode($token)); ?>">Click here</a> to go to the list of elections you can vote in.
<?php } else { ?>
You currently haven't logged in with a token. When an election is announced, please check your email (and spam) for a voting link, which includes your token.<br/>
<form metod="GET" action="/">
  You can also enter your token here:
  <input type="password" id="token" name="token" value=""><br>
  <input type="submit" value="Login">
</form>
<?php } ?>

<h3><a id="validvote_single"></a>What is a valid vote? - Single choice</h3>

You must choose a single option.
If you submit a ballot with zero or multiple options selected, the system may accept it, however it will not be counted.

<h3><a id="validvote_ranked"></a>What is a valid vote? - Ranked election</h3>

You must rank candidates, using increasing integers starting from 1.</br>
So 1 is your most preferred candidate, 2 is your second choice, ... etc.</br>
You don't need to rank all candidates, if you are indifferent towards the remaining candidates, leave them empty, or use a 0.</br>
You must not skip or repeat numbers except for 0.</br>
</br>
Numbers should be written as unsigned decimal integers. (Only digits 0123456789, not starting with a 0, unless the number is 0.</br>
Other representations may be interpreted at the discretion of the returning officer if the intent is clear.</br>
</br>
You are able to cast a ballot that does not follow these rules, however during counting they will be marked as spoilt, and not counted.</br>
</br>

<h3>What is a token?</h3>

Tokens are how we maintain the electoral register. Everyone is issued with one token, which puts you on the electoral register for all running elections.</br>
You can then use your token to cast a single vote in each of the elections.</br>
You don't have to vote in all elections you are eligible for, or in any specific order.

<h3>How do I get my token?</h3>

When an election is announced, you will get an email with your voting link. This link will look something like this:</br>
https://<?php echo htmlspecialchars($CONFIG_url); ?>?token=&ltSome letters and numbers&gt</br>
This link contains your token, and clicking it will take you straight to voting.</br>
</br>
Due to reasons out of our control, this email may go to spam. Please check there too!</br>

<h3>What if I didn't get my token?</h3>

First, please check your spam folder. If you can't find it there, please contact the administrator <?php echo htmlspecialchars($CONFIG_webmaster_name); ?> at <a href="mailto:<?php echo htmlspecialchars($CONFIG_webmaster_email); ?>"><?php echo htmlspecialchars($CONFIG_webmaster_email); ?></a> to send it again.</br>

<h3><a id="verify"></a>How can I verify my vote?</h3>

When you vote, you are given a 12 digit random proof. If you want, you can write this down.</br>
On the election page, you can find an up-to-date list of all proofs that have been submitted to that election.</br>
When polling closes, this list will also show the ballot paper associated with each proof. You can then check that your ballot paper is indeed shown there next to your proof.</br>
Publishing the list of ballots also allows anyone to perform the count, further increasing security.

<h3>Does this mean people will know how I voted?</h3>

No. Your proof is completely random, and not tied to your token.</br>
We use two databases, to separate the contents of the ballot box (votes and proofs), and the electoral register (tokens, and whether they have been used)</br>
After polls close, the link between tokens and emails is also destroyed.</br>
However, if you would like to convince yourself of your anonimity, feel free to swap tokens with trusted friends.</br>
Please note, sending someone your token will allow them to vote on your behalf. If they don't give you their valid token for you to vote with, there is nothing we can do.</br>

<h3>Can I change my vote?</h3>

No. Once your vote is cast, we independently update two lists: we mark that you have voted, and we add your vote to the list of all votes.
Neither list contains a timestamp, or any other information that can be used to connect your identity with your vote.
Since it is impossible to trace which cast vote is yours, it is not possible to remove or edit it.

<h3>My question is not listed here?</h3>

Please contact the returning officer, <?php echo htmlspecialchars($CONFIG_webmaster_name); ?> at <a href="mailto:<?php echo htmlspecialchars($CONFIG_webmaster_email); ?>"><?php echo htmlspecialchars($CONFIG_webmaster_email); ?></a> with any questions you may have.

<h3>What system is this?</h3>

This system was developed back in 2020 to allow the Merton JCR Executive Committee elections to take place online during Covid. It was written by Marcell Szakaly, the JCR Returning Officer at the time, and has since been slightly modified.</br>
To be as transparent as possible, the code is released open source under the MIT license at TODO.
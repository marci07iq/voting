## A simple trustable e-voting system

This voting system is designed to be easy to use, while providing the maximum amount of trust within reasonable constaints.

### Security
There are many schemes for conducting cryptographically verifieable online voting. While these systems offer superioir security, they are not easy for most end-users to use or gain confidence in.
This is particularly true for ranked voting systems, as they can't rely on aggregate cryptographic schemes.

This system offers a reasonble trust, while still being easy to use and easy to understand.

#### Threat model
We assume an attacker who may be involed in the administration of the election process, or equivalently has gained access to the election systems.
We assume that their attack is only successful, if the outcome of the election is effected in a stealthy way.
Denial of service is not considered to be a successful attack.

We seek to provide users assurance that if the election was conducted, it's results have not been tampered with.

#### Voting flow
- The election administrator sets up the ballot questions
- The administrator obtains a list of email addresses for all eligible voters, and generates a unique random token for each of them
- The administrators adds the list of tokens to the electoral register
- The administrator emails the tokens to the voters, as `?token=` link
- The administrator opens the elections
- Users vote following these steps:
  - They click their own voting link
  - They choose the election they wish to vote in
  - They fill out their ballot
  - The system stores their ballot, invalidates their voting token, and generates a proof id
  - The proof id is displayed to the user
- The list of proof ids cast in the election is displayed while polls are open
- The administrator closes polls
- The system now displays all proofs cast, next to the content of the ballot paper
- Anyone can use the list of all cast votes to perform the count themselves.

#### Ballot modification/removal attack
A user may be concerened that their ballot was not counted, or modified.

When submitting a vote, it is given a random proof id, which is given to the user.
Once polls close, all proofs are displayed next to the contents of the ballot.
The user can verify that their ballot is displayed next to their proof.

#### Ballot stuffing
A user may be concerned that additional ballots were cast.

The system maintains a list of which eligible voters did and did not vote.
The number of voters who voted must be equal to the number of votes cast.

The list of people who did and did not vote can be provided to a trusted auditor.
They can determine if the number of votes cast does not equal the number of participants who voted, and they can check if someone who did not vote is listed as having voted.

#### Miscounting
A voter may be concerned that the count was incorrectly performed.

All cast votes are published once polls close. Any user can perform the count themselves. The system allows the data to be downloaded in a machine readable format, to assist automated counting.

### End user experience
For end-users, the experience is the following:
- Get an email with a voting link
- Click personalized link
- Cast your vote
- Done.

### Future features
- Hybrid elections, with vote casting both online and in-person

## Setup
This section is for system administrators. We assume an Apache/PHP server is installed. The election website has no administrator user interface, all configuration is done through the backend SQL server.
Create an SQL database and user with permission to it. Place the required information in `_includes/_config.php`.

Create tables:
```sql
 CREATE TABLE `elections` (
  `sid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `json` text NOT NULL,
  `ranked` tinyint(1) NOT NULL,
  `open` tinyint(1) NOT NULL DEFAULT '0',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`),
  UNIQUE KEY `id` (`id`)
);

 CREATE TABLE `regkeys` (
  `token` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `reg_key` varchar(255) NOT NULL
);

CREATE TABLE `tokens` (
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `election` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  UNIQUE KEY `uniqueelection` (`token`,`election`),
  KEY `election` (`election`),
  CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`election`) REFERENCES `elections` (`sid`)
);

CREATE TABLE `votes` (
  `vote` text NOT NULL,
  `proof` varchar(15) NOT NULL,
  `election` int(10) unsigned NOT NULL,
  UNIQUE KEY `proof` (`proof`),
  KEY `election` (`election`),
  CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`election`) REFERENCES `elections` (`sid`)
);
```

Set up an election
```sql
INSERT INTO `elections` (`id`, `name`, `description`, `json`, `ranked`, `open`, `archived`) VALUES ("test_choice", "Test choice", "Test of a single choice election", '[{"id":"YE","name":"For"}, {"id":"NA","name":"Agains"}, {"id":"AB","name":"Abstain"}]', False, False, False);

INSERT INTO `elections` (`id`, `name`, `description`, `json`, `ranked`, `open`, `archived`) VALUES ("test_ranked", "Test ranked", "Test of a ranked voting", '[{"id":"A","name":"Alice"}, {"id":"B","name":"Bob"}, {"id":"C","name":"Charlie"}]', True, False, False);
```

Check the ID assigned to the elections:
```sql
SELECT `sid`, `id`, `name` from `elections`;
```

Offline generate voting tokens, and inject them into the system:
```sql
INSERT INTO `tokens` (`election`, `token`) VALUES (TODO, TODO);
```

Open the election:
```sql
UPDATE `elections` SET `open`=True WHERE `id`=TODO;
```

Close the election:
```sql
UPDATE `elections` SET `open`=False, `archived`=False WHERE `id`=TODO;
```

## Downloading data
For machine-aided verification, go to `/export.php?election=`. You will be given the stored JSON of every ballot paper.
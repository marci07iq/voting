<?php

require_once __DIR__ . "/../_includes/_db.php";
require_once __DIR__ . "/../_includes/_mail.php";

function createElection()
{
    print("  Create election\n");
    print("===================\n");
    $key = readline("Key: ");
    $name = readline("Name: ");
    $desc = readline("Description: ");
    $ranked = readline("Ranked [0/1]: ");

    $options = [];
    do {
        print("Add option\n");
        $opt_key = readline("Option key: ");
        $opt_name = readline("Option name: ");

        $option = array(
            "id" => $opt_key,
            "name" => $opt_name
        );

        $options[] = $option;
    } while (readline("More options [0/1]?") == "1");

    if (readline("Accept? [0/1]") == "1") {
        DB::query("INSERT INTO `elections` (`id`, `name`, `description`, `json`, `ranked`, `open`, `archived`) VALUES (:key, :name, :desc, :json, :ranked, False, False);", array(
            ":key" => $key,
            ":name" => $name,
            ":desc" => $desc,
            ":json" => json_encode($options),
            ":ranked" => $ranked === "1"
        ));
        print("Added.\n");
    }
}

function editElection()
{
    print("  Open/Close election\n");
    print("=======================\n");
    $key = readline("Key: ");
    $stage = readline("Stage [0=closed, 1=open, 2=done]: ");

    $open = False;
    $archived = False;

    switch ($stage) {
        case "0":
            print("Setting as closed. ");
            $open = False;
            $archived = False;
            break;
        case "1":
            print("Setting as open. ");
            $open = True;
            $archived = False;
            break;
        case "2":
            print("Setting as finished. ");
            $open = False;
            $archived = True;
            break;
        default:
            print("Invalid option");
            return;
    }

    if (readline("Accept? [0/1]") == "1") {
        DB::query("UPDATE `elections` SET `open`=:open, `archived`=:archived WHERE `id`=:key", array(
            ":key" => $key,
            ":open" => $open,
            ":archived" => $archived
        ));
        print("Changed.\n");
    }
}

function enrolTokens($tokens, $elections)
{
    foreach ($elections as $election) {
        $res = DB::query("SELECT sid FROM elections WHERE id=:id", array(":id" => $election));
        if ($res->rowCount() === 1) {

            $r = $res->fetch(PDO::FETCH_ASSOC);

            foreach ($tokens as $token) {
                DB::query("INSERT INTO `tokens` (`election`, `token`) VALUES (:election, :token)", array(
                    ":election" => $r["sid"],
                    ":token" => $token
                ));
            }
        }
        else {
            print("Unknown election " . $election . "\n");
        }
    }
}

function keygenHelper($N)
{
    $res = [];
    $idx = 0;
    for ($idx = 0; $idx < $N; $idx++) {
        $res[] = randomBase64(12);
    }

    return $res;
}

function tokenEmail($emails, $tokens)
{
    if (count($emails) !== count($tokens)) {
        print("List length mismatch\n");
        return False;
    }

    if (readline("Send emails to " . strval(count($tokens)) . " people [0/1]? ") !== "1") {
        return False;
    }

    $idx = 0;
    for ($idx = 0; $idx < count($tokens); $idx++) {
        print("Sending to " . $emails[$idx] . "... ");
        $res = sendMail(
            $emails[$idx],
            Config::MAIL_REGISTER_SUBJECT,
            sprintf(Config::MAIL_REGISTER_BODY, $tokens[$idx]));
        if ($res) {
            print(" OK\n");
        } else {
            print(" FAILED!\n");
        }
        sleep(1);
    }
    return True;
}

function login()
{
    //Prevent script from being ran non-interactively
    $password = randomBase10(9);
    print("OTP: " . $password . "\n");
    if (readline("OTP? ") !== $password) {
        exit();
        die();
    }
/*if(hash("sha256", readline("Password? ")) !== "5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8") {
 exit();
 die();
 }*/
}

function main()
{
    login();

    $elections = [];
    $emails = [];
    $tokens = [];

    do {
        print("\n\n");
        print("Working set: " . strval(count($elections)) . " elections, " . strval(count($emails)) . " emails, " . strval(count($tokens)) . " tokens.\n");
        print("Select operation:\n");
        print(" 0 - Exit\n");

        print(" 10 - Create election\n");
        print(" 11 - Open/close election\n");
        print(" 12 - Query register\n");
        print(" 13 - Send emails\n");
        print(" 14 - Add to register\n");

        print(" 20 - Add working elections\n");
        print(" 21 - Add working emails\n");
        print(" 22 - Add working tokens (upload)\n");
        print(" 23 - Add working tokens (rng)\n");

        print(" 30 - Show working elections\n");
        print(" 31 - Show working emails\n");
        print(" 32 - Show working tokens\n");

        print(" 40 - Reset working elections\n");
        print(" 41 - Reset working emails\n");
        print(" 42 - Reset working tokens\n");
        $op = intval(readline("Select operation: "));

        switch ($op) {
            case 0:
                return;
                break;

            case 10:
                createElection();
                break;
            case 11:
                editElection();
                break;
            case 13:
                tokenEmail($emails, $tokens);
                break;
            case 14:
                enrolTokens($tokens, $elections);
                break;

            case 20:
                $elections_str = readline("Elections ids (comma separated): ");
                $elections = array_merge($elections, explode(",", $elections_str));
                break;
            case 21:
                $emails_str = readline("Emails (comma separated): ");
                $emails = array_merge($emails, explode(",", $emails_str));
                break;
            case 22:
                $tokens_str = readline("Tokens (comma separated): ");
                $tokens = array_merge($tokens, explode(",", $tokens_str));
                break;
            case 23:
                $tokens_cnt = intval(readline("Token count: "));
                $tokens = array_merge($tokens, keygenHelper($tokens_cnt));
                break;

            case 30:
                print("Current election ids:\n");
                print(implode(",", $elections));
                break;
            case 31:
                print("Current emails:\n");
                print(implode(",", $emails));
                break;
            case 32:
                print("Current tokens:\n");
                print(implode(",", $tokens));
                break;

            case 40:
                if (readline("Reset working election ids [0/1]? \n") === "1") {
                    $elections = [];
                    print("Deleted working elections");
                }
                break;
            case 41:
                if (readline("Reset working emails [0/1]? \n") === "1") {
                    $emails = [];
                    print("Deleted working emails");
                }
                break;
            case 42:
                if (readline("Reset working tokens [0/1]? \n") === "1") {
                    $tokens = [];
                    print("Deleted working tokens");
                }
                break;

            default:
                print("Unknown option\n");
                break;
        }

    } while (True);
}

if(php_sapi_name() == "cli") {
    main();
}

?>
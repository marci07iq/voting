<?php
require_once __DIR__ . "/_includes/_config.php";
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo htmlspecialchars(Config::SITE_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
      @media only screen and (max-width: 900px) {
        body {
          background-color: #ffffff;
        }

        .content {
          padding: 10px;
        }
      }

      @media only screen and (min-width: 900px) {
        body {
          background-color: lightblue;
        }

        .content {
          padding: 20px;
        }

        .content_container {
          overflow: hidden;

          -webkit-box-shadow: 5px 10px 10px 0px rgba(0,0,0,0.5);
          -moz-box-shadow: 5px 10px 10px 0px rgba(0,0,0,0.5);
          box-shadow: 5px 10px 10px 0px rgba(0,0,0,0.5);
          border-radius: 10px;  

          width: 860px;

          margin-left: auto;
          margin-right: auto;
          margin-top: 20px;
        }
      }

      .content_container {
        padding: 0px;
        background: #ffffff;
      }

      .alert_container {
        background:lightcoral;
      }

      .alert {
        width: 100%;
        padding: 10px;
      }

      td {
        padding: 5px 10px 5px 10px;
      }

      p {
        margin-top: 0px;
      }

      table.sparse tr {
        display:block;
        padding: 10px;
        border-radius: 10px;
        
        -webkit-box-shadow: 5px 10px 10px 0px rgba(0,0,0,0.5);
        -moz-box-shadow: 5px 10px 10px 0px rgba(0,0,0,0.5);
        box-shadow: 5px 10px 10px 0px rgba(0,0,0,0.5);
        margin-bottom: 20px;
      }

      tr {  
        padding: 5px;
      }

      tr:nth-child(odd) {
        background: #f0f0f0;
      }

      tr:nth-child(even) {
        background: #e0e0e0;
      }

      table.ballot_spoilt tr:nth-child(odd) {
        background: #ffe0e0;
      }

      table.ballot_spoilt tr:nth-child(even) {
        background: #ffd0d0;
      }

      table {
        width: 100%;
        margin-top: 20px;
      }

      table.ballot {
        margin-top: 0px;
      }

      * {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 14pt;
      }

      input[type="number"] {
        width: 50px;
      }

      body {
        padding: 0px;
        margin: 0px;
      }

      input[type="radio"] {
        height: 1em;
        width: 1em
      }
    </style>
  </head>
  <body>
    <div class="content_container">
    <?php
        require_once __DIR__ . "/_includes/_message.php";

        //Messages::error("This website is being updated. Please expect bugs.");

        //Generate errors (and page)
        ob_start();
        try {
          $onError = function ($level, $message, $file, $line) {
            throw new ErrorException($message, 0, $level, $file, $line);
          };
          set_error_handler($onError);

          //echo "Test";

          if(isset($_REQUEST["faq"])) {
            include __DIR__ . "/_help_content.php";
          } else if(isset($_REQUEST["register_key"])) {
            include __DIR__ . "/_register_content.php";
          } else if(isset($_REQUEST["submit"])) {
            include __DIR__ . "/_submit_content.php";
          } else {
            include __DIR__ . "/_index_content.php";
          }

          $ob = ob_get_clean();
        } catch (Throwable $throwable) {
          Messages::error("Error: " . $throwable->getMessage());
          ob_end_clean();
          $ob = "A fatal error has occured.";
        } finally {
          restore_error_handler();
        }

      ?>
        <div class="alert_container" id="alert_container">
          <?php 
          foreach(Messages::$errors as $error) {
            echo '<div class="static_alert alert" role="alert">' . htmlspecialchars($error) . '</div>';
          }?>
        </div>

    <div class="content">
      
        <script>
          function addalert(msg) {
            //Delete all dynamic alerts
            document.querySelectorAll("#alert_container .dynamic_alert").forEach(e => e.parentNode.removeChild(e));

            //Add new
            let ndiv = document.createElement("div");
            ndiv.innerText = msg;
            ndiv.classList.add("dynamic_alert");
            document.getElementById("alert_container").appendChild(ndiv);
            ndiv.setAttribute("role", "alert");
          }
        </script>

        <?php
          $my_token = NULL;
          if(isset($_REQUEST["token"]) || isset($_REQUEST["ntoken"])) {
            $my_token = ((isset($_REQUEST["token"])) ? ($_REQUEST["token"]) : ((isset($_REQUEST["ntoken"])) ? ($_REQUEST["ntoken"]) : ""));
          }
          ?>
          <p>
            <a href="/index.php<?php if(is_string($my_token)) echo ("?ntoken=" . htmlspecialchars(urlencode($my_token))); ?>">Go to all elections</a>&emsp;
            <?php if(is_string($my_token)) { ?>
            <a href="/index.php<?php echo ("?token=" . htmlspecialchars(urlencode($my_token))); ?>">Go to my elections</a>&emsp;
            <?php } ?>
            <a href="/index.php?faq<?php if(is_string($my_token)) echo ("&ntoken=" . htmlspecialchars(urlencode($my_token))); ?>">Help</a>&emsp;
            <?php
            if(isset($_REQUEST["token"]) || isset($_REQUEST["ntoken"])) {
            ?>
              <a href="/index.php">Logout</a>&emsp;
            <?php
            }
            ?>
          </p>
      <?php
        echo $ob;
      ?>
    </div>
    </div>
  </body>
</html>
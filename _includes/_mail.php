<?php

//from_name: Part before @ (not included)
function sendMail($to, $subject, $text, $from_name = "elections", $from_display = "JCR Elections")
{
  //Just in case user gives invalid address
  $full_from = (explode("@", $from_name)[0]) . "@mertonjcr.org";

  $headers = "From: " . $from_display . " <" . $full_from . ">\r\n";
  $headers .= "Reply-To: jcr.it@merton.ox.ac.uk\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";

  return mail($to, $subject, $text, $headers);
}

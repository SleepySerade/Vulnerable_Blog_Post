<?php
function generate_token() {
    
    if(!isset($_SESSION["csrf_token"])) {
        
        $token = hash("sha256",uniqid(rand(), TRUE));
        $_SESSION["csrf_token"] = $token;
        $_SESSION['token_time'] = time();
    } else {
        
        $token = $_SESSION["csrf_token"];
        $_SESSION['token_time'] = time();
    }
    return $token;
  }
  ?>
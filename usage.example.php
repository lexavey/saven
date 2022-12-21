<?php 
function mailMe($id,$subject,$message){
    $subject = rawurlencode(base64_encode($subject));
    $message = rawurlencode(base64_encode($message));
    $url = "http://localhost:1123";
    if($send = file_get_contents("$url/?app=$id&subject_encoded=$subject&message_encoded=$message")){
        if($send == 'ok'){
            echo "Success send message";
        }else{
            die("server failed");
        }
    }else{
        die("cannot send message");
    }
}
mailMe("sams","Hello World","<h1>A message</h1>");
    
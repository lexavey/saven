<?php 
function mailMe($id,$subject,$message){
    $subject = rawurlencode(base64_encode($subject));
    $message = rawurlencode(base64_encode($message));
    $url = "http://localhost:1123";
    if($send = file_get_contents("$url/?app=$id&subject_encoded=$subject&message_encoded=$message")){
        if($send == "ok"){
            echo "Success send message";
        }else{
            file_put_contents("failed.txt", "\nID : ".$id."\nSubject : ".$subject."\nMessage : ".$message."\n", FILE_APPEND);
            return false;
        }
    }else{
        file_put_contents("failed.txt", "\nID : ".$id."\nSubject : ".$subject."\nMessage : ".$message."\n", FILE_APPEND);
        return false;
    }
    return true;
}
mailMe("sams","Hello World","<h1>A message</h1>");
    
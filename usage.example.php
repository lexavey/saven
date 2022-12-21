<?php 
function mailMe($id,$subject,$message,$is_html = false){
    $subject = rawurlencode(base64_encode($subject));
    $message = rawurlencode(base64_encode($message));
    $url = "https://saven.obatkakirangen.com/";

    if($is_html){
        $url = "$url/?app=${id}&subject_encoded=${subject}&message_encoded=${message}&html";
    }else{
        $url = "$url/?app=${id}&subject_encoded=${subject}&message_encoded=${message}";
    }
    if($send = file_get_contents($url)){
        if($send == "ok"){
            // echo "Success send message";
            return true;
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
mailMe("YourID","Hello World","<h1>A message</h1>",true);
mailMe("YourID","Hello World","a plain \n message");
    
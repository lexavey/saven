<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require __DIR__ . '/vendor/autoload.php';
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use Google\Client;
use Google\Service\Gmail;

/**
 * Returns an authorized API client.
 * @return Client the authorized client object
 */
function getClient($tokenPath)
{
    
    $client = new Client();
    $client->setApplicationName('Saven Workers');
    $client->setScopes('https://mail.google.com/');
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
      	if(!$accessToken){
          @unlink($tokenPath);
          die("Access token error, revoke your access<br>Go to: <a href='https://myaccount.google.com/permissions?pli=1'>https://myaccount.google.com/permissions?pli=1</a>, remove the authorization to your app and run your code. You should receive the refresh token.");
        }
        // var_dump($client->setAccessToken($accessToken));
        $client->setAccessToken($accessToken);
    }
	
    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        $id = $_SESSION['id'];
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $new_token=$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            if (array_key_exists('error', $new_token)) {

                @unlink($tokenPath);

                echo "Full Error : ".join(', ', $new_token);
                echo "<br><a href='/'>Back</a>";
                exit;
            }

            // exit;
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            
            // $authCode = trim(fgets(STDIN));
            if(isset($_GET['code']) && $_GET['code'] != ''){
                $authCode=$_GET['code'];
            }else{
                echo "Your ID : ".$id." ";
                echo "<a href='?logout'>Log Out</a>";
                echo "<br>Click Here : <a href='$authUrl'>$authUrl</a>";
                echo "<form>Or enter auth code : <input type='text' name='code'></form>";
                exit;
            }

            try {
                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);
              
            }catch (Exception $e) {
        		echo 'An error occurred: ' . $e->getMessage();
        		echo "<br>";
                exit;
        	}
            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                echo "Full Error : ".join(', ', $accessToken);
                echo "<br><a href='?'>Back</a>";
                exit;
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        file_put_contents("logs.txt", $id."=>".$tokenPath."\n", FILE_APPEND);
        header('Location: /');
        return true;
        // return $client;
    }else{
        if(isset($_GET['code'])){
            header('Location: /');
            exit;
        }
        return $client;
    }
}


// Get the API client and construct the service object.

// file_put_contents('a.txt',json_encode($service));
/**
* @param $sender string sender email address
* @param $to string recipient email address
* @param $subject string email subject
* @param $messageText string email text
* @return Google_Service_Gmail_Message
*/
function createMessage($sender, $to, $subject, $messageText) {
	$message = new Google_Service_Gmail_Message();

	$rawMessageString = "From: <{$sender}>\r\n";
	$rawMessageString .= "To: <{$to}>\r\n";
	$rawMessageString .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
	$rawMessageString .= "MIME-Version: 1.0\r\n";
	$rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n";
	$rawMessageString .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
	$rawMessageString .= "{$messageText}\r\n";

	$rawMessage = strtr(base64_encode($rawMessageString), array('+' => '-', '/' => '_'));
	$message->setRaw($rawMessage);
		return $message;
}
	/**
	* @param $service Google_Service_Gmail an authorized Gmail API service instance.
	* @param $user string User's email address
	* @param $message Google_Service_Gmail_Message
	* @return Google_Service_Gmail_Draft
	*/
function createDraft($service, $user, $message) {
	$draft = new Google_Service_Gmail_Draft();
	$draft->setMessage($message);

	try {
		$draft = $service->users_drafts->create($user, $draft);
		print 'Draft ID: ' . $draft->getId();
	} catch (Exception $e) {
		print 'An error occurred: ' . $e->getMessage();
	}
	return $draft;
}
function sendMessage($service, $userId, $message) {

	try {
		$message = $service->users_messages->send($userId, $message);
        // 		print 'Message with ID: ' . $message->getId() . ' sent.';
		return $message;
	} catch (Exception $e) {
		print 'An error occurred: ' . $e->getMessage();
	}
	return null;
}


if(isset($_GET['app'])){
    $md5 = md5($_GET['app']);
    $tokenPath = "token/$md5.json";
    if(is_file($tokenPath)){
        $client = getClient($tokenPath);
        $service = new Gmail($client);
        $users = $service->users;
        $profile = $users->getProfile("me");
        $email = $profile->getEmailAddress();
        
        if(isset($_GET['subject']) && isset($_GET['message']) || isset($_GET['subject_encoded']) && isset($_GET['message_encoded'])){
            if(isset($_GET['subject']) && isset($_GET['message'])){
                $subject = $_GET['subject'];
                $message = $_GET['message'];
            }
            if(isset($_GET['subject_encoded']) && isset($_GET['message_encoded'])){
                $subject = base64_decode($_GET['subject_encoded']);
                $message = base64_decode($_GET['message_encoded']);
            }
            
            
            $create_message=createMessage($email,$email,$subject,$message);
            // createDraft($service,"me",$create_message);
            if($send = sendMessage($service,"me",$create_message)){
                echo "ok";
            }else{
                echo "error";
            }
        }else{
            die("subject or message is needed");
        }
        
    }else{
        echo "Token error";
    }
    
    
    die;
}

session_start();
if(isset($_GET['logout'])){
    session_destroy();
    header('Location: /');
}
if(isset($_GET['id']) && $_GET['id'] != ''|| isset($_SESSION['id']) && $_SESSION['id'] != ''){
    if(isset($_GET['id'])){
        $id = $_GET['id'];
        $_SESSION['id'] = $id;
    }else{
        $id = $_SESSION['id'];
    }
    
    $tokenPath = "token/".md5($id).".json";
    $client = getClient($tokenPath);
    echo "Your ID : $id";
    $service = new Gmail($client);
    $users = $service->users;
    $profile = $users->getProfile("me");
    $email = $profile->getEmailAddress();
    $access = "?app=$id&subject={Subject}&message={Message}";
    $access_enc = "?app=$id&subject_encoded={Base64 Encoded Subject}&message_encoded={Base64 Encoded Message}";
    $helloworld = "?app=$id&subject=Hello From Me&message=Lorem ipsum ".rawurlencode("<h1>dolor</h1>")." sit amet";
    $helloworld_enc = "?app=$id&subject_encoded=".rawurlencode(base64_encode("Hello From Me"))."&message_encoded=".rawurlencode(base64_encode("Lorem ipsum <h1>dolor</h1> sit amet"));
    echo "<br>Your authorized email : $email ";
    echo "<a href='?logout'>Log Out</a>";
    echo "<br>Your access url : $access";
    echo "<br>Your encoded access url : $access_enc";
    echo "<br>Try : <a href='$helloworld'>$helloworld</a>";
    echo "<br>Try : <a href='$helloworld_enc'>$helloworld_enc</a>";
    
    echo '<pre>'.htmlentities('
    <?php
    function mailMe($id,$subject,$message){
        $subject = rawurlencode(base64_encode($subject));
        $message = rawurlencode(base64_encode($message));
        $url = "https://saven.obatkakirangen.com/";
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
    mailMe("'.$id.'","Hello World","<h1>A message</h1>");
    ').'</pre>';
    
}else{
    echo "<form>ID : <input type='text' name='id'><input type='submit' value='SEND'></form>";
}

# saven

### What is this ?

You can save any message to your email without using SMTP.

Example use : `https://saven.worker.dev/?app=dev&subject=Hello%20From%20Me&message=message`

It will send to your email `myemail@gmail.com` with sender `myemail@gmail.com`

### How to use ?
    git clone https://github.com/lexavey/saven
    cd saven
    composer install
Create OAuth client ID in https://console.cloud.google.com/apis/credentials

Application type : Web application

Authorized redirect URIs : yordomain.com

in OAuth consent screen Manually add scopes `https://mail.google.com/`

Download json, and save in folder `saven` rename as `credentials.json`


RUN

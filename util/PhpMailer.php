<?php

namespace Util;

use PHPMailer\PHPMailer\PHPMailer as Mailer;
use PHPMailer\PHPMailer\Exception as PhpMailerException;

class PHPMailer 
{

    private $mailer;
    private $debugLevel = 2;

    private $defaultOptions = [
        'Host' => 'smtp.gmail.com',
        'SMTPAuth' => true,
        'Username' => 'jomino.domino@gmail.com',
        'Password' => 'jomdom64',
        'SMTPSecure' => 'tls',
        'Port' => 587
    ];
   
    public function __construct($options=[])
    {
        $this->mailer = new Mailer(true); 

        foreach ($options as $key => $value) {
            $this->defaultOptions[$key.''] = $value;
        }

    }

    public function send($from,$to,$subject,$body)
    {
        $mail = $this->mailer;

        //Server settings
        $mail->SMTPDebug = $this->debugLevel;
        $mail->isSMTP(); 

        foreach ($this->defaultOptions as $key => $value) {
            $mail->{$key} = $value;
        }

        try { 
        
            //Recipients
            $mail->setFrom($from);
            $mail->addAddress($to); 
            $mail->addReplyTo($from);
        
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        
            $mail->send();

            return true;

        } catch (PhpMailerException $e) {
            $error = $mail->ErrorInfo;
            return $error;
        }

    }

}
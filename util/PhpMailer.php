<?php

namespace Util;

use PHPMailer\PHPMailer\PHPMailer as Mailer;
use PHPMailer\PHPMailer\Exception as PhpMailerException;

class PhpMailer 
{

    private $mailer;
    private $debugLevel = 0;

    private $sender = \App\Parameters::EMAIL['username'];

    private $defaultOptions = [
        'Host' => \App\Parameters::EMAIL['host'],
        'Username' => \App\Parameters::EMAIL['username'],
        'Password' => \App\Parameters::EMAIL['password'],
        'SMTPAuth' => true,
        'SMTPSecure' => 'tls',
        'Port' => 587,
        'CharSet' => 'UTF-8'
    ];
   
    public function __construct($options=[])
    {
        $this->mailer = new Mailer(true); 

        foreach ($options as $key => $value) {
            $this->defaultOptions[$key.''] = $value;
        }

    }

    public function send($to,$subject,$body)
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
            $mail->setFrom($this->sender, 'Ipefix Service');
            $mail->addAddress($to); 
            $mail->addReplyTo($this->sender);
        
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
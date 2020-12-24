<?php
    namespace EZMAIL;   
    use SMTP\Smtp;
    use SMTP\Mail;

    /**
    * @copyright (c) Nerdtrix LLC 2020
    * @author Name: Jerry Urena
    * @author Social link:  @jerryurenaa
    * @author email: jerryurenaa@gmail.com
    * @author website: jerryurenaa.com
    * @license MIT (included with this project)
    */

    #Autoload
    spl_autoload_register(function ($className)
    {
        $fileName = sprintf("%s.php", $className);

        if (file_exists($fileName))
        {
            require ($fileName);
        }
        else
        {
            die(sprintf("Class not found %s", $fileName));
        }
    });

    
    /**
     * Example using SMTP Protocol
     */

    #Create a new instance
    $smtp = new Smtp();
    
    #Add email subject
    $smtp->subject = "test Email";

    #Add email body
    $smtp->body = "<p>This is a sample email</p>";

    #Add attachment
    #$smtp->attachment = ["name", "File URL"];

    #Add To email
    $smtp->to = ["Your name" => "myemail@example.com"];  

    #Send Email
    $confirm = $smtp->send();

    print_r($confirm);


    /**
     * Example using the Default php mail Protocol
     */
    
    /*
    #Create a new instance
    $mail = new Mail();
    
    #Add email subject
    $mail->subject = "test Email";

    #Add email body
    $mail->body = "<p>This is a sample email</p>";

    #Add attachment
    #$smtp->attachment = ["name", "File URL"];

    #Add To email
    $mail->to = ["Your name" => "myemail@example.com"]; 

    #Send Email
    $confirm = $mail->send();

    print_r($confirm);
    */
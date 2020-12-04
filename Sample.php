<?php
    namespace EZMAIL;   
    use SMTP\Smtp;

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

    
    #Create a new instance
    $smtp = new Smtp();
    
    #Add email subject
    $smtp->subject = "test Email";

    #Add email body
    $smtp->body = "<p>This is a sample email</p>";

    #Add attachment
    #$smtp->attachment = ["name", "File URL"];

    #Add To email
    $smtp->to = ["Your name" => "jerryurenaa@gmail.com"]; 

    #Send Email
    $confirm = $smtp->send();

    print_r($confirm);
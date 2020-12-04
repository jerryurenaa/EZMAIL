<?php
    namespace SMTP;

    /**
    * @copyright (c) Nerdtrix LLC 2020
    * @author Name: Jerry Urena
    * @author Social link:  @jerryurenaa
    * @author email: jerryurenaa@gmail.com
    * @author website: jerryurenaa.com
    * @license MIT (included with this project)
    */

    class Config
    {
        public $get = [
            "CHARSET" => "UTF-8", //Default charset
            "MAIL_PRIORITY" => 3, //Default value is 3
            "CONSOLE_LOG" => true, //This will print the SMTP transaction from the SEND instance.
            "APP_NAME" => "EZMAIL Protocol", //App Title
            "CONNECTION_TIMEOUT" => 20, //Default connection timeout

            /**
             * Email server configuration
             */
            "SMTP_HOST"  => "",
            "SMTP_USERNAME" => "",
            "SMTP_PORT" => 123,  
            "SMTP_PASSWORD" => ""
        ];
    }

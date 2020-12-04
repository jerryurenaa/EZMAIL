<?php
    namespace SMTP; 
    use SMTP\Config;
    use \Exception;


    /**
    * @copyright (c) Nerdtrix LLC 2020
    * @author Name: Jerry Urena
    * @author Social link:  @jerryurenaa
    * @author email: jerryurenaa@gmail.com
    * @author website: jerryurenaa.com
    * @license MIT (included with this project)
    */


    class Smtp
    {
        public 
            $subject, #email subject
            $body, #Plain text or html 
            $replyTo, #empty by default     
            $from = [], #[Name => Email] : Optional
            $to = [], #[Name => Email] : Required
            $attachment = [], #[Name => file path] : optional  
            $cc = [], #empty by default
            $bcc = []; #empty by default

        #Helper strings
        private $smtp, $data = null;       
        
        #Config instance
        private $config;


        #Constructor
        public function __construct()
        {
            #Initialize config
            $this->config = new Config();

            #Initialize SMTP connection.
            $this->connect();
        }

        #Destructor
        public function __destruct()
        {
          #Disconnect
          fclose($this->smtp);
        }
        
        
        /**
         * @method Connect
         * This is the handshake process
         */
        private function connect()
        {
            try
            {
                #Connection
                $connection = fsockopen(
                    $this->config->get["SMTP_HOST"], 
                    $this->config->get["SMTP_PORT"], 
                    $errno, 
                    $errstr, 
                    $this->config->get["CONNECTION_TIMEOUT"]
                );

                if(empty($connection))
                {
                    throw new Exception("$errstr ($errno)");
                }

                #Connection response
                $response = fgets($connection, 512); 

                $responseCode = (int) substr($response, 0, 3);

                #Validate response
                if($responseCode !== 220)
                {
                    throw new Exception($response);
                }

                $this->smtp = $connection;
                
                #Handshake
                $this->sendCommand("HELO", 250);
            
                #secure
                $this->sendCommand("STARTTLS", 220);

                if(!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) 
                {
                    throw new Exception("Failed to start TLS");
                }

                #Encrypted Handshake
                $this->sendCommand("HELO", 250);
                
                #Auth login using username and password
                $this->sendCommand("AUTH LOGIN", 334);
                $this->sendCommand(base64_encode($this->config->get["SMTP_USERNAME"]), 334);
                $this->sendCommand(base64_encode($this->config->get["SMTP_PASSWORD"]), 235);
            }
            catch(Exception $ex)
            {
                throw new Exception($ex);
            }
        }


        /**
         * @method send
         * @return string
         * @throws exceptions
         * 
         * @before attempting to send an email you must first set the
         * required strings to prevent connection errors.
         */
        public function send()
        {
            if(empty($this->subject) || empty($this->body) || empty($this->to))
            {
                return "subject, body and to strings are requried to send an email";
            }

            $this->sendCommand(sprintf("MAIL FROM: <%s>", $this->config->get["SMTP_USERNAME"]), 250);

            foreach($this->to as $name => $email)
            {
                $this->sendCommand("RCPT TO: <{$email}>", 250);
            }

            $this->sendCommand("DATA", 354);
    
            #Create mail string
            $this->createData();

            $this->sendCommand($this->data, 250);

            return "Email sent successfully";
        }


        /**
         * @method createData
         * STOP :: WARNING :: before modifying this file 
         * you must read and understant how mime works.
         */
        private function createData()
        {
            #Email header
            $this->addString(["MIME-Version" => "1.0"]);
            $this->addString(["X-PoweredBy" => $this->config->get["APP_NAME"]]);
            $this->addString(["X-Mailer" => $this->config->get["APP_NAME"]]);
            $this->addString(["Date" => date('r')]);
            $this->addString(["X-Priority" => $this->config->get["MAIL_PRIORITY"]]);  
            $this->addString(["Subject" => $this->subject], 1, true);
            $this->addString(["Return-Path" => $this->config->get["SMTP_USERNAME"]]);
        
            #Default value
            if(empty($this->from))
            {
                $this->from = [$this->config->get["APP_NAME"] => $this->config->get["SMTP_USERNAME"]];
            }

            $this->addString(["From" => sprintf("%s <%s>", key($this->from), end($this->from))]);
            $this->addString(["Message-ID" => sprintf("<%s.%s>", md5(uniqid()),  end($this->from))]);
            
            #To Header 
            $tostring = null;
            foreach ($this->to as $toName => $toEmail) 
            {
                if(!empty($toName))
                {
                    $toName = $this->encodeString($toName);
                }

                $tostring .=  "{$toName}<{$toEmail}>,";
            }

            #Remove the last comma
            $tostring = rtrim($tostring, ",");

            $this->addString(["To" => $tostring]);


            #CC Header 
            $ccString = null;
            foreach ($this->cc as $ccName => $ccEmail) 
            {
                if(!empty($ccName))
                {
                    $ccName = $this->encodeString($ccName); 
                }

                $ccString .=  "{$ccName} <{$ccEmail}>,";
            }

            #Remove the last comma
            $ccString = rtrim($ccString, ",");

            $this->addString(["Cc" => $ccString]);


            #BCC Header 
            $bccstring = null;
            foreach ($this->bcc as $bccName => $bccEmail) 
            {
                if(!empty($bccName))
                {
                    $bccName = $this->encodeString($bccName);
                }

                $bccstring .=  "{$bccName} <{$bccEmail}>,";
            }

            #Remove the last comma
            $ccString = rtrim($ccString, ",");

            $this->addString(["Bcc" => $ccString]);


            #Reply to
            if(empty($this->replyTo))
            {
                $this->replyTo = $this->config->get["SMTP_USERNAME"];
            }

            $this->addString(["Reply-To" => $this->replyTo]);

            
            $boundary = md5(uniqid(rand(), true));

            $multiPart = !$this->attachment ? "alternative" : "mixed";

            $this->addString(["Content-Type" => "multipart/{$multiPart}; boundary=\"{$boundary}\""]); 

            // if($multiPart != "mixed")
            // {
            //     /**
            //      * We are not duplicating our content, we are just supporting 
            //      * as many devices as we want we with the alternative baundary.
            //      */

            //     #plain text
            //     $this->addString("--{$boundary}");
            //     $this->addString(["Content-Type" => "text/text; charset=\"UTF-8\""]);
            //     $this->addString(["Content-Transfer-Encoding" => "base64"], 2); #Two line breaks
            //     $this->addString(chunk_split(base64_encode($this->body)));

            //     #Add watches and others here
            // }
            
            #html content
            $this->addString("--{$boundary}");
            $this->addString(["Content-Type" => "text/html; charset=\"UTF-8\""]);
            $this->addString(["Content-Transfer-Encoding" => "base64"], 2); #Two line breaks
            $this->addString(chunk_split(base64_encode($this->body)));

            #Attachments
            if(!empty($this->attachment))
            {
                foreach ($this->attachment as $name => $path)
                {
                    #Add file extension to the name
                    $name = sprintf("%s.%s", $name, pathinfo($path, PATHINFO_EXTENSION));

                    $this->addString("--{$boundary}");
                    $this->addString(["Content-Type" => "application/octet-stream; name=\"{$name}\""]);
                    $this->addString(["Content-Transfer-Encoding" => "base64"]);
                    $this->addString(["Content-Disposition" => "attachment; filename=\"{$name}\""], 2);
                    $this->addString(chunk_split(base64_encode(file_get_contents($path)))); 
                }
            }

            #End alternative
            $this->addString("--{$boundary}--");

            #End content
            $this->addString(".");
        }


        /**
         * @method encodeString
         * @param string 
         * @return string
         */
        private function encodeString($string)
        {
            return sprintf("=?utf-8?B?%s?= ", base64_encode($string));
        }


        /**
         * @method addString 
         * @param string | array content
         * @param int breakNumber
         * @param boolean encoded
         * Appends to data
         */
        private function addString($content, $breakNumber = 1, $encoded = false)
        {
            #determine line breaks
            $lineBreak = $breakNumber == 1 ? PHP_EOL : PHP_EOL . PHP_EOL;

            #Content is not an array
            if(!is_array($content))
            {
                $this->data .= sprintf("%s%s", $content, $lineBreak);

                return;
            }

            #Content is encoded
            if($encoded)
            {
                $this->data .= sprintf("%s: =?utf-8?B?%s?=%s", key($content), base64_encode(end($content)), $lineBreak);

                return;
            }
            
            #Default
            $this->data .= sprintf("%s: %s%s", key($content), end($content), $lineBreak);
        }


        /**
         * @method sendCommand
         * @param string command
         * @param int validCode
         * @return boolean
         * @throws exceptions
         * @print to console on debug mode
         */
        private function sendCommand($command, $validCode)
        {
            #Send Command with line breaks
            fputs($this->smtp, $command . PHP_EOL);

            #Read response string
            $response = fgets($this->smtp, 512); 

            #Response code
            $responseCode = substr(trim($response), 0, 3);

            #Validate response
            if($responseCode != (string)$validCode)
            {
                throw new Exception($response);
            }

            #Print transaction to the screen
            if($this->config->get["CONSOLE_LOG"])
            {
                print_r($response . "<pre>"); //Print with linebreak
            }
            
            return true;
        }
    }
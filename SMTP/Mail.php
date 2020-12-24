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


    class Mail
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
 
        
        #Config instance
        private 
            $config, #SMTP configuration
            $boundary, #Email Baundary separator
            $header, #Header string
            $bodyContent; #Body string


        #Constructor
        public function __construct()
        {
            if (!function_exists("mail"))
            {
               die("Please enable or install php mail");
            }

            #Initialize config
            $this->config = new Config();
        }

        public function __destruct()
        {
            $this->config = null;
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
            
            $this->createHeader(); #Build mail header string
            $this->createBody();    #Build mail body string

            if(mail(end($this->to), $this->subject, $this->bodyContent, $this->header))
            {
                return "Email sent successfully";
            }

            return "Unable to send mail";
        }


        /**
         * @method createHeader
         * STOP :: WARNING :: before modifying this file 
         * you must read and understant how mime works.
         */
        private function createHeader()
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

            
            $this->boundary = md5(uniqid(rand(), true));

            $multiPart = !$this->attachment ? "alternative" : "mixed";

            $this->addString(["Content-Type" => "multipart/{$multiPart}; boundary=\"{$this->boundary}\""]); 
        }


        /**
         * @method createBody
         * Body email content.
         */
        private function createBody()
        {
            #html content
            $this->addString("--{$this->boundary}", "bodyContent");
            $this->addString(["Content-Type" => "text/html; charset=\"UTF-8\""], "bodyContent");
            $this->addString(["Content-Transfer-Encoding" => "base64"], "bodyContent", 2); #Two line breaks
            $this->addString(chunk_split(base64_encode($this->body)), "bodyContent");

            #Attachments
            if(!empty($this->attachment))
            {
                foreach ($this->attachment as $name => $path)
                {
                    #Add file extension to the name
                    $name = sprintf("%s.%s", $name, pathinfo($path, PATHINFO_EXTENSION));

                    $this->addString("--{$this->boundary}", "bodyContent");
                    $this->addString(["Content-Type" => "application/octet-stream; name=\"{$name}\""], "bodyContent");
                    $this->addString(["Content-Transfer-Encoding" => "base64"], "bodyContent");
                    $this->addString(["Content-Disposition" => "attachment; filename=\"{$name}\""], "bodyContent", 2);
                    $this->addString(chunk_split(base64_encode(file_get_contents($path))), "bodyContent"); 
                }
            }

            #End alternative
            $this->addString("--{$this->boundary}--", "bodyContent");
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
         * @param string type (header or body)
         * @param int breakNumber (number of line breaks max 2)
         * @param boolean encoded
         * Appends to requested type
         */
        private function addString($content, $type = "header", $breakNumber = 1, $encoded = false)
        {
            #determine line breaks
            $lineBreak = $breakNumber == 1 ? PHP_EOL : PHP_EOL . PHP_EOL;

            #Content is not an array
            if(!is_array($content))
            {
                $this->{$type} .= sprintf("%s%s", $content, $lineBreak);

                return;
            }

            #Content is encoded
            if($encoded)
            {
                $this->{$type} .= sprintf("%s: =?utf-8?B?%s?=%s", key($content), base64_encode(end($content)), $lineBreak);

                return;
            }
            
            #Default
            $this->{$type} .= sprintf("%s: %s%s", key($content), end($content), $lineBreak);
        }
    }
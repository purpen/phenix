<?php
if (!class_exists('PHPMailer')) {
    include_once 'phpmailer/class.phpmailer.php';
    include_once 'phpmailer/class.smtp.php';
}
class Doggy_Util_Mail {
    /**
     * 发送邮件
     * 
     * @param mixed $to array|string 收件人地址
     * @param string $subject 主题
     * @param string $body 邮件正文
     * @param string $from 发信人地址
     * @param string $fromName 发信人名称
     * @param string $charset 字符集
     */
    public static function send($to,$subject,$body,$from=null,$fromName=null,$charset='utf-8'){
        $mailer = new PHPMailer();
        
        $mail_setting = Doggy_Config::get('app.util.mail.default');
        
        $mailer->Host = $mail_setting['host'];
        $mailer->Mailer = 'smtp';
        $smtp_auth = $mail_setting['smtp_auth'];
        if($smtp_auth){
            $mailer->SMTPAuth=true;
            $mailer->Username = $mail_setting['user'];
            $mailer->Password = $mail_setting['password'];
        }else{
            $mailer->SMTPAuth=false;
        }
        $mailer->CharSet = $charset;
        if(is_null($from)){
            $from = $mail_setting['from'];
        }
        if(is_null($fromName)){
            $fromName = $mail_setting['from_name'];
        }
        $mailer->From = $from;
        $mailer->FromName = $fromName;
        
        $mailer->Subject = $subject;
        
        if(!is_array($to)){
            $to = array($to);
        }
        
        foreach($to as $address){
            $mailer->AddAddress($address);
        }
        $mailer->Body = $body;
        Doggy_Log_Helper::debug("host:".$mailer->Host.' username:'.$mailer->Username);
        
        $ok = $mailer->Send();
        if(!$ok){
            Doggy_Log_Helper::error("send mail error:".$mailer->ErrorInfo);
        }
        unset($mailer);
        return $ok;
    }
    
    /**
     * Validate and verify an email account
     *
     * @param string $email 
     * @param string $domain_check check email domain dns record
     * @param string $verify probe and verify the email is available.
     * @param string $probe_address 
     * @param string $helo_address 
     * @param bool $return_errors If return the error message, default is false.
     * @return bool True if the email is verfied ok.
     */
    public static function validate_email($email, $domain_check = false, $verify = false, $probe_address='', $helo_address='', $return_errors=false) { 
        $server_timeout = 180; # timeout in seconds. Some servers deliberately wait a while (tarpitting) 
        # Check email syntax with regex 
        if (preg_match('/^([a-zA-Z0-9\._\+-]+)\@((\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,7}|[0-9]{1,3})(\]?))$/', $email, $matches)) {
            $user = $matches[1]; 
            $domain = $matches[2]; 
            # Check availability of  MX/A records 
            if ($domain_check) { 
                if (!function_exists('checkdnsrr')) {
                    throw new Doggy_Exception("checkdnsrr not exists");
                }
                if(getmxrr($domain, $mxhosts, $mxweight)) { 
                    for($i=0;$i<count($mxhosts);$i++){ 
                        $mxs[$mxhosts[$i]] = $mxweight[$i]; 
                    } 
                    asort($mxs); 
                    $mailers = array_keys($mxs);
                } elseif(checkdnsrr($domain, 'A')) {
                    // according to » RFC 2821 when no mail exchangers are listed, 
                    // hostname itself should be used as the only mail exchanger with a priority of 0
                    $mailers[0] = gethostbyname($domain);
                } else { 
                    $mailers=array(); 
                }
                $total = count($mailers);
                # check each mailserver 
                if($total > 0 && $verify) { 
                    # Check if mailers accept mail 
                    for($n=0; $n < $total; $n++) { 
                        # Check if socket can be opened 
                        Doggy_Log_Helper::debug("Checking server $mailers[$n]");
                        $connect_timeout = $server_timeout; 
                        $errno = 0; 
                        $errstr = 0; 
                        # Try to open up socket 
                        if($sock = @fsockopen($mailers[$n], 25, $errno , $errstr, $connect_timeout)) {
                            $response = fgets($sock); 
                            Doggy_Log_Helper::debug("Opening up socket to $mailers[$n]... Succes!\n");
                            stream_set_timeout($sock, 30); 
                            $meta = stream_get_meta_data($sock); 
                            Doggy_Log_Helper::debug("$mailers[$n] replied: $response\n");
                            $cmds = array( 
                                "HELO $helo_address", 
                                "MAIL FROM: <$probe_address>", 
                                "RCPT TO: <$email>", 
                                "QUIT", 
                            ); 
                            # Hard error on connect -> break out 
                            # Error means 'any reply that does not start with 2xx ' 
                            if(!$meta['timed_out'] && !preg_match('/^2\d\d[ -]/', $response)) { 
                                $error = "Error: $mailers[$n] said: $response\n"; 
                                Doggy_Log_Helper::debug("error:$error");
                                break; 
                            } 
                            foreach($cmds as $cmd) { 
                                $before = microtime(true); 
                                fputs($sock, "$cmd\r\n"); 
                                $response = fgets($sock, 4096); 
                                $t = 1000*(microtime(true)-$before); 
                                if(!$meta['timed_out'] && preg_match('/^5\d\d[ -]/', $response)) { 
                                    $error = "Unverified address: $mailers[$n] said: $response";
                                    Doggy_Log_Helper::debug("error:$error");
                                    break 2; 
                                } 
                            } 
                            fclose($sock); 
                            Doggy_Log_Helper::debug("Succesful communication with $mailers[$n], no hard errors, assuming OK");
                            break; 
                        } 
                        elseif($n == $total-1) { 
                            $error = "None of the mailservers listed for $domain could be contacted"; 
                        } 
                    } 
                } 
                elseif($total <= 0) { 
                    $error = "No usable DNS records found for domain '$domain'";
                } 
            } 
        } 
        else { 
            $error = 'Address syntax not correct';
        }

        if ($return_errors) { 
            # Give back details about the error(s). 
            # Return TRUE if there are no errors. 
            if(isset($error)) {
                return $error; 
            }
            else {
                return true; 
            }
        } else { 
            if (isset($error)) {
                return false;
            }
            else {
                return true;
            }
        } 
    }
}
?>
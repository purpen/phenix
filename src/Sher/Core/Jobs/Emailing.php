<?php
/**
 * 邮件服务
 * @author purpen
 */
use Mailgun\Mailgun;

class Sher_Core_Jobs_Emailing extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		Doggy_Log_Helper::debug("Make emailing jobs!");
		
		try{
			$name = $this->args['name'];
			$email = $this->args['email'];
			
			$edm_id = $this->args['edm_id'];
			
			$model = new Sher_Core_Model_Edm();
			$result = $model->load($edm_id);
			if(empty($result)){
				Doggy_Log_Helper::warn("Waiting edm is empty!");
				return false;
			}
			
			$subject = $result['title'];
			$content = $result['mailbody'];
			
			Doggy_Log_Helper::debug("Start to send email [$email]!");
			
			$ch = curl_init("http://www.baidu.com/");
			$fp = fopen("/Users/xiaoyi/data/uploads/example_homepage.txt", "w");

			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);

			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			
			// 开始发送
			$result = $this->send_mailgun($name, $email, $subject, $content);
			
			Doggy_Log_Helper::debug("Send to end: ".json_encode($result));
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Clean temp order failed: ".$e->getMessage());
		}
	}
	
	public function send_mailgun($name, $email, $subject, $content){
 
		$config = array();

		$config['api_key'] = "key-6k-1qi-1gvn4q8dpszcp8uvf-7lmbry0";
 
		$config['api_url'] = "https://api.mailgun.net/v2/email.taihuoniao.com/messages";
 
		$message = array();
 
		$message['from'] = "太火鸟 <noreply@email.taihuoniao.com>";
 
		$message['to'] = $email;
 
		$message['subject'] = $subject;
 
		$message['html'] = $content;
		
		print_r($message);
 
		$ch = curl_init();
 	    print '1';
		curl_setopt($ch, CURLOPT_URL, $config['api_url']);
 	   print '2';
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
 
		curl_setopt($ch, CURLOPT_USERPWD, "api:{$config['api_key']}");
 	   print '3';
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
 
		curl_setopt($ch, CURLOPT_POST, true); 
 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
 	    print '4';
		$result = curl_exec($ch);
		print '5';
 	    print_r($result);
		curl_close($ch);
 
		return $result;
	}
	
	/**
	 * 发送邮件
	 */
	public function send_mail($name, $to_mail, $subject, $content){
		$mg = new Mailgun('key-6k-1qi-1gvn4q8dpszcp8uvf-7lmbry0');
		$domain = 'email.taihuoniao.com';
		
		Doggy_Log_Helper::debug("Mailgun to send email[$to_mail][$subject][$content]!");
		
		$result = $mg->sendMessage($domain, array(
			'from' => '太火鸟 <noreply@email.taihuoniao.com>',
			'to' => "$name <$to_mail>",
			'subject' => $subject,
			'html' => $content,
		));
		
		Doggy_Log_Helper::warn("Mailgun send[$to_mail] is done!");
		
		return $result;
	}
	
	public function pertest($name,$email,$edm_id){
		try{			
			$model = new Sher_Core_Model_Edm();
			$result = $model->load($edm_id);
			if(empty($result)){
				Doggy_Log_Helper::warn("Waiting edm is empty!");
				return false;
			}
			
			$subject = $result['title'];
			$content = $result['mailbody'];
			
			Doggy_Log_Helper::debug("Start to send email[$email]!");
			
			// 开始发送
			$this->send_mail($email, $subject, $content);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Clean temp order failed: ".$e->getMessage());
		}
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}
?>
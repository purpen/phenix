<?php
/**
 * 异步上传用户头像服务
 * @author tianshuai
 */
class Sher_Core_Jobs_UserAvatar extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		Doggy_Log_Helper::debug("Make user avatar task jobs!");
		$user_id = isset($this->args['user_id']) ? (int)$this->args['user_id'] : 0;
        $avatar_url = isset($this->args['avatar_url']) ? $this->args['avatar_url'] : null;

        if(empty($user_id) || empty($avatar_url)){
            Doggy_Log_Helper::warn("Waiting user_id or avatar url is empty!");
            return false;
        }
		
		try{
			// 检测用户是否存在
			$user_model = new Sher_Core_Model_User();
			$user = $user_model->load($user_id);
			if(empty($user)){
				Doggy_Log_Helper::warn("user is empty!");
				return false;
			}

            $accessKey = Doggy_Config::$vars['app.qiniu.key'];
            $secretKey = Doggy_Config::$vars['app.qiniu.secret'];
            $bucket = Doggy_Config::$vars['app.qiniu.bucket'];
            // 新截图文件Key
            $qkey = Sher_Core_Util_Image::gen_path_cloud();

            $client = \Qiniu\Qiniu::create(array(
                'access_key' => $accessKey,
                'secret_key' => $secretKey,
                'bucket'     => $bucket
            ));

            // 存储新图片
            $res = $client->upload(@file_get_contents($avatar_url), $qkey);
            if (empty($res['error'])){
                $avatar_up = $qkey;
            }else{
                $avatar_up = false;
            }

            if($avatar_up){
                // 更新用户头像
                $user_model->update_avatar(array(
                    'big' => $qkey,
                    'medium' => $qkey,
                    'small' => $qkey,
                    'mini' => $qkey
                ));   
            }               

		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Queue user avatar failed: ".$e->getMessage());
		}
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}


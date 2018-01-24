<?php
/**
 *	验证码
 */
class Sher_Core_Util_Captcha{  //class start  
  
    private $sname = '';  
  
    public function __construct($sname=''){ // $sname captcha session name  
        $this->sname = $sname==''? 'm_captcha' : $sname;  
    }  
  
    /** 生成验证码图片 
    * @param  int   $length 驗證碼長度 
    * @param  Array $param  參數 
    * @return IMG 
    */  
    public function create($length=4,$param=array()){  
        Header("Content-type: image/PNG");  
        $authnum = $this->random($length);   //生成验证码字符.  
      
        $width  = isset($param['width'])? $param['width'] : 15;     //文字宽度  
        $height = isset($param['height'])? $param['height'] : 20;   //文字高度  
        $pnum   = isset($param['pnum'])? $param['pnum'] : 300;      //干扰象素个数  
        $lnum   = isset($param['lnum'])? $param['lnum'] : 15;        //干扰线条数  
  
        $this->captcha_session($this->sname,$authnum);                //將隨機數寫入session  
  
        $pw = $width*$length+10;  
        $ph = $height+6;  
                  
        $im = imagecreate($pw,$ph);                     //imagecreate() 新建图像，大小为 x_size 和 y_size 的空白图像。  
        $black = ImageColorAllocate($im, 238,238,238);  //设置背景颜色  
      
        $values = array(  
                mt_rand(0,$pw),  mt_rand(0,$ph),  
                mt_rand(0,$pw),  mt_rand(0,$ph),  
                mt_rand(0,$pw),  mt_rand(0,$ph),  
                mt_rand(0,$pw),  mt_rand(0,$ph),  
                mt_rand(0,$pw),  mt_rand(0,$ph),  
                mt_rand(0,$pw),  mt_rand(0,$ph)  
        );  
        imagefilledpolygon($im, $values, 6, ImageColorAllocate($im, mt_rand(170,255),mt_rand(200,255),mt_rand(210,255)));   //設置干擾多邊形底圖  
      
        /* 文字 */  
        for ($i = 0; $i < strlen($authnum); $i++){  
            $font = ImageColorAllocate($im, mt_rand(0,50),mt_rand(0,150),mt_rand(0,200));//设置文字颜色  
            $x  = $i/$length * $pw + rand(1, 6);    //设置随机X坐标  
            $y  = rand(1, $ph/3);                   //设置随机Y坐标  
            imagestring($im, mt_rand(4,6), $x, $y, substr($authnum,$i,1), $font);   
        }  
  
        /* 加入干扰象素 */  
        for($i=0; $i<$pnum; $i++){  
            $dist = ImageColorAllocate($im, mt_rand(0,255),mt_rand(0,255),mt_rand(0,255)); //设置杂点颜色  
            imagesetpixel($im, mt_rand(0,$pw) , mt_rand(0,$ph) , $dist);   
        }   
  
        /* 加入干擾線 */  
        for($i=0; $i<$lnum; $i++){  
            $dist = ImageColorAllocate($im, mt_rand(50,255),mt_rand(150,255),mt_rand(200,255)); //設置線顏色  
            imageline($im,mt_rand(0,$pw),mt_rand(0,$ph),mt_rand(0,$pw),mt_rand(0,$ph),$dist);  
        }  
  
        ImagePNG($im);      //以 PNG 格式将图像输出到浏览器或文件  
        ImageDestroy($im);  //销毁一图像  
    }  
  
  
    /** 檢查驗證碼 
    * @param String $captcha    驗證碼 
    * @param int    $flag       驗證成功后 0:不清除session 1:清除session 
    * @return boolean 
    */    
    public function check($captcha,$flag=1){  
        if(empty($captcha)){  
            return false;  
        }else{  
            if(strtoupper($captcha)==$this->captcha_session($this->sname)){   //檢測驗證碼  
                if($flag==1){  
                    $this->captcha_session($this->sname,'');  
                }  
                return true;  
            }else{  
                return false;  
            }  
        }  
    }  
      
  
    /* 产生随机数函数 
    * @param    int     $length 需要隨機生成的字符串數 
    * @return   String 
    */  
    private function random($length){  
        $hash = '';  
        $chars = 'ABCDEFGHIJKLMNPQRSTUVWXYZ23456789';  
        $max = strlen($chars) - 1;  
        for($i = 0; $i < $length; $i++) {  
            $hash .= $chars[mt_rand(0, $max)];  
        }  
        return $hash;  
    }  
  
  
    /** 驗證碼session處理方法 
    * @param    String  $name   captcha session name 
    * @param    String  $value 
    * @return   String 
    */  
    private function captcha_session($name,$value=null){  
        if(isset($value)){  
            if($value!==''){  
                $_SESSION[$name] = $value;  
            }else{  
                unset($_SESSION[$name]);  
            }  
        }else{  
            return isset($_SESSION[$name])? $_SESSION[$name] : '';  
        }  
    }  
  
}   // class end 

?>

<?php
/**
 * 移动端设备号统计
 * @author tianshuai
 */
class Sher_Core_Service_Pusher extends Sher_Core_Service_Base {

    protected static $instance;
	
    /**
     * current service instance
     *
     * @return Sher_Core_Service_Pusher
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            return self::$instance = new Sher_Core_Service_Pusher();
        }
        return self::$instance;
    }

	
}


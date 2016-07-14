<?php

/**
 * 
 * @author tianshuai
 *
 */
class Sher_Core_Util_JdPay_SettingsINI extends Sher_Core_Util_JdPay_Settings
{
	function load($file) {
		if (file_exists ( $file ) == true) {
			$this->_settings = parse_ini_file ( $file, true );
		}
	}

}

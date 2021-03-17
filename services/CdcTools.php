<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SAS Comptoir du Code
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SAS Comptoir du Code is strictly forbidden.
 * In order to obtain a license, please contact us: contact@comptoirducode.com
 *
 * @author    Vincent - Comptoir du Code
 * @copyright Copyright(c) 2015-2020 SAS Comptoir du Code
 * @license   Commercial license
 * @package   cdc_googletagmanager
 */

class CdcTools {

	/**
	 * Return true if String if found in file
	 */
	public static function stringInFile($string, $file) {
		$handle = @fopen($file, 'r');
        if(!$handle) {
            // file not found
            return false;
        }

		$found = false; // init as false
		while (($buffer = fgets($handle)) !== false) {
		    if (strpos($buffer, $string) !== false) {
		        $found = true;
		        break; // Once you find the string, you should break out the loop.
		    }
		}
		fclose($handle);

		return $found;
	}


    /**
     * Checks if the controller has been called from XmlHttpRequest (AJAX)
     * method from Controller.php
     * @return bool
     */
    public static function isXmlHttpRequest()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && Tools::strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

}

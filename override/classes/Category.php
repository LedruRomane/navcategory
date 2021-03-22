<?php
/**
 * Creative Elements - Elementor based PageBuilder
 *
 * @author    WebshopWorks
 * @copyright 2019-2020 WebshopWorks.com
 * @license   One domain support license
 */
defined('_PS_VERSION_') or die;
class Category extends CategoryCore
{
    /*
    * module: creativeelements
    * date: 2020-12-03 09:13:32
    * version: 1.0.14
    */
    const CE_OVERRIDE = true;
    /*
    * module: creativeelements
    * date: 2020-12-03 09:13:32
    * version: 1.0.14
    */
    public function __construct($idCategory = null, $idLang = null, $idShop = null)
    {
        parent::__construct($idCategory, $idLang, $idShop);
        $ctrl = Context::getContext()->controller;
        if ($ctrl instanceof CategoryController && !CategoryController::$initialized && !$this->active && Tools::getIsset('id_employee') && Tools::getIsset('adtoken')) {
            $tab = 'AdminCategories';
            if (Tools::getAdminToken($tab . (int) Tab::getIdFromClassName($tab) . (int) Tools::getValue('id_employee')) == Tools::getValue('adtoken')) {
                $this->active = 1;
            }
        }
    }

    public static $instance = array();

    public static function getInstance($id_category)
    {
        if (isset(self::$instance[$id_category])) {
            return self::$instance[$id_category];
        }

        return self::$instance[$id_category] = new Category($id_category);
    }

    public static function getMediaCategory($id_category) {
        $idLang = Context::getContext()->language->id;
        try {
            $currentCategory = new Category($id_category);
            if ($currentCategory->id_parent == 103) {
                return $currentCategory;
            }
            $parentCategories = $currentCategory->getParentsCategories($idLang);
            foreach($parentCategories as $parentCategory) {
                if ($parentCategory['id_parent'] == 103) {
                    $mediaCategory = new Category($parentCategory['id_category'], $idLang);
                    break;
                }
            }
        }
        catch (Exception $e) {
            return false;
        }
        if (isset($mediaCategory)) {
            return $mediaCategory;
        } else {
            return false;
        }
    }
}



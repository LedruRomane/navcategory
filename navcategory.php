<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
require_once _PS_MODULE_DIR_.'navcategory/classes/Config.php';
require_once _PS_MODULE_DIR_.'navcategory/services/CdcTools.php';

class Navcategory extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'navcategory';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'RLedru';
        $this->need_instance = 1;


        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Navigation Category');
        $this->description = $this->l('Module prestashop pour naviguer dans les pages catégories avec un menu dynamique de l\'arborescence. ');

        $this->confirmUninstall = $this->l('Etes-vous sûr de vouloir désinstaller ce module? ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }


    public function install()
    {
        Configuration::updateValue('NAVCATEGORY_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('DisplayHeaderCategory');
    }

    public function uninstall()
    {
        Configuration::deleteByName('NAVCATEGORY_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitNavcategoryModule')) == true) {
            $this->postProcess();
        }

        $output = null;

        if(Config::get('HOOK') == null) {
            $hook = $this->installCustomHooks();
            if ($hook) {
                $output .= $this->displayConfirmation($this->l('Hooks are correctly installed!'));
                $hookDb = Config::updateHook('displayHeaderCategory', 'Top of page category', 'This hook is placed at the top of product list on page category');
                $hookCat = Config::updateValue('HOOK', 'hookInstalled');
                if (!$hookDb || !$hookCat) {
                    $output .= $this->displayError($this->l('Hooks are not correctly installed :-('));
                }
            } else {
                $output .= $this->displayError($this->l('Hooks are not correctly installed :-('));
                $this->smarty->assign(array(
                    'troubleshooting' => true
                ));
            }
        }

            $this->context->smarty->assign('module_dir', $this->_path);

            $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

            return $output . $this->renderForm();

    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitNavcategoryModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'NAVCATEGORY_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'NAVCATEGORY_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'NAVCATEGORY_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'NAVCATEGORY_LIVE_MODE' => Configuration::get('NAVCATEGORY_LIVE_MODE', true),
            'NAVCATEGORY_ACCOUNT_EMAIL' => Configuration::get('NAVCATEGORY_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'NAVCATEGORY_ACCOUNT_PASSWORD' => Configuration::get('NAVCATEGORY_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayHeaderCategory()
    {
        $data=null;

        $this->context->smarty->assign([
            'data' => $data
        ]);
        return $this->display(__FILE__, 'navcategory.tpl');
    }

    /***
     * Install into tpl template of prestashop the custom hook
     * @return bool
     */
    public function installCustomHooks()
    {
        $success = true;
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $filename = _PS_THEME_DIR_ . 'templates/catalog/listing/product-list.tpl';
            if (!CdcTools::stringInFile('{hook h="displayHeaderCategory"}', $filename)) {
                $file_content = Tools::file_get_contents($filename);
                $strg = "(<section id=\"main\">)";
                if (!empty($file_content)) {
                    $matches = preg_split($strg, $file_content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                    if (count($matches) == 2) {
                        $new_content = $matches[0] . "<section id=\"main\"> \n {hook h=\"displayHeaderCategory\"}" . $matches[1];
                        if (!file_put_contents($filename, $new_content)) {
                            $success = false;
                        }
                    } else {
                        $success = false;
                    }
                } else {
                    $success = false;
                }
            }
        }
        return $success;
    }

}

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
            $this->registerHook('DisplayHeaderCategory') &&
            $this->registerHook('backOfficeFooter');
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

        $output = null;
        if (Tools::isSubmit('savePerso')) {
            $this->postProcessPerso();
            if($output == null)
            {
                $output .= $this->displayConfirmation($this->l('Configurations mises à jour.'));
            }
        }
        if (Tools::isSubmit('saveAuto')) {
            $this->postProcessAuto();
            if($output == null)
            {
                $output .= $this->displayConfirmation($this->l('Configurations mises à jour.'));
            }
        }

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

        return $output.$this->renderForm();

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
        $inputData = $this->getConfigFormValues();
        $checkboxData = $this->getConfigCheckboxValues();
        $datas = array_merge($inputData,$checkboxData);
        $helper->tpl_vars = array(
            'fields_value' => $datas,/* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $fields_form[0]['form'] = array(
            'legend' => array(
            'title' => $this->l('Pré-selection'),
            'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type'      => 'radio',
                    'label'     => $this->l('Quelle méthode d\'affichage voulez-vous utiliser? '),
                    'desc'      => $this->l('Choisissez l\'un ou l\'autre.'),
                    'name'      => 'TYPE_CONFIG',
                    'required'  => true,
                    'class'     => 'radiobutton',
                    'is_bool'   => true,
                    'values'    => array(
                        array(
                            'id'    => 'perso',
                            'value' => 1,
                            'label' => $this->l('Maillage personnalisé en fonction de la catégorie courante.')
                        ),
                        array(
                            'id'    => 'auto',
                            'value' => 0,
                            'label' => $this->l('Maillage automatique par niveau en fonction de la catégorie courante.')
                        )
                    ),
                ),
            ));
        $fields_form[1]['form'] = array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    $this->getTextBoxPerso(1),
                    $this->getCheckboxPerso(1),
                    $this->getTextBoxPerso(2),
                    $this->getCheckboxPerso(2),
                    $this->getTextBoxPerso(3),
                    $this->getCheckboxPerso(3),
                    $this->getTextBoxPerso(4),
                    $this->getCheckboxPerso(4),

                ),
                'submit' => array(
                    'title' => $this->l('Enregistrer'),
                    'name'  => 'savePerso'
                ),
        );
        $fields_form[2]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                $this->getTextBoxAuto(2),
                $this->getTextBoxAuto(3),
                $this->getTextBoxAuto(4),
                $this->getTextBoxAuto(5),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Selectionnez l\'arborescence de la catégorie courante voulue pour le bloc : '),
                    'desc' => $this->l('Faites vos choix.'),
                    'name' => 'Auto_options',
                    'values' => array(
                        'query' => $this->getOptions(),
                        'id' => 'id_checkbox_options',
                        'name' => 'checkbox_options_name',
                    ),
                ),
            ),

            'submit' => array(
                'title' => $this->l('Enregistrer'),
                'name'  => 'saveAuto'
            ),

        );
        return $fields_form;

    }

    /***
     * Option bloc for the displayForm checkbox Perso in the Admin Config page (title + checkboxes)
     *
     */
    public function getCheckboxPerso($num){
        $checkbox = array(
                'type' => 'checkbox',
                'label' => $this->l('Selectionnez l\'arborescence de la catégorie courante voulue pour le bloc '.$num.' : '),
                'desc' => $this->l('Faites vos choix.'),
                'name' => 'Perso_options_'.$num,
                'values' => array(
                    'query' => $this->getOptions(),
                    'id' => 'id_checkbox_options',
                    'name' => 'checkbox_options_name',
                ),
            );
        return $checkbox;
    }

    /***
     * Option bloc for the displayForm textbox Perso in the Admin Config page (title + checkboxes)
     *
     */
    public function getTextBoxPerso($num){
        $textBox = array(
            'col' => 3,
            'type' => 'text',
            'label' => $this->l('Titre '.$num.' : '),
            'name' => 'PERSO_TITRE_'.$num,
            'required' => true,
            'desc'     => $this->l('Donner un titre pour le bloc numéro '.$num.'. ')
        );
        return $textBox;
    }

    /***
     * Option bloc for the displayForm textbox Auto in the Admin Config page (title + checkboxes)
     *
     */
    public function getTextBoxAuto($num){
        $textBox = array(
            'col' => 3,
            'type' => 'text',
            'label' => $this->l('Titre au niveau '.$num.' : '),
            'name' => 'AUTO_TITRE_'.$num,
            'required' => true,
            'desc'     => $this->l('Titre obligatoire.')
        );
        return $textBox;
    }

    /***
     * Options for the displayForm in the Admin Config page (checkboxes)
     * @return string[][]
     */
    public function  getOptions(){
        $options =array(
            array(
                'id_checkbox_options' => '1',
                'checkbox_options_name' => 'Grand-parents',
            ),
            array(
                'id_checkbox_options' => '2',
                'checkbox_options_name' => 'Parents',
            ),
            array(
                'id_checkbox_options' => '3',
                'checkbox_options_name' => 'Oncles',
            ),
            array(
                'id_checkbox_options' => '4',
                'checkbox_options_name' => 'Frères',
            ),
            array(
                'id_checkbox_options' => '5',
                'checkbox_options_name' => 'Cousins',
            ),
            array(
                'id_checkbox_options' => '6',
                'checkbox_options_name' => 'Enfants',
            ),
            array(
                'id_checkbox_options' => '7',
                'checkbox_options_name' => 'Neveux',
            ),
            array(
                'id_checkbox_options' => '8',
                'checkbox_options_name' => 'Petits-enfants',
            )
        );
        return $options;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {

        $return = array(
            'TYPE_CONFIG' => Config::get('TYPE_CONFIG'),
            'AUTO_TITRE_2' => Config::get('AUTO_TITRE_2'),
            'AUTO_TITRE_3' => Config::get('AUTO_TITRE_3'),
            'AUTO_TITRE_4' => Config::get('AUTO_TITRE_4'),
            'AUTO_TITRE_5' => Config::get('AUTO_TITRE_5'),
            'PERSO_TITRE_1' => Config::get('PERSO_TITRE_1'),
            'PERSO_TITRE_2' => Config::get('PERSO_TITRE_2'),
            'PERSO_TITRE_3' => Config::get('PERSO_TITRE_3'),
            'PERSO_TITRE_4' => Config::get('PERSO_TITRE_4')

        );

        return $return;
    }

    protected function getConfigCheckboxValues(){

        $configAutoField = array (
            'Auto_options' => Config::get('Auto_options')
        );
        $configPersoField = array (
            'Perso_options' => Config::get('Perso_options')
        );

        $opts = $this->getOptions();
        $id_checkbox_options = array();
        foreach($opts as $options){
            $id_checkbox_options[] = $options['id_checkbox_options'];
            }
        $id_checkbox_options_post = array();
        foreach ($id_checkbox_options as $opt_id)
        {
            if (Tools::getValue('Auto_options_'.(int)$opt_id))
            {
                $id_checkbox_options_post['Auto_options_'.(int)$opt_id] = true;
            }
        }
        $id_checkbox_options_config = array();
        if ($confs = Config::get('Auto_options')){
            $confs = explode(',', Config::get('Auto_options'));
        }
        else{
            $confs = array();
        }
        foreach ($confs as $conf){
            $id_checkbox_options_config['Auto_options_'.(int)$conf] = true;
        }

        if (Tools::isSubmit('saveAuto')){
            $configAutoField = array_merge($configAutoField, array_intersect($id_checkbox_options_post, $id_checkbox_options_config));
        }
        else{
            $configAutoField = array_merge($configAutoField, $id_checkbox_options_config);
        }
        return $configAutoField;
    }


    /**
     * Save form data.
     */
    protected function postProcessPerso()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Config::updateValue($key, Tools::getValue($key));
        }

        $all_opts = $this->getOptions();
        $checkbox_options = array();
        foreach ($all_opts as $chbx_options)
        {
            if (Tools::getValue('options_'.(int)$chbx_options['id_checkbox_options']))
            {
                $checkbox_options[] = $chbx_options['id_checkbox_options'];
            }
        }
        Config::updateValue('Perso_options', implode(',', $checkbox_options));
    }

    /**
     * Save form data.
     */
    protected function postProcessAuto()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Config::updateValue($key, Tools::getValue($key));
        }

        $all_opts = $this->getOptions();
        $checkbox_options = array();
        foreach ($all_opts as $chbx_options)
        {
            if (Tools::getValue('Auto_options_'.(int)$chbx_options['id_checkbox_options']))
            {
                $checkbox_options[] = $chbx_options['id_checkbox_options'];
            }
        }
        Config::updateValue('Auto_options', implode(',', $checkbox_options));
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeFooter()
    {
        return $this->display(__FILE__, 'back.tpl');
    }

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

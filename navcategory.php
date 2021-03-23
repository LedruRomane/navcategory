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
require_once _PS_MODULE_DIR_.'navCategory/classes/Config.php';
require_once _PS_MODULE_DIR_.'navCategory/services/CdcTools.php';

class NavCategory extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'navCategory';
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
        if (Shop::isFeatureActive())
        {
        Shop::setContext(Shop::CONTEXT_ALL);
    }

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('displayHeaderCategory') &&
            $this->registerHook('displayBackOfficeFooter');
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
        $helper->submit_action = 'submitNavCategoryModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $inputData = $this->getConfigFormValues();
        $checkboxData = array_merge($this->getConfigCheckboxValuesPerso(),$this->getConfigCheckboxValuesAuto());
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

    protected function getConfigCheckboxValuesAuto(){

        $configAutoField = array (
            'Auto_options' => Config::get('Auto_options')
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

    protected function getConfigCheckboxValuesPerso(){

        $configPersoField = array (
            'Perso_options_2' => Config::get('Perso_options_2'),
            'Perso_options_3' => Config::get('Perso_options_3'),
            'Perso_options_4' => Config::get('Perso_options_4'),
            'Perso_options_5' => Config::get('Perso_options_5'),
        );

        for($i=2; $i<6; $i++){
            $num=$i-1;
            $opts = $this->getOptions();
            $id_checkbox_options = array();
            foreach($opts as $options){
                $id_checkbox_options[] = $options['id_checkbox_options'];
            }
            $id_checkbox_options_post = array();
            foreach ($id_checkbox_options as $opt_id)
            {
                if (Tools::getValue('Perso_options_'.$num.'_'.(int)$opt_id))
                {
                    $id_checkbox_options_post['Perso_options_'.$num.'_'.(int)$opt_id] = true;
                }
            }
            $id_checkbox_options_config = array();
            if ($confs = Config::get('Perso_options_'.$i)){
                $confs = explode(',', Config::get('Perso_options_'.$i));
            }
            else{
                $confs = array();
            }
            foreach ($confs as $conf){
                $id_checkbox_options_config['Perso_options_'.$num.'_'.(int)$conf] = true;
            }
            if (Tools::isSubmit('saveAuto')){
                $configPersoField = array_merge($configPersoField, array_intersect($id_checkbox_options_post, $id_checkbox_options_config));
            }
            else{
                $configPersoField = array_merge($configPersoField, $id_checkbox_options_config);
            }
        }

        return $configPersoField;
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

        for ($i=2; $i<6; $i++){
            $all_opts = $this->getOptions();
            $checkbox_options = array();
            $num = $i-1;
            foreach ($all_opts as $chbx_options)
            {
                if (Tools::getValue('Perso_options_'.$num.'_'.(int)$chbx_options['id_checkbox_options']))
                {
                    $checkbox_options[] = $chbx_options['id_checkbox_options'];
                }
            }
            $id = 'Perso_options_'.$i;
            Config::updateValue($id, implode(',', $checkbox_options));
        }
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

    /***
     * return datas to template hook.
     * @return mixed
     */
    public function hookDisplayHeaderCategory()
    {
        $data = array();
        $currentCategory = new Category(Tools::getValue('id_category'));
        $typeOfConfig = Config::get('TYPE_CONFIG');
        $grandParents = $this->getGrandParents($currentCategory);
        $parent = $this->getParent($currentCategory);
        $uncles = $this->getUncles($currentCategory);
        $brothers = $this->getBrothers($currentCategory);
        $cousins = $this->getCousins($currentCategory);
        $children = $this->getChildren($currentCategory);
        $nephew = $this->getNephew($currentCategory);
        $grandChildren = $this->getGrandChildren($currentCategory);

        //AUTO
        if($typeOfConfig == 0){
            $configChecked = explode(',', Config::get('Auto_options'));
            foreach($configChecked as $value)
            {
                switch ($value)
                {
                    case '1':
                        $data = array_merge($data,$grandParents);
                        break;
                    case '2':
                        $data = array_merge($data,$parent);
                        break;
                    case '3':
                        $data = array_merge($data,$uncles);
                        break;
                    case '4':
                        $data = array_merge($data,$brothers);
                        break;
                    case '5':
                        $data =  array_merge($data,$cousins);
                        break;
                    case '6':
                        $data = array_merge($data,$children);
                        break;
                    case '7':
                        $data = array_merge($data,$nephew);
                        break;
                    case '8':
                        $data = array_merge($data,$grandChildren);
                        break;
                }
            }
            $titles = array(
                "2" => Config::get('AUTO_TITRE_2'),
                "3" => Config::get('AUTO_TITRE_3'),
                "4" => Config::get('AUTO_TITRE_4'),
                "5" =>Config::get('AUTO_TITRE_5')
            );
            $ifExist = $this->ifDepthExist($data);


        }
        //PERSO
        else if($typeOfConfig == 1){
            $titles = array(
                "0" => Config::get('PERSO_TITRE_1'),
                "1" => Config::get('PERSO_TITRE_2'),
                "2" => Config::get('PERSO_TITRE_3'),
                "3" =>Config::get('PERSO_TITRE_4')
            );

            $configChecked = array(
                0 => explode(',', Config::get('Perso_options_2')),
                1 => explode(',', Config::get('Perso_options_3')),
                2 => explode(',', Config::get('Perso_options_4')),
                3 => explode(',', Config::get('Perso_options_5'))
            );

            foreach ($configChecked as $key => $value) {
                $group = array();
                foreach($value as $key2 => $id){
                    switch ($id)
                    {
                        case '1':
                            $group = array_merge($group,$grandParents);
                            break;
                        case '2':
                            $group = array_merge($group,$parent);
                            break;
                        case '3':
                            $group = array_merge($group,$uncles);
                            break;
                        case '4':
                            $group = array_merge($group,$brothers);
                            break;
                        case '5':
                            $group =  array_merge($group,$cousins);
                            break;
                        case '6':
                            $group = array_merge($group,$children);
                            break;
                        case '7':
                            $group = array_merge($group,$nephew);
                            break;
                        case '8':
                            $group = array_merge($group,$grandChildren);
                            break;
                    }

                    $data[$key] = $group;
                    foreach($group as $key2 => $value){
                        if(isset($group) && $value['id_category'] != 2){
                            $ifExist[$key] = true;
                        }
                    }
                }
            }
        }

        $this->context->smarty->assign([
            'data' => $data,
            'title' => $titles,
            'ifExist' => $ifExist,
            'type' => $typeOfConfig
        ]);
        return $this->display(__FILE__, 'navCategory.tpl');
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

    /***
     * get children form current category
     * @param $currentCategory
     * @return mixed
     */
    public function getChildren($currentCategory){
        $category_children = $currentCategory->getChildren(Tools::getValue('id_category'),$this->context->language->id);
        foreach ($category_children as $key => $value){
            $cat = new Category($value['id_category']);
            $value['level_depth'] = $cat->level_depth;
            $category_children[$key] = $value;
        }

        return $category_children;
    }

    /***
     * get parent from current category
     * @param $currentCategory
     * @return mixed
     */
    public function getParent($currentCategory){
        $category_grandparent = $currentCategory->getParentsCategories();
        foreach($category_grandparent as $key => $value){
            if($value['id_category'] == $currentCategory->id ){
                unset($category_grandparent[$key]);
            }
            elseif($value['id_category'] == $currentCategory->id_parent){
                $category_parent[] = $category_grandparent[$key];
                unset($category_grandparent[$key]);
            }
        }
        return $category_parent;
    }

    /***
     * get all parents without direct parent from current category
     * @param $currentCategory
     * @return mixed
     *
     */
    public function getGrandParents($currentCategory){
        $category_grandparent = $currentCategory->getParentsCategories();
        foreach($category_grandparent as $key => $value){
            if($value['id_category'] == $currentCategory->id ){
                unset($category_grandparent[$key]);
            }
            elseif($value['id_category'] == $currentCategory->id_parent){
                $category_parent[] = $category_grandparent[$key];
                unset($category_grandparent[$key]);
            }
        }
        return $category_grandparent;
    }

    /***
     * get grandchildren from current category
     * @param $currentCategory
     * @return array
     */
    public function getGrandChildren($currentCategory){
        $category_grandchildren = array();
        if ($subCategories = $currentCategory->getSubCategories($this->context->language->id)) {
            foreach ($subCategories as $key => $subcat) {
                $subcatObj = new Category($subcat['id_category']);
                $category_grandchildren = array_merge($category_grandchildren, $subcatObj->getSubCategories($this->context->language->id));
            }
        }
        return $category_grandchildren;
    }

    /***
     * get uncles from current category
     * @param $currentCategory
     * @return mixed
     */
    public function getUncles($currentCategory){
        $category_grandparents = $this->getGrandParents($currentCategory);
        $getOnlyGrandParent = reset($category_grandparents);
        $grandParentCategory = new Category($getOnlyGrandParent['id_category']);
        $category_uncles = $grandParentCategory->getChildren($getOnlyGrandParent['id_category'],$this->context->language->id);
        foreach($category_uncles as $key => $value){
            if($value['id_category'] == '1' || $value['id_category'] == $currentCategory->id_parent){
                unset($category_uncles[$key]);
            }
            else{
                $cat = new Category($value['id_category']);
                $value['level_depth'] = $cat->level_depth;
                $category_uncles[$key] = $value;
            }
        }
        return $category_uncles;
    }

    /***
     * get cousins from current category
     * @param $currentCategory
     * @return array
     */
    public function getCousins($currentCategory){
        $category_cousins = array();
        $category_uncles = $this->getUncles($currentCategory);
        foreach($category_uncles as $key => $value){
            $uncle = new Category($value['id_category']);
            $category_cousins = array_merge( $category_cousins, $uncle-> getChildren($value['id_category'],$this->context->language->id));
        }
        foreach ($category_cousins as $key => $value){
            $cat = new Category($value['id_category']);
            $value['level_depth'] = $cat->level_depth;
            $category_cousins[$key] = $value;
        }
        return $category_cousins;
    }

    /**
     * get all nephew from the current category
     * @param $currentCategory
     * @return array
     */
    public function getNephew($currentCategory){
        $category_cousins = $this->getCousins($currentCategory);
        $category_brothers = $this->getBrothers($currentCategory);
        $categories = array_merge($category_cousins,$category_brothers);
        $category_nephew = array();
        foreach($categories as $key => $value){
            $reference = new Category($value['id_category']);
            $category_nephew = array_merge($category_nephew,$reference->getChildren($value['id_category'],$this->context->language->id));
        }
        foreach ($category_nephew as $key => $value){
            $cat = new Category($value['id_category']);
            $value['level_depth'] = $cat->level_depth;
            $category_nephew[$key] = $value;
        }
        return $category_nephew;
    }

    /**
     * get brothers from the current category
     * @param $currentCategory
     * @return array
     */
    public function getBrothers($currentCategory){
        $category_brother = array();
        $category_parent = $this->getParent($currentCategory);
        foreach($category_parent as $key => $value){
            $parent = new Category($value['id_category']);
            $category_brother = array_merge($category_brother,$parent->getChildren($value['id_category'],$this->context->language->id));
        }
        foreach($category_brother as $key => $value){
            if($value['id_category'] == $currentCategory->id) {
                unset($category_brother[$key]);
            }
            else{
                $cat = new Category($value['id_category']);
                $value['level_depth'] = $cat->level_depth;
                $category_brother[$key] = $value;
            }
        }
        return $category_brother;
    }

    public function ifDepthExist($data){
        $ifExist = array();
        foreach ($data as $key => $value){
            $ifExist[$value['level_depth']] = true;
        }
        return $ifExist;
    }

}

<?php
/**
 * Gère le formulaire d'un model
 *
 * @author:  Michel Dumont <michel.dumont.io>
 * @version: 1.0.5 - 2021-03-15
 * @license: http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package: prestashop 1.6 - 1.7
 */

namespace mdg\salerestriction\core\Forms;

abstract class ObjectForm
{
    /** @var object \Context instanciated */
    public $context;

    /** @var object \legacyContext instanciated */
    public $legacyContext;

    /** @var object \Languages Collection */
    public $local;

    /** @var object the main object of the form */
    public $object;

    /** @var object this module */
    public $module;

    /** @var string name of the controller that loads the form  */
    public $controller_name;

    /** @var string name of the current form file needed, especially for translations */
    public $form_name;

    /** @var string action of form */
    public $form_action;

    /** @var array attributs and values of the objet */
    public $fields_value;

    /** @var array form structure */
    public $fields_form;

    /** @var string */
    public $currentToken;

    /** @var string */
    public $currentIndex;

    /**
     * Declare values common to all forms
     *
     * @param string
     * @param object
     */
    public function __construct($object = null, $legacyContext = null)
    {
        $this->context = \Context::getContext();
        $this->module = \Module::getInstanceByName(basename(realpath(dirname(__FILE__) . '/../../..')));
        $this->controller_name = $this->context->controller->controller_name;

        if ($legacyContext) {
            $this->legacyContext = $legacyContext;
            $this->locales = $this->legacyContext->getLanguages();
        }
    }

    /** Fill the necessary variables
     *  If we are in the context of a FormHelper
     *  We fill the parameters with those entered in the adminModuleController currently used
     *
     * @param string path|name of this children file
     * @param int|object main object of form
     *
     * @return void
     */
    public function constructFormHelper($filePath, $object = null)
    {
        if (is_object($object)) {
            $className = get_class($object);
            $this->identifier = $className::$definition['primary'];
            $this->table = $className::$definition['table'];
            $this->object = $object;
        } else {
            $className = $this->context->controller->className;
            $this->table = $this->context->controller->table;
            $this->identifier = $className::$definition['primary'];
            $this->object = new $className($object);
        }

        $this->currentToken = \Tools::getValue('token');
        $this->currentIndex = "index.php?controller={$this->controller_name}";

        $this->form_name = basename($filePath, '.php');
        $this->form_action = "submit_{$this->form_name}";
    }

    /**
     * Fill the necessary variables
     * You must declare the parameters in the child constructor which instantiates this object
     *
     * @param string path|name of this children file
     * @param string main object::class_name of form
     * @param int|object main object of form
     *
     * @return void
     */
    public function constructFormBuilder($filePath, $className, $object = null)
    {
        $this->table = $className::$definition['table'];
        $this->identifier = $className::$definition['primary'];
        $this->object = is_object($object) ? $object : $className::getInstanceByIdObject($object);

        $this->form_name = basename($filePath, '.php');
        $this->form_action = "submit_{$this->form_name}";
    }

    public function setMedia()
    {
    }

    /**
     * Add entries to the Symfony Formbuilder
     * @since PS 1.7.6
     *
     * @param array params du hook
     *
     * @return void
     */
    public function modifyFormBuilder(&$params)
    {
    }

    /**
     * Add entries to the Prestashop Form
     *
     * @param array params du hook
     *
     */
    public function modifyFormHelper(&$params)
    {
    }

    /**
     * Returns data for AdminModuleController :: renderForm
     * @see AdminModuleController
     *      public function renderForm()
     *      {
     *          foreach ((new BlockForm($this->object))->modifyControllerFormHelper() as $key => $value) {
     *              $this->$key = $value;
     *          }
     *          return parent::renderForm();
     *      }
     *
     * @return array
     */
    public function modifyControllerFormHelper()
    {
        /**
         * Exemple:
         *   $this->fields_value = (array) $this->object;
         *   $this->multiple_fieldsets = true;
         *   $this->fields_form = [];
         *   $this->fields_form[] = [
         *       'form' => [
         *           'legend' => [
         *               'title' => $this->module->l('Sample', $this->form_name),
         *               'icon' => 'icon-align-left',
         *           ],
         *           'input' => [
         *               [
         *                   'type' => 'text',
         *                   'label' => $this->module->l('Title', $this->form_name),
         *                   'name' => 'title',
         *                   'lang' => true,
         *                   'required' => true,
         *               ],
         *           ],
         *       ],
         *   ];
         */

        $output = [
            'multiple_fieldsets' => isset($this->multiple_fieldsets) ? $this->multiple_fieldsets : count($this->fields_form),
            'fields_value' => $this->fields_value ? $this->fields_value : (array) $this->object,
            'fields_form' => $this->fields_form,
        ];
        return $output;
    }

    /**
     * Returns the data formatted for the selects of Symfony Formbuilder
     *
     * @param array couple [id => value, ...]
     *
     * @return array
     */
    public function getBuilderFormChoices(array $params)
    {
        $output = [];

        foreach ($params as $id => $value) {
            $output[$id] = $this->module->l($value, $this->form_name);
        }

        return $output;
    }

    /**
     * Returns the data formatted for the selects of Prestashop HelperForm
     *
     * @param array couple [id => value, ...]
     *
     * @return array
     */
    public function getHelperFormChoices(array $params)
    {
        $output = [];

        foreach ($params as $id => $value) {
            $output[] = ['id' => $id, 'name' => $this->module->l($value, $this->form_name)];
        }

        return [
            'id' => 'id',
            'name' => 'name',
            'query' => $output,
        ];
    }

    /**
     * Returns the module index
     *
     * @return string
     */
    public function getModuleIndex()
    {
        return $this->context->link->getAdminLink('AdminModules', true) . "&configure={$this->module->name}&tab_module={$this->module->tab}&module_name={$this->module->name}";
    }
}

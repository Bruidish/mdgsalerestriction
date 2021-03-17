<?php
/**
 * Gère le formulaire du model Product
 *
 * @author:  Michel Dumont <michel.dumont.io>
 * @version: 1.0.0 - 2020-11-11
 * @license: http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package: prestashop 1.7
 */

namespace mdg\salerestriction\core\Forms;

use PrestaShopBundle\Form\Admin\Type\DatePickerType;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use PrestaShopBundle\Form\Admin\Type\TranslateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use \mdg\salerestriction\Models\ProductModel;

class ProductForm extends \mdg\salerestriction\Forms\ObjectForm
{
    /**
     * @inheritdoc
     */
    public function __construct($object = null, $legacyContext = null)
    {
        parent::__construct($object, $legacyContext);
        parent::constructFormBuilder(__FILE__, ProductModel::class, $object);
    }

    /** Retourne le formulaire pour la page produit */
    public function buildForm($builder)
    {
        return $builder
            ->createNamedBuilder($this->module->name, FormType::class, $this->object)
            ->add('limit', TextType::class,
                [
                    'label' => $this->module->l('limit sales by customer', $this->form_name),
                    'required' => false,
                    'data' => $this->object->limit,
                    'help' => $this->module->l('Leave 0 to disallow', $this->form_name),
                ]
            )
            ->add('date_from', DatePickerType::class,
                [
                    'label' => $this->module->l('Date start of limit', $this->form_name),
                    'required' => false,
                    'data' => $this->object->date_from,
                    'help' => $this->module->l('Leave blanck to ignore', $this->form_name),
                ]
            )
            ->add('date_to', DatePickerType::class,
                [
                    'label' => $this->module->l('Date end of limit', $this->form_name),
                    'required' => false,
                    'data' => $this->object->date_to,
                    'help' => $this->module->l('Leave blanck to ignore', $this->form_name),
                ]
            )
            ->add('text_available', TranslateType::class,
                [
                    'label' => $this->module->l('Message if sale is available', $this->form_name),
                    'type' => FormattedTextareaType::class,
                    'locales' => $this->locales,
                    'hideTabs' => false,
                    'required' => false,
                    'options' => ['limit' => 200],
                    'data' => $this->object->text_available,
                ]
            )
            ->add('text_notavailable', TranslateType::class,
                [
                    'label' => $this->module->l('Message if sale is not available', $this->form_name),
                    'type' => FormattedTextareaType::class,
                    'locales' => $this->locales,
                    'hideTabs' => false,
                    'required' => false,
                    'options' => ['limit' => 200],
                    'data' => $this->object->text_notavailable,
                ]
            )
            ->getForm();
    }

    /** Traite l'enregistrement du formulaire de la page produit
     *
     * @param array datas à enregistrer
     *
     * @return bool
     */
    public function processForm($formData)
    {
        $output = true;

        // Save datas
        foreach ($this->object::$definition['fields'] as $fieldName => $fieldParams) {
            $this->object->{$fieldName} = (isset($formData[$fieldName]) ? $formData[$fieldName] : $this->object->{$fieldName});
        }

        $output &= $this->object->save();

        return $output;
    }
}

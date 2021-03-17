<?php
/**
 * @author:  Michel Dumont <michel.dumont.io>
 * @version: 1.0.0 - 2021-01-27
 * @license: http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package: prestashop 1.6
 */

namespace mdg\salerestriction\v17\Traits;

use mdg\salerestriction\Forms\ProductForm;
use mdg\salerestriction\Models\ProductModel;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait HookTrait
{
    /**
     * Override product properties to modify sale option
     *
     * @inheritdoc
     */
    public function hookActionGetProductPropertiesAfter(array &$params)
    {
        $productModel = ProductModel::getInstanceByIdObject($params['product']['id_product'], false);
        if ($productModel->active) {
            $available_for_order = $productModel->getIsAvailableForOrder((int) $this->context->customer->id, (int) $this->context->cart->id);
            $params['product']['available_for_order'] &= $available_for_order;
            $params['product'][$this->name] = [
                'soldQuantity' => $productModel->soldQuantity,
                'wantedQuantity' => $productModel->wantedQuantity,
                'limitQuantity' => $productModel->limit,
                'date_from' => $productModel->date_from,
                'date_to' => $productModel->date_to,
                'text_available' => $productModel->text_available[$this->context->language->id],
                'text_notavailable' => $productModel->text_notavailable[$this->context->language->id],
                'available_for_order' => $available_for_order,
            ];
        }

    }

    /**
     * Display messages about product availability
     *
     * @inheritdoc
     */
    public function hookDisplayProductActions()
    {
        return $this->fetch("module:{$this->name}/views/templates/hook/v17/dspProductAction.tpl");
    }

    /**
     * @inheritdoc
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        if (!in_array($this->context->controller->php_self, ['product'])) {
            return;
        }

        $this->context->controller->registerStylesheet("module-{$this->name}-front-css", "modules/{$this->name}/views/css/front.css");
        $this->context->controller->registerJavascript("module-{$this->name}-front-js", "modules/{$this->name}/views/js/front.min.js", ['priority' => 999, 'attribute' => 'async']);
    }

    #region BO PRODUCT
    /**
     * Generates the form in the product sheet
     *
     * @inheritdoc
     */
    public function hookDisplayAdminProductsOptionsStepBottom(array $params)
    {
        $productId = (int) $params['id_product'];
        $kernel = SymfonyContainer::getInstance();

        $twig = $kernel->get('twig');
        $formFactory = $kernel->get('form.factory');
        $legacyContext = $this->get('prestashop.adapter.legacy.context');

        $productForm = new ProductForm($productId, $legacyContext);

        return $twig->render(_PS_MODULE_DIR_ . "{$this->name}/views/templates/admin/product-options-step-bottom.html.twig", [
            'form' => $productForm->buildForm($formFactory)->createView(),
        ]);
    }

    /**
     * CRUD on product's datas
     *
     * @inheritdoc
     */
    public function hookActionObjectProductAddAfter(array $params)
    {
        $productId = (int) $params['object']->id;
        $productForm = new ProductForm($productId);

        return $productForm->processForm(\Tools::getValue($this->name));
    }
    public function hookActionObjectProductUpdateAfter($params)
    {
        return $this->hookActionObjectProductAddAfter($params);
    }
    public function hookActionObjectDeleteUpdateAfter($params)
    {
        $productId = (int) $params['object']->id;
        $object = ProductModel::getInstanceByIdObject($productId);

        return $object->delete();
    }
    #endregion BO PRODUCT

}

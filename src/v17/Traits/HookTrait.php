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
        $productModelDatas = $this->_getCurrentProductModel($params['product']['id_product'], (int) $this->context->customer->id, (int) $this->context->cart->id, (int) $this->context->language->id);
        if ($productModelDatas) {
            $params['product']['available_for_order'] &= $productModelDatas['available_for_order'];
            $params['product'][$this->name] = $productModelDatas;
        }
    }

    /**
     * Return data for a product considering customer and cart
     *
     * @param int
     * @param int
     * @param int
     * @param int
     *
     * @return array|false
     */
    private function _getCurrentProductModel($idProduct, $idCustomer, $idCart, $idLang)
    {
        $productModel = ProductModel::getInstanceByIdObject($idProduct, false);
        if (!$productModel->active) {
            return false;
        }

        $available_for_order = $productModel->getIsAvailableForOrder($idCustomer, $idCart);
        $output = [
            'soldQuantity' => $productModel->soldQuantity,
            'wantedQuantity' => $productModel->wantedQuantity,
            'limitQuantity' => $productModel->limit,
            'date_from' => $productModel->date_from,
            'date_to' => $productModel->date_to,
            'text_available' => $productModel->text_available[$idLang],
            'text_notavailable' => $productModel->text_notavailable[$idLang],
            'available_for_order' => $available_for_order,
            'quickViewModal' => (bool) (\Tools::getValue('action') == 'quickview'),
        ];

        return $output;
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
        if (!in_array($this->context->controller->php_self, ['product', 'category', 'search', 'cart'])) {
            return;
        }

        if ($this->context->controller->php_self == 'cart') {
            $productsLimit = [];
            foreach ($this->context->cart->getProducts() as $product) {
                $productModelDatas = $this->_getCurrentProductModel($product['id_product'], (int) $this->context->customer->id, (int) $this->context->cart->id, (int) $this->context->language->id);
                $productsLimit[$product['id_product']] = $productModelDatas;
            };

            \Media::addJsDef([$this->name => $productsLimit]);
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

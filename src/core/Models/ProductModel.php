<?php
/**
 * @author Michel Dumont <michel.dumont.io>
 * @version 1.0.0 - 2021-03-15
 * @copyright 2020
 * @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package prestashop 1.7
 */

namespace mdg\salerestriction\core\Models;

if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

class ProductModel extends \mdg\salerestriction\Models\ObjectModel
{
    /** @var int id de l'obket Prestashop associé */
    public $id_object;

    /** @var bool limit of purchase for a customer */
    public $limit;

    /** @var string start date of the limitation */
    public $date_from;

    /** @var string ed date of the limitation */
    public $date_to;

    /** @var string message to explain the limit [lang] */
    public $text_available;

    /** @var string message to warn the limit is reached [lang] */
    public $text_notavailable;

    /**
     * @var boolean
     * @see static::__construct()
     */
    public $active;

    /**
     * @var int
     * @see static::getIsAvailableForOrder()
     */
    public $soldQuantity;

    /**
     * @var int
     * @see static::getIsAvailableForOrder()
     */
    public $wantedQuantity;

    public static $definition = [
        'table' => 'mdgsalerestriction_product',
        'primary' => 'id_association',
        'multilang' => true,
        'multi_shop' => true,
        'fields' => [
            'id_object' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'limit' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_from' => ['type' => self::TYPE_DATE],
            'date_to' => ['type' => self::TYPE_DATE],
            'text_available' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'lang' => true],
            'text_notavailable' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'lang' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);

        $dateNow = date('Y-m-d');

        $this->date_from = $this->date_from == '0000-00-00 00:00:00' ? null : $this->date_from;
        $this->date_to = $this->date_to == '0000-00-00 00:00:00' ? null : $this->date_to;
        $this->active = ($this->limit && (!$this->date_from || date('Y-m-d', strtotime($this->date_from)) <= $dateNow) && (!$this->date_to || date('Y-m-d', strtotime($this->date_to)) >= $dateNow));
    }

    /** Instantiates this class to the associated Prestashop object
     *
     * @param int id de l'object associé
     *
     * @return self
     */
    public static function getInstanceByIdObject($idObject, $autoCreate = true)
    {
        $id = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue("SELECT " . static::$definition['primary'] . " FROM " . _DB_PREFIX_ . static::$definition['table'] . " WHERE id_object={$idObject}");

        if (!$id && $autoCreate) {
            $that = new self();
            $that->id_object = $idObject;
            $that->add();
            $id = $that->id;
        }

        return new self($id);
    }

    /**
     * Check if the product is available for order concidering only this module params
     *
     * @param int
     * @param int
     *
     * @return bool
     */
    public function getIsAvailableForOrder($idCustomer, $idCart)
    {
        if ($this->active) {
            $countSaleQuery = new \DbQuery();
            $countSaleQuery->select('SUM(od.product_quantity)');
            $countSaleQuery->from('order_detail', 'od');
            $countSaleQuery->innerJoin('orders', 'o', 'o.id_order=od.id_order');
            $countSaleQuery->where("o.valid=1");
            $countSaleQuery->where("o.id_customer={$idCustomer}");
            $countSaleQuery->where("od.product_id={$this->id_object}");
            if ($this->date_from) {
                $countSaleQuery->where("o.date_upd>='{$this->date_from}'");
            }
            if ($this->date_to) {
                $countSaleQuery->where("o.date_upd<='{$this->date_to}'");
            }
            $this->soldQuantity = (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($countSaleQuery);

            $countCartQuery = new \DbQuery();
            $countCartQuery->select('SUM(cp.quantity)');
            $countCartQuery->from('cart_product', 'cp');
            $countCartQuery->where("cp.id_cart={$idCart}");
            $countCartQuery->where("cp.id_product={$this->id_object}");
            $this->wantedQuantity = (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($countCartQuery);

            return (bool) (($this->soldQuantity + $this->wantedQuantity) < (int) $this->limit);
        }

        return true;
    }
}

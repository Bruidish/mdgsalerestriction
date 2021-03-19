{**
 * @author Michel Dumont <michel.dumont.io>
 * @version 1.0.0 - 2021-03-16
 * @copyright 2021
 * @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package prestashop 1.7
 *}

{if isset($product.mdgsalerestriction)}
    <div
        id="mdgsalerestriction-wrap"
        class="flex-fill"
        data-id_product="{$product.id}"
        data-limit_quantity="{$product.mdgsalerestriction.limitQuantity}"
        data-sold_quantity="{$product.mdgsalerestriction.soldQuantity}"
        data-wanted_quantity="{$product.mdgsalerestriction.wantedQuantity}"
        >

        <div class="availableForOrder{if !$product.mdgsalerestriction.available_for_order} d-none{/if}">
            {$product.mdgsalerestriction.text_available nofilter}
        </div>
        <div class="notAvailableForOrder{if $product.mdgsalerestriction.available_for_order} d-none{/if}">
            {$product.mdgsalerestriction.text_notavailable nofilter}
        </div>
    </div>

    {if $product.mdgsalerestriction.quickViewModal}
        <script>
            $(function () {
                new MdgSaleRestrictionProduct('#mdgsalerestriction-wrap');
            })
        </script>
    {/if}
{/if}


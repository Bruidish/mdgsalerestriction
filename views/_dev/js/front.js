/**
 * @author Michel Dumont <michel.dumont.io>
 * @version 1.0.0 - 2021-01-28
 * @copyright 2021
 * @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package prestashop 1.6 - 1.7
 */

class MdgSaleRestriction {
  constructor() {
    this.$wrapper = $('#mdgsalerestriction-wrap');
    if (!this.$wrapper.length) {
      return false;
    }

    this.idProduct = parseInt(this.$wrapper.data('id_product'));
    this.soldQuantity = parseInt(this.$wrapper.data('sold_quantity'));
    this.wantedQuantity = parseInt(this.$wrapper.data('wanted_quantity'));
    this.limitQuantity = parseInt(this.$wrapper.data('limit_quantity'));

    $('#quantity_wanted').attr('max', this.limitQuantity - (this.wantedQuantity + this.soldQuantity));

    this.events();
  }

  events() {
    // Update product action when cart is updated
    prestashop.on('updateCart', (e) => {
      this.wantedQuantity = 0;

      for (var i = 0; i < e.resp.cart.products.length; i++) {
        if (e.resp.cart.products[i].id_product == this.idProduct) {
          this.wantedQuantity += parseInt(e.resp.cart.products[i].quantity_wanted)
        }
      }

      this.render();
    });

    // Limit quantity
    $("body").on("change keyup", "#quantity_wanted", (e) => {
      var $el = $(e.currentTarget);
      if ($el.val() > this.limitQuantity - (this.wantedQuantity + this.soldQuantity)) {
        $el.val(this.limitQuantity - (this.wantedQuantity + this.soldQuantity));
      }
    });
  }

  render() {
    $('#quantity_wanted').attr('max', this.limitQuantity - (this.wantedQuantity + this.soldQuantity));
    $('.product-add-to-cart .add button').attr('disabled', this.wantedQuantity + this.soldQuantity >= this.limitQuantity);
    $('.product-add-to-cart .availableForOrder').toggleClass('d-none', this.wantedQuantity + this.soldQuantity >= this.limitQuantity);
    $('.product-add-to-cart .notAvailableForOrder').toggleClass('d-none', this.wantedQuantity + this.soldQuantity < this.limitQuantity);
  }

}

$(function () {
  new MdgSaleRestriction;
})
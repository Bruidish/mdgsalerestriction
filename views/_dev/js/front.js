/**
 * @author Michel Dumont <michel.dumont.io>
 * @version 1.0.0 - 2021-01-28
 * @copyright 2021
 * @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @package prestashop 1.6 - 1.7
 */

/**
 * Regroups method in the Product page
 */
class MdgSaleRestrictionProduct {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.$wrapper = $(this.wrapper);
    if (!this.$wrapper.length) {
      return false;
    }

    this.idProduct = parseInt(this.$wrapper.data('id_product'));
    this.soldQuantity = parseInt(this.$wrapper.data('sold_quantity'));
    this.wantedQuantity = parseInt(this.$wrapper.data('wanted_quantity'));
    this.limitQuantity = parseInt(this.$wrapper.data('limit_quantity'));

    this.render();
    this.events();
  }

  events() {
    // Update product action and messages when cart is updated
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
    $(document).on("change keyup", "#quantity_wanted", (e) => {
      var $el = $(e.currentTarget);
      if ($el.val() > this.limitQuantity - (this.wantedQuantity + this.soldQuantity)) {
        $el.val(this.limitQuantity - (this.wantedQuantity + this.soldQuantity))
        return false
      }
    });
  }

  render() {
    var notAvailable = this.wantedQuantity + this.soldQuantity >= this.limitQuantity
    $('#quantity_wanted').attr('disabled', notAvailable)
    $('.product-add-to-cart .add button').attr('disabled', notAvailable)
    $('.product-add-to-cart .availableForOrder').toggleClass('d-none', notAvailable)
    $('.product-add-to-cart .notAvailableForOrder').toggleClass('d-none', !notAvailable)
  }
}


/**
 * Regroups method in the Shoppingcart page
 */
class MdgSaleRestrictionCart {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.$wrapper = $(this.wrapper);
    if (!this.$wrapper.length || typeof mdgsalerestriction == 'undefined') {
      return false;
    }

    this.render();
    this.events();
  }

  render() {
    $.each($('input.js-cart-line-product-quantity', this.$wrapper), function (index, el) {
      var $el = $(el);
      var $li = $el.closest('li');

      if (mdgsalerestriction[$el.data('product-id')]) {
        var maxWantedQuantity = (mdgsalerestriction[$el.data('product-id')].limitQuantity - mdgsalerestriction[$el.data('product-id')].soldQuantity)

        $li.find('.mdgsalerestriction-message').remove();
        $li.find('.product-line-grid-body').append(`
            <div class="product-line-info mdgsalerestriction-message">
              ${mdgsalerestriction[$el.data('product-id')].text_available}
            </div>
          `);

        $el.on('touchspin.on.startupspin keyup', () => {
          if ($el.val() > maxWantedQuantity) {
            $el.val(maxWantedQuantity)
            return false
          }
        })
      }
    })
  }

  events() {
    // Update product action and messages when cart is updated
    prestashop.on('updatedCart', (e) => {
      this.$wrapper = $(this.wrapper);
      this.render();
    });
  }
}

$(function () {
  new MdgSaleRestrictionProduct('#mdgsalerestriction-wrap');
  new MdgSaleRestrictionCart('.cart-overview');
})
define(
    ['jquery', 'mage/calendar'],
    function ($) {
        'use strict';

        return function (config) {
            $('body').trigger('processStart');

            $.ajax({
                url: config.url,
                method: 'GET',
                showLoader: true,
                data: {shipping_method: config.shippingmethod, order_id: config.orderid}
            }).done(function (response) {
                if (response.error === 0) {
                    $('.order-payment-method + .order-shipping-address').append(response.html);

                    if ($('#expiration_date').length) {
                        $('#expiration_date').calendar({
                            changeYear: true,
                            changeMonth: true,
                            yearRange: new Date().getFullYear() + ":2050",
                            buttonText: $.mage.__('Select Date'),
                            dateFormat: "dd-mm-yy"
                        });
                    }
                }
            }).always(function () {
                $('body').trigger('processStop');
            });
        }
    }
);

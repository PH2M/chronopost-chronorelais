define(
    [
        "uiComponent",
        'ko',
        'Chronopost_Chronorelais/js/view/checkout/relais'
    ],
    function (
        Component,
        ko,
        Relais
    ) {
        'use strict';
        return Component.extend({
            address: Relais.relaisAddress,
            defaults: {
                template: 'Chronopost_Chronorelais/checkout/sidebar/info_relais'
            },
            initialize: function () {
                this._super(); //you must call super on components or they will not render
            },
            getRelaisAddress: function () {
                var address2 = Relais.relaisAddress();
                return address2 ? address2.company + '<br />' + address2.street + ' ' + address2.postcode + ' ' + address2.city : '';
            }
        });
    }
);

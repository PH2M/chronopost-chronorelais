define(
    [
        "uiComponent",
        'ko',
        'Chronopost_Chronorelais/js/view/checkout/rdv'
    ],
    function (
        Component,
        ko,
        Rdv
    ) {
        'use strict';
        return Component.extend({
            rdvInfo: Rdv.rdvInfo,
            defaults: {
                template: 'Chronopost_Chronorelais/checkout/sidebar/info_rdv'
            },
            initialize: function () {
                this._super(); //you must call super on components or they will not render
            },
            getRdvInfo: function () {
                var rdvInfo = Rdv.rdvInfo();
                return rdvInfo ? rdvInfo.deliveryDate : '';
            }
        });
    }
);

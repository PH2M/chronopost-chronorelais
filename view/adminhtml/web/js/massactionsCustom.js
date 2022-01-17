define([
    'jquery',
    'underscore',
    'uiRegistry',
    'mageUtils',
], function ($, _, registry, utils) {
    'use strict';

    var mixin = {

        defaultCallback: function (action, data) {
            var itemsType = data.excludeMode ? 'excluded' : 'selected',
                selections = {};

            selections[itemsType] = data[itemsType];
            if (!selections[itemsType].length) {
                selections[itemsType] = false;
            }

            _.extend(selections, data.params || {});

            if (action.type === "imprimer_etiquettes") { // Add custom grid data
                selections.data = {};
                for (var i = 0; i < selections[itemsType].length; i++) {
                    var item_id = selections[itemsType][i];
                    var target = {};
                    target[item_id] = {};
                    target[item_id].dimensions = $("#form_" + item_id + " #order_dimensions").val();
                    target[item_id].nb_colis = $("#form_" + item_id + " input[name=\"nb_colis\"]").val();
                    target[item_id].contract_id = $("#contract-" + item_id).val();

                    target[item_id].expiration_date = null;
                    if ($(".expiration-date-" + item_id).length) {
                        target[item_id].expiration_date = $(".expiration-date-" + item_id).val();
                    }

                    target[item_id].offer = null;
                    if ($("#offers-" + item_id).length) {
                        target[item_id].offer = $("#offers-" + item_id).val();
                    }

                    selections.data = Object.assign(selections.data, target);
                }
            }

            utils.submit({
                url: action.url,
                data: selections,
            });
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});

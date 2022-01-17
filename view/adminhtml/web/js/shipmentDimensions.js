require([
    'jquery'
], function ($) {
    'use strict';

    var fn = function () {
        buildDimensionsJson();

        $(".adminhtml-order_shipment-new #edit_form, .adminhtml-order-shipment-new #edit_form").on('save', function () {
            buildDimensionsJson();

            return false;
        });

        function buildDimensionsJson()
        {
            var dimensions = {};

            var weights = $('.dimensions-input-container input[name="weight_input"]');
            var widths = $('.dimensions-input-container input[name="width_input"]');
            var heights = $('.dimensions-input-container input[name="height_input"]');
            var lengths = $('.dimensions-input-container input[name="length_input"]');

            for (var i = 0; i < weights.length; i++) {
                var dimension = {};
                dimension.weight = $(weights[i]).val();
                dimension.width = $(widths[i]).val();
                dimension.height = $(heights[i]).val();
                dimension.length = $(lengths[i]).val();
                dimensions[i] = dimension;
            }

            $('#input_dimensions').val(JSON.stringify(dimensions));
        }
    };

    fn();
});

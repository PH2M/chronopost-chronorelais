define(
    ['jquery', 'weightAndDimensions'],
    function ($, weightAndDimensions) {
        'use strict';

        var body = $('body');

        body.on("change", "select[id^='contract-']", function () {
            var entity_id = $(this).data('entityid');
            var form = $('#form_' + entity_id);
            form.find('input[name="contract"]').val($(this).val());
        });

        body.on("change", "select[id^='offers-']", function () {
            var entity_id = $(this).data('entityid');
            var form = $('#form_' + entity_id);
            form.find('input[name="offer"]').val($(this).val());
        });

        body.on("change", ".expiration-date", function () {
            var entity_id = $(this).data('entityid');
            var form = $('#form_' + entity_id);
            form.find('input[name="expiration_date"]').val($(this).val());
        });

        body.on('click', "form[id^='form_'] > button[type='submit']", function () {
            var form = $(this).parent();
            var id = form.find('input[name="order_id"]').val();
            if ($("#messages > .id-" + id).length !== 0) {
                return false;
            }
        });
    }
);

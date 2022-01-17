define([
    'jquery'
], function ($) {
    'use strict';

    var body = $('body');

    body.on('change', '#label_return_address', function () {
        $('#label_return_address_value').val($(this).val());
    });

    body.on('mousedown', 'a.label_return_link', function (e) {
        e.preventDefault();
        e.stopPropagation();

        let value = $('#label_return_address_value').val();

        var currentUrl = $(this).attr('href');
        var url = new URL(currentUrl);
        url.searchParams.set("recipient_address_type", value);
        $(this).attr('href', url.href);

        return false;
    });
});

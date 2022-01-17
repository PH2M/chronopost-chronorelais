define([
    'jquery'
], function ($) {
    'use strict';

    return function () {
        $(document).ready(function () {
            $('.chrono-clean-button').on('click', function () {
                var idButton = $(this).attr('id');
                var splits = idButton.split("_");
                if (splits && splits[1]) {
                    var section = splits[1];

                    var inputs = $('#chronorelais_' + section + ' input');
                    inputs.each(function () {
                        $(this).val('');
                    });

                    var selects = $('#chronorelais_' + section + ' select');
                    selects.each(function () {
                        $(this).prop("selectedIndex", 0);
                    });
                }
            });
        });
    }
});



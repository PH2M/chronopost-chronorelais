define(
    ['ko'],
    function (ko) {
        'use strict';
        var relaisAddress = ko.observable(null);
        return {
            relaisAddress: relaisAddress
        };
    }
);

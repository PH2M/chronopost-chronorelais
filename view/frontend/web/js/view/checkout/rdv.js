define(
    ['ko'],
    function (ko) {
        'use strict';
        var rdvInfo = ko.observable(null);
        return {
            rdvInfo: rdvInfo
        };
    }
);

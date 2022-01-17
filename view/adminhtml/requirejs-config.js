var config = {
    deps: [
        "Chronopost_Chronorelais/js/initLinkRetour",
        "Chronopost_Chronorelais/js/initLinkContract",
        "Chronopost_Chronorelais/js/weightAndDimensions",
        "Chronopost_Chronorelais/js/shipmentNew",
        "Chronopost_Chronorelais/js/shipmentDimensions"
    ],
    map: {
        "*": {
            weightAndDimensions : "Chronopost_Chronorelais/js/weightAndDimensions"
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/grid/massactions': {
                'Chronopost_Chronorelais/js/massactionsCustom': true
            }
        }
    },
    shim: {
        "Chronopost_Chronorelais/js/initLinkRetour": {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/initLinkContract": {
            deps: ["jquery", "weightAndDimensions"]
        },
        "Chronopost_Chronorelais/js/contracts": {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/cleanInformations": {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/weightAndDimensions":  {
            deps: ["jquery"]
        },
        "Chronopost_Chronorelais/js/shipmentNew":  {
            deps: ["jquery", "mage/calendar"]
        },
        "Chronopost_Chronorelais/js/shipmentDimensions":  {
            deps: ["jquery"]
        }
    }
};

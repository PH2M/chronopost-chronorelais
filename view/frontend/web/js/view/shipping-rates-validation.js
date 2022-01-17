define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        '../model/shipping-rates-validator',
        '../model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        chronopostShippingRatesValidator,
        chronopostShippingRatesValidationRules
    ) {
        "use strict";

        defaultShippingRatesValidator.registerValidator('chronopost', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronopost', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronopostc10', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronopostc10', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronopostc18', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronopostc18', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronoexpress', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronoexpress', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronosameday', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronosameday', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronorelais', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronorelais', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronorelaiseur', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronorelaiseur', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronorelaisdom', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronorelaisdom', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronopostsrdv', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronopostsrdv', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronocclassic', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronocclassic', chronopostShippingRatesValidationRules);

        defaultShippingRatesValidator.registerValidator('chronofresh', chronopostShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('chronofresh', chronopostShippingRatesValidationRules);

        return Component;
    }
);

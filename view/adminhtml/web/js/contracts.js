define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return function (config) {

        function buildContractContainer()
        {
            var main_container = $('#main-container');
            var nbContracts = $('.container-contract').length;

            var clone = $('#container-contract').clone();
            clone.removeClass("item-0");
            clone.addClass("item-" + nbContracts);

            var temp = "<button class=\"scalable deleteContract\" type=\"button\" id=\"" + nbContracts + "\"><span>" + config.labelDeleteButton + "</span></button>";
            clone.find('button.createContract').remove();
            clone.find('button.checkContract').after(temp);
            main_container.append(clone);

            var inputs = document.querySelectorAll('.item-' + nbContracts + ' input');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].value = "";
            }
        }

        function deleteContract(button)
        {
            var elemContainer = '.container-contract.item-' + $(button).attr('id');
            $(elemContainer).remove();

            buildConfigObject();
        }

        function checkLogin(button)
        {
            var container = button.parentNode;
            var reg = new RegExp('item-[0-9]+');
            var result = reg.exec(container.classList);
            if (result) {
                var account_number = document.querySelector('.' + result[0] + ' #chronorelais_shipping_account_number').value;
                var account_sub_number = document.querySelector('.' + result[0] + ' #chronorelais_shipping_sub_account_number').value;
                var account_pass = document.querySelector('.' + result[0] + ' #chronorelais_shipping_account_pass').value;

                var params = {
                    account_number: account_number,
                    sub_account_number: account_sub_number,
                    account_pass: account_pass
                };

                new Ajax.Request(config.ajaxUrl, {
                    parameters: params,
                    onSuccess: function (data) {
                        var response = data.responseText.evalJSON();
                        response = response.return;
                        var msg = '<strong style="color:#007700;">' + $.mage.__('The contract is valid.') + '</strong>';
                        if (response.errorCode === undefined) {
                            msg = '<strong>' + $.mage.__('The webservice is temporarily inaccessible. Please try again later.') + '</strong>';
                        } else if (response.errorCode !== 0) {
                            msg = '<strong style="color:#FF0000;">' + $.mage.__('The contract is invalid.') + '</strong>'
                        }
                        document.querySelector('.' + result[0] + ' #validation_result').update(msg);
                    }
                });
            }
        }

        function buildConfigObject()
        {
            var config = {};

            var contractsNames = document.getElementsByClassName('contract_name');
            var contractsNumbers = document.getElementsByClassName('contract_number');
            var contractsSubAccounts = document.getElementsByClassName('contract_sub_account');
            var contractsPass = document.getElementsByClassName('contract_pass');

            for (var i = 0; i < contractsNames.length; i++) {
                var contrat = {};
                contrat.name = contractsNames[i].value;
                contrat.number = contractsNumbers[i].value;
                contrat.subAccount = contractsSubAccounts[i].value;
                contrat.pass = contractsPass[i].value;
                if (!(contrat.name === "" && contrat.number === "" && contrat.subAccount === "" && contrat.pass === "")) {
                    config[i] = contrat;
                }
            }

            $('#chronorelais_contracts_contracts').val(JSON.stringify(config));
        }

        function _init()
        {
            var checkbox = $$('#row_chronorelais_contracts_contracts .use-default input');
            var inputCheckbox = checkbox[0];
            if (inputCheckbox) {
                if (inputCheckbox.checked === true) {
                    disableInputs();
                    disableButtons();
                }
            }
        }

        function disableInputs()
        {
            var inputs = $$('#row_chronorelais_contracts_contracts .value input');
            inputs.forEach(function (element) {
                element.disabled = true;
                element.classList.add('disabled');
            });
        }

        function disableButtons()
        {
            var buttons = $$('#row_chronorelais_contracts_contracts .value button');
            buttons.forEach(function (element) {
                element.disabled = true;
                element.classList.add('disabled');
            });
        }

        function debounce(fn, delay)
        {
            return function () {
                fn.args = arguments
                fn.timeout_id && clearTimeout(fn.timeout_id)
                fn.timeout_id = setTimeout(function () {
                    return fn.apply(fn, fn.args);
                }, delay);
            };
        }

        $(document).ready(function () {
            _init();

            $('#main-container').on('keyup', 'input.contractfield', debounce(function () {
                buildConfigObject();
            }, 350));

            $('#row_chronorelais_contracts_contracts').on('click', 'button.createContract', function () {
                buildContractContainer();
            });

            $('#main-container').on('click', 'button.deleteContract', function () {
                deleteContract(this);
            });

            $('#main-container').on('click', 'button.checkContract', function () {
                checkLogin(this);
            });
        });
    }
});

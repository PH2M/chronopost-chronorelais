define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-service',
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Chronopost_Chronorelais/js/view/checkout/map',
    'mage/template',
    'Magento_Checkout/js/model/full-screen-loader',
    'Chronopost_Chronorelais/js/view/checkout/relais',
    'Chronopost_Chronorelais/js/view/checkout/rdv',
    'Chronopost_Chronorelais/js/jquery.bxslider.min'
], function (
    Component,
    shippingService,
    $,
    urlBuilder,
    quote,
    leafletMap,
    mageTemplate,
    Loader,
    Relais,
    Rdv
) {
    'use strict';

    return Component.extend({
        templateHtml: {
            logo: "<img src='<%- data.methodLogo %>' style='vertical-align:middle;' alt='<%- data.label %>'/>",
            point_relais: '<li>' +
                '<input name="shipping_method_chronorelais" type="radio" value="<%- data.identifiantChronopostPointA2PAS %>" id="s_method_chronorelais_<%- data.identifiantChronopostPointA2PAS %>" class="radio" <%- data.checked %>/>' +
                '<label for="s_method_chronorelais_<%- data.identifiantChronopostPointA2PAS %>"><%- data.nomEnseigne %> - <%- data.adresse1 %> - <%- data.codePostal %> - <%- data.localite %></label>' +
                '</li>'
        },
        shippingRates: shippingService.getShippingRates(),
        shippingMethod: quote.shippingMethod,
        logos: {},
        carrierLogosUrl: urlBuilder.build("chronorelais/ajax/getcarrierslogos"),
        getRelaisUrl: urlBuilder.build("chronorelais/ajax/getrelais"),
        getSaturdayUrl: urlBuilder.build("chronorelais/ajax/getsaturdayoption"),
        resetSessionRelaisUrl: urlBuilder.build("chronorelais/ajax/resetsessionrelais"),
        setSessionRelaisUrl: urlBuilder.build("chronorelais/ajax/setsessionrelais"),
        resetSessionSaturdayOptionUrl: urlBuilder.build("chronorelais/ajax/resetsessionsaturdayoption"),
        setSessionSaturdayOptionUrl: urlBuilder.build("chronorelais/ajax/setsessionsaturdayoption"),
        leafletMap: leafletMap,
        xhrSetSessionSaturdayOption: false,
        xhrResetSessionSaturdayOption: false,
        xhrSetSessionRelais: false,
        xhrResetSessionRelais: false,
        xhrBindRelay: false,
        xhrGetSaturdayOption: false,
        xhrUpdateCarriersLogo: false,
        getPlanningUrl: urlBuilder.build("chronorelais/ajax/getplanning"),
        setSessionRdvUrl: urlBuilder.build("chronorelais/ajax/setsessionrdv"),
        resetSessionRdvUrl: urlBuilder.build("chronorelais/ajax/resetsessionrdv"),
        xhrSetSessionRdv: false,
        xhrResetSessionRdv: false,
        xhrGetPlanning: false,
        lastShippingMethod: null,
        flagBindRelay: false,

        initialize: function () {
            this._super();

            var self = this;

            self.bindSaturdayOption();
            self.resetSessionSaturdayOption(null);

            // Observer shipping rate change
            self.shippingRates.subscribe(function (rates) {
                self.updateCarriersLogo(rates);
            });

            // Observer shipping method change
            self.shippingMethod.subscribe(function (shippingMethod) {
                // To avoid twice call
                if (shippingMethod !== null) {
                    if (shippingMethod.carrier_code !== self.lastShippingMethod) {
                        self.resetSessionRelais();
                        self.resetSessionRdv();
                        self.resetSessionSaturdayOption(shippingMethod);
                    }

                    self.bindRelay(shippingMethod, false);
                    self.lastShippingMethod = shippingMethod.carrier_code;
                }
            });

            // Resize appointment container on mobile
            $(window).resize(function () {
                self.resizeAppointmentContainerMobile();
            });
        },

        updateCarriersLogo: function (rates) {
            var self = this;

            if (self.xhrUpdateCarriersLogo) {
                self.xhrUpdateCarriersLogo.abort();
            }

            self.xhrUpdateCarriersLogo = $.ajax({
                cache: true,
                url: self.carrierLogosUrl
            }).done(function (response) {
                self.logos = $.extend({}, self.logos, response);

                var timerLogoId = setInterval(() => self.updateCarrierMethodLogo(rates, timerLogoId), 1000);
            });
        },

        updateCarrierMethodLogo: function (rates, timerLogoId) {
            var self = this;

            for (var i = 0; i < rates.length; i++) {
                var carrierCode = rates[i].carrier_code;
                var methodCode = rates[i].method_code;

                if (carrierCode.indexOf("chrono") !== -1) {
                    var methodLogo = self.logos[methodCode];
                    if (methodLogo) {
                        var label = $('#label_method_' + methodCode + '_' + methodCode);
                        if (label.length) {
                            var logoHtml = mageTemplate(self.templateHtml['logo']);
                            var tmpl = logoHtml({
                                data: {
                                    label: label.text(),
                                    methodLogo: methodLogo
                                }
                            });

                            var image = label.find('img');
                            if ($(image).length === 0) {
                                label.prepend(tmpl);
                            }

                            clearInterval(timerLogoId);
                        }
                    }
                }
            }
        },

        showSaturdayInput: function (shippingMethod) {
            var self = this;

            if (self.xhrGetSaturdayOption) {
                self.xhrGetSaturdayOption.abort();
            }

            self.xhrGetSaturdayOption = $.ajax({
                url: self.getSaturdayUrl,
                data: {
                    'method_code': shippingMethod['method_code']
                }
            }).done(function (response) {
                if (response && response.content) {
                    var existingContainerSelector = $("#saturday_container_" + shippingMethod['method_code']);
                    if ($(existingContainerSelector).length <= 0) {
                        var label = $("#label_method_" + response.method_code + "_" + response.method_code);
                        var parent = label.parent('tr').get(0);
                        $(parent).after(response.content);
                    }
                } else {
                    var saturdayContainer = $('.saturday_container');
                    if (saturdayContainer.length) {
                        saturdayContainer.remove();
                        saturdayContainer = null;
                    }
                }
            }).always(function () {
                Loader.stopLoader();
            });
        },

        bindSaturdayOption: function () {
            var self = this;

            $('body').on('click', '#saturday_delivery', function () {
                var value = ($(this).prop('checked')) ? '1' : '0';

                Loader.startLoader();

                self.xhrSetSessionSaturdayOption = $.ajax({
                    url: self.setSessionSaturdayOptionUrl,
                    type: 'POST',
                    data: {
                        saturday_option: value
                    }
                }).done(function () {
                    return true;
                }).always(function () {
                    Loader.stopLoader();
                });

                return true;
            });
        },

        bindRelay: function (shippingMethod, postcode) {
            var self = this;

            if (!self.isMethodAvailable(shippingMethod['method_code'])) {
                return;
            }

            if (shippingMethod && typeof shippingMethod['method_code'] !== undefined) {
                // Get relay data
                if (shippingMethod['method_code'].indexOf("chronorelais") !== -1) {
                    self.flagBindRelay = true;
                    self.getRelay(shippingMethod, postcode);
                } else {
                    // Remove map container
                    var mapContainer = $('.chronomap_container');
                    if (mapContainer.length) {
                        mapContainer.remove();
                    }

                    // Get planning data
                    if (shippingMethod['method_code'].indexOf("chronopostsrdv") !== -1) {
                        self.getPlanning(shippingMethod);
                    }
                }
            }
        },

        getRelay: function (shippingMethod, postcode) {
            var self = this;

            // Display buttons or remove map if postcode is null
            var mapContainer = $('.chronomap_container');
            if (mapContainer.length) {
                if (!postcode) {
                    mapContainer.remove();
                    mapContainer = null;
                } else {
                    // Hide button
                    var mapPostcodeBtn = $('#map-postcode-btn');
                    if (mapPostcodeBtn.length) {
                        mapPostcodeBtn.hide();
                    }

                    // Show loader
                    var postcodePleaseWait = $('#postalcode-please-wait');
                    if (postcodePleaseWait.length) {
                        postcodePleaseWait.show();
                    }
                }
            }

            // Get relay points from shipping address
            var form = $('#co-shipping-form');
            var shippingAddressTmp = quote.shippingAddress();
            var shippingAddress = {
                "country_id": shippingAddressTmp["countryId"],
                "street": shippingAddressTmp["street"] ? shippingAddressTmp["street"] : form.find('input[name="street[0]"]').val(),
                "postcode": shippingAddressTmp["postcode"],
                "city": shippingAddressTmp["city"] ? shippingAddressTmp["city"] : form.find('input[name="city"]').val()
            };

            Loader.startLoader();

            if (self.xhrBindRelay) {
                self.xhrBindRelay.abort();
            }

            self.xhrBindRelay = $.ajax({
                url: self.getRelaisUrl,
                data: {
                    'method_code': shippingMethod['method_code'],
                    'postcode': postcode,
                    'shipping_address': shippingAddress
                }
            }).done(function (response) {
                // Show postcode button
                var mapPostcodeBtn = $('#map-postcode-btn');
                if (mapPostcodeBtn.length) {
                    mapPostcodeBtn.show();
                }

                // Hide loader
                var postcodePleaseWait = $('#postalcode-please-wait');
                if (postcodePleaseWait.length) {
                    postcodePleaseWait.hide();
                }

                if (response.error) {
                    alert(response.message);
                    self.flagBindRelay = false;
                    return false;
                }

                // Show and map update
                var label = $("#label_method_" + response.method_code + "_" + response.method_code);
                var trads = response.trads;
                if (label.length) {
                    var parent = label.parent('tr').get(0);
                    if (mapContainer && mapContainer.length) {
                        mapContainer.replaceWith(response.content);
                    } else {
                        $(parent).after(response.content);
                    }

                    var mapVisible = $("#chronomap_" + response.method_code).length > 0;

                    /* Init map */
                    if (mapVisible) {
                        var leafletMap = self.leafletMap.createMap("chronomap_" + response.method_code);
                        leafletMap.setRelayIcon(response['relay_icon']);
                    }

                    if (response.relaypoints && response.relaypoints.length > 0) {
                        var found = false;

                        for (var iterator = 0; iterator < response.relaypoints.length; iterator++) {
                            var relayPoint = response.relaypoints[iterator];

                            if (mapVisible) {
                                leafletMap.addMarker(relayPoint, trads);
                            }

                            if (relayPoint.identifiantChronopostPointA2PAS == response.chronopost_chronorelais_relais_id) {
                                relayPoint.checked = "checked='checked'";
                                found = true;
                            }

                            var relayPointHtml = mageTemplate(self.templateHtml['point_relais']);
                            var tmpl = relayPointHtml({
                                data: relayPoint
                            });

                            var relayPointContainer = $('#relaypoint_container_' + response.method_code);
                            relayPointContainer.append(tmpl);

                            var point = $('#s_method_chronorelais_' + relayPoint.identifiantChronopostPointA2PAS);
                            point.click(function () {
                                self.setSessionRelais($(this).val());
                                if (mapVisible) {
                                    leafletMap.loadMyPoint($(this).val());
                                }
                            }).bind();

                            point.on('setSession', function () {
                                self.setSessionRelais($(this).val());
                            }).bind();
                        }

                        if (!found) {
                            let id = response.relaypoints[0].identifiantChronopostPointA2PAS;
                            $('#s_method_chronorelais_' + id).trigger('click');
                        }
                    }

                    var chronomapContainer = $("#chronomap_container_" + response.method_code);
                    if (chronomapContainer.length && chronomapContainer.find('.mappostalcode button')) {
                        chronomapContainer.find('.mappostalcode button').click(function () {
                            self.bindRelay({"method_code": response.method_code}, chronomapContainer.find('.mappostalcode input').val());
                        });
                    }
                }
            }).always(function () {
                self.flagBindRelay = false;
                Loader.stopLoader();
            });
        },

        isMethodAvailable: function (method_code) {
            var self = this;

            var rates = self.shippingRates();
            for (var i = 0; i < rates.length; i++) {
                var methodCode = rates[i].method_code;
                if (methodCode === method_code) {
                    return true;
                }
            }

            return false;
        },

        resetSessionRelais: function () {
            var self = this;

            if (!self.isMethodAvailable('chronorelais')) {
                return;
            }

            var shippingMethod = self.shippingMethod();
            if (shippingMethod && shippingMethod['method_code'].indexOf("chronorelais")) {
                if (self.xhrResetSessionRelais) {
                    self.xhrResetSessionRelais.abort();
                }

                self.xhrResetSessionRelais = $.ajax({
                    url: self.resetSessionRelaisUrl,
                    type: 'POST'
                }).done(function () {
                    Relais.relaisAddress('');
                });
            }
        },

        setSessionRelais: function (relais_id) {
            var self = this;

            var shippingMethod = self.shippingMethod();

            if (!shippingMethod || typeof shippingMethod['method_code'] == "undefined") {
                alert($.mage.__("Please select your shipping method"));
                return;
            }

            var inputChrono = $('input[value="' + shippingMethod['carrier_code'] + '_' + shippingMethod['method_code'] + '"]');
            if (!inputChrono.is(':checked')) {
                inputChrono.prop('checked', true);
            }

            if (self.xhrSetSessionRelais) {
                self.xhrSetSessionRelais.abort();
            }

            Loader.startLoader();

            self.xhrSetSessionRelais = $.ajax({
                url: self.setSessionRelaisUrl,
                type: 'POST',
                data: {
                    relais_id: relais_id
                }
            }).done(function (response) {
                if (response.success) {
                    Relais.relaisAddress(response.relais);
                } else if (response.error) {
                    Relais.relaisAddress('');
                    alert(response.message);
                }
            }).always(function () {
                Loader.stopLoader();
            });
        },

        getPlanning: function (shippingMethod) {
            var self = this;

            var planningContainer = $('#chronopost_srdv_planning_container');
            var shippingAddressTmp = quote.shippingAddress();
            var shippingAddress = {
                "country_id": shippingAddressTmp["countryId"],
                "street": shippingAddressTmp["street"] ? shippingAddressTmp["street"] : $('#co-shipping-form').find('input[name="street[0]"]').val(),
                "postcode": shippingAddressTmp["postcode"],
                "city": shippingAddressTmp["city"] ? shippingAddressTmp["city"] : $('#co-shipping-form').find('input[name="city"]').val(),
                "region_id": shippingAddressTmp["regionId"] ? shippingAddressTmp["regionId"] : $('#co-shipping-form').find('select[name="region_id"]').val(),
                "region_code": shippingAddressTmp["regionCode"] ? shippingAddressTmp["regionCode"] : ''
            };

            Loader.startLoader();

            if (self.xhrGetPlanning) {
                self.xhrGetPlanning.abort();
            }

            self.xhrGetPlanning = $.ajax({
                url: self.getPlanningUrl,
                data: {
                    'method_code': shippingMethod['method_code'],
                    'shipping_address': shippingAddress
                }
            }).done(function (response) {
                var label = $("#label_method_" + response.method_code + "_" + response.method_code);
                if (label.length) {
                    var parent = label.parent('tr').get(0);
                    if (planningContainer && planningContainer.length) {
                        planningContainer.replaceWith(response.content);
                    } else {
                        $(parent).after(response.content);
                    }

                    var rdvCarousel = $("#rdvCarouselContent");
                    if (rdvCarousel.length) {
                        rdvCarousel.bxSlider({
                            /** @see : https://github.com/stevenwanderski/bxslider-4/issues/1240 */
                            touchEnabled: (navigator.maxTouchPoints > 0), /** @todo remove when fixed */
                            infiniteLoop: false,
                            pager: false,
                            nextSelector: '.carousel-control.next',
                            prevSelector: '.carousel-control.prev',
                            nextText: $.mage.__('Next week'),
                            prevText: $.mage.__('Previous week'),
                            onSlideAfter: function (slideElement, oldIndex, newIndex) {
                                $('.carousel-control').removeClass('inactive');
                                if (newIndex === 0) {
                                    $('.carousel-control.prev').addClass('inactive');
                                } else if (newIndex === (self.getSlideCount() - 1)) {
                                    $('.carousel-control.next').addClass('inactive');
                                }
                            }
                        });
                    }

                    var globalMobile = $('#global-mobile');
                    if (globalMobile.length) {
                        globalMobile.find('th').click(function () {
                            globalMobile.find('th').removeClass('active');
                            $(this).addClass('active');
                            $('#time-list').find('ul').hide();
                            var idUlHoraireDay = $(this).attr('id').replace("th", "ul");
                            $('#' + idUlHoraireDay).show();
                        });
                        globalMobile.find('th:first').click();
                    }

                    $('body').on('click', 'input.shipping_method_chronopostsrdv', function () {
                        self.selectRdvHoraire($(this));
                    });
                }
            }).always(function () {
                Loader.stopLoader();
            });
        },

        selectRdvHoraire: function (input) {
            var self = this;

            var slotValue = input.val();
            var slotValueJson = JSON.parse(slotValue);

            var rdvCarousel = $("#rdvCarouselContent");
            if (rdvCarousel.length) {
                rdvCarousel.find('th').removeClass('active');
                input.parents('tr:first').find('th').addClass('active');
                rdvCarousel.find('th#th_' + slotValueJson.deliveryDate.substr(0, 10)).addClass('active');
            }

            self.setSessionRdv(slotValueJson);
        },

        setSessionRdv: function (slotValueJson) {
            var self = this;

            if (self.xhrSetSessionRdv) {
                self.xhrSetSessionRdv.abort();
            }

            self.xhrSetSessionRdv = $.ajax({
                url: self.setSessionRdvUrl,
                type: 'POST',
                data: {
                    chronopostsrdv_creneaux_info: slotValueJson
                }
            }).done(function (response) {
                if (response.success) {
                    Rdv.rdvInfo(response.rdvInfo);

                    var currentShippingMethodTitle = quote.shippingMethod().method_title;
                    currentShippingMethodTitle = self.getBaseShippingMethodTitle(currentShippingMethodTitle);
                    quote.shippingMethod().method_title = currentShippingMethodTitle + response.rdvInfo;

                    var label = $('#label_method_chronopostsrdv_chronopostsrdv');
                    if (label.length) { /* on change le label du mode par rdv */
                        var shippingMethodTitle = label.html();
                        shippingMethodTitle = self.getBaseShippingMethodTitle(shippingMethodTitle);
                        label.html(shippingMethodTitle + response.rdvInfo);
                    }
                } else if (response.error) {
                    Rdv.rdvInfo('');
                    alert(response.message);
                }
            });
        },

        resetSessionSaturdayOption: function (shippingMethod) {
            var self = this;

            Loader.startLoader();

            $('.saturday_container').each(function () {
                $(this).remove();
            });

            if (self.xhrSetSessionSaturdayOption) {
                self.xhrSetSessionSaturdayOption.abort();
            }

            self.xhrSetSessionSaturdayOption = $.ajax({
                url: self.resetSessionSaturdayOptionUrl,
                type: 'POST'
            }).always(function () {
                if (shippingMethod !== null) {
                    self.showSaturdayInput(shippingMethod);
                } else {
                    Loader.stopLoader();
                }
            });
        },

        resetSessionRdv: function () {
            var self = this;

            if (!self.isMethodAvailable('chronopostsrdv')) {
                return;
            }

            var shippingMethod = self.shippingMethod();
            if (shippingMethod && shippingMethod['method_code'].indexOf("chronopostsrdv")) {
                if (self.xhrResetSessionRdv) {
                    self.xhrResetSessionRdv.abort();
                }

                self.xhrResetSessionRdv = $.ajax({
                    url: self.resetSessionRdvUrl,
                    type: 'POST'
                }).done(function () {
                    Rdv.rdvInfo('');

                    var label = $('#label_method_chronopostsrdv_chronopostsrdv');
                    if (label.length) {
                        var shippingMethodTitle = label.html();
                        shippingMethodTitle = self.getBaseShippingMethodTitle(shippingMethodTitle);
                        label.html(shippingMethodTitle);
                    }
                });
            }
        },

        getBaseShippingMethodTitle: function (title) {
            return title.replace(/- Le \d{2}\/\d{2}\/\d{2,4} entre \d{1,2}:\d{0,2} et \d{1,2}:\d{0,2}/g, '');
        },

        resizeAppointmentContainerMobile: function () {
            var globalMobile = $('#global-mobile');
            if (globalMobile.length) {
                globalMobile.css('max-width', ($('main').width() - 20) + 'px');
            }
        }
    });
});

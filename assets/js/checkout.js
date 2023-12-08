document.addEventListener('DOMContentLoaded', function () {
    const stepShippingElements = document.querySelectorAll('.step-shipping');
    stepShippingElements.forEach(element => element.classList.add('active'));

    //On est obligé d'utilisé jQuery pour le checkout car il est chargé en AJAX et les events sont détectés par jQuery
    //Fist load, keep loading order !!!
    jQuery(document.body).on(
        "updated_checkout",
        function () {
            setTimeout(function () {
                checkoutNavigation();
                reapplySelectedClasses();
                loadShippingMethods();
                loadDriveDateTimePicker();
                handleBillingFields();
                handleEnterKeydown();
            }, 200);
        });

    const loadShippingMethods = () => {
        const wcShippingMethod = document.querySelector('.woocommerce-shipping-totals.shipping');
        const billingActions = document.querySelector('.woocommerce-billing-actions');
        const billingFields = document.querySelector('.woocommerce-billing-fields');
        const shippingSection = document.querySelector('.shipping_address');
        let shippingMethods = wcShippingMethod.querySelectorAll('.woocommerce-shipping-methods');
        let shippingOptions = wcShippingMethod.querySelectorAll('.km-shipping-option');
        let shippingInputs = wcShippingMethod.querySelectorAll('input[type="radio"].shipping_method');

        shippingMethods.forEach(shippingMethod => {
            shippingMethod.addEventListener('click', () => handleShippingMethodClick(shippingMethod, shippingOptions, shippingInputs, billingActions, billingFields, shippingSection));
        });

        shippingOptions.forEach(option => {
            option.addEventListener('click', () => handleShippingOptionClick(option, shippingOptions));
        });

    }

    const handleShippingMethodClick = (shippingMethod, shippingOptions, shippingInputs, billingActions, billingFields, shippingSection) => {
        if (shippingMethod.classList.contains('selected')) return;

        const selectedShippingMethodId = shippingMethod.id;
        localStorage.setItem('selectedShipping', selectedShippingMethodId);
        document.querySelectorAll('.woocommerce-shipping-methods.selected').forEach(el => el.classList.remove('selected'));
        shippingMethod.classList.add('selected');

        if (selectedShippingMethodId === 'shipping-method-shipping') {
            reapplyShippingOption(shippingOptions);
            toggleBillingFieldsRequired(true);
            billingActions.style.display = 'block';
            billingFields.style.display = 'none';
            shippingSection.style.display = 'block';
        }
        if (selectedShippingMethodId === 'shipping-method-drive') {
            document.querySelector('input#shipping_method_0_drive').click();
            billingActions.style.display = 'none';
            billingFields.style.display = 'block';
            shippingSection.style.display = 'none';
        }
        jQuery(document.body).trigger('update_checkout');
    }

    const handleShippingOptionClick = (option, shippingOptions) => {
        if (option.classList.contains('selected')) return;

        shippingOptions.forEach(opt => opt.classList.remove('selected'));
        option.classList.add('selected');

        const shippingInput = option.querySelector('input[type="radio"]');
        shippingInput.checked = true;
        localStorage.setItem('selectedShippingOption', shippingInput.value);

        jQuery(document.body).trigger('update_checkout');
    }

    const reapplyShippingOption = (shippingOptions) => {
        const selectedShippingOption = localStorage.getItem('selectedShippingOption');
        if (selectedShippingOption) {
            const selectedOptionInput = document.querySelector(`input[value="${selectedShippingOption}"]`);
            if (selectedOptionInput) {
                const selectedOption = selectedOptionInput.closest('.km-shipping-option');
                if (selectedOption) {
                    selectedOption.classList.add('selected');
                    selectedOptionInput.checked = true;
                }
            }
        }
    }

    const loadDriveDateTimePicker = () => {

        let dateTimePickers = document.querySelectorAll('.drive-datetimepicker');

        dateTimePickers.forEach(function (dateTimePicker) {

            const dayInputs = document.querySelectorAll('.drive-datepicker-day .day');
            const timeSlots = document.querySelectorAll('.drive-datepicker-time .slot');

            //When click on a particular day, remove active class on all other days and add active class on that day
            dayInputs.forEach(function (day) {
                day.addEventListener('click', function () {
                    dayInputs.forEach(function (day) {
                        day.classList.remove('active');
                    });
                    day.classList.add('active');
                    //Get inner text of day and set it to the hidden input field drive_date
                    let dayText = day.innerText;
                    let driveDate = dateTimePicker.querySelector('.drive_date');
                    driveDate.value = dayText;
                    dateTimePicker.querySelector('#drive-date-wrapper').classList.add('woocommerce-validated');
                });
            });


            //Same for slot
            timeSlots.forEach(function (slot) {
                slot.addEventListener('click', function () {
                    timeSlots.forEach(function (slot) {
                        slot.classList.remove('active');
                    });
                    slot.classList.add('active');
                    //Get inner text of day and set it to the hidden input field drive_date
                    let slotText = slot.innerText;
                    let driveTime = dateTimePicker.querySelector('.drive_time');
                    driveTime.value = slotText;
                    dateTimePicker.querySelector('#drive-time-wrapper').classList.add('woocommerce-validated');
                });
            });

            jQuery('form.checkout').on('checkout_place_order', function () {
                var driveDate = jQuery('.drive_date').val();
                var driveTime = jQuery('.drive_time').val();

                // Validation pour drive_date
                if (!driveDate) {
                    // Affiche une erreur et empêche la soumission du formulaire
                    return false;
                }

                // Validation pour drive_time
                if (!driveTime) {
                    // Affiche une erreur et empêche la soumission du formulaire
                    return false;
                }

                return true; // Tout est valide
            });

            let loadMoreCount = 1;
            dateTimePicker.querySelector('.load-more-days').addEventListener('click', function (event) {
                let offset = loadMoreCount * 20; // Chaque fois, ajoutez 20 jours supplémentaires
                handleLoading(event, true);
                kmAjaxCall('get_drive_available_days', { offset: offset })
                    .then(response => {
                        if (response.success) {
                            dateTimePicker.querySelector('.day-list').innerHTML += response.data;
                            loadMoreCount++;
                            loadDriveDateTimePicker();
                        }
                        handleLoading(event, false);
                    })
                    .catch(error => {
                        console.error('Erreur lors de la récupération des jours supplémentaires:', error);
                    });
            });
        });
    }

    const checkoutNavigation = () => {
        const multistepNavbars = document.querySelectorAll('.km-multistep-navbar');
        const elementorCheckoutNavbar = document.querySelector('.shopengine-multistep-navbar');
        const step0Btn = elementorCheckoutNavbar.querySelector('.shopengine-multistep-button[data-item="0"]');
        const step1Btn = elementorCheckoutNavbar.querySelector('.shopengine-multistep-button[data-item="1"]');
        const placeOrderButton = document.querySelector('#custom_paiement_btn');

        multistepNavbars.forEach(navbar => {
            let stepShipping = navbar.querySelector('.step-shipping');
            let stepPayment = navbar.querySelector('.step-payment');

            stepShipping.addEventListener('click', () => {
                step0Btn.click();
                if (step0Btn.parentElement.classList.contains('active')) {
                    stepShipping.classList.add('active');
                    stepPayment.classList.remove('active');
                }
            });

            stepPayment.addEventListener('click', () => {
                step1Btn.click();

                if (step1Btn.parentElement.classList.contains('active')) {
                    stepPayment.classList.add('active');
                    stepShipping.classList.remove('active');
                }
            });
            placeOrderButton.addEventListener('click', () => {
                stepPayment.click();
            });
        });
    }

    const togglShippingAdress = (billingFields) => {
        const showBillingBtn = document.querySelector('.bool-action.false');
        const hideBillingBtn = document.querySelector('.bool-action.true');

        // Définir la visibilité initiale des champs de facturation au chargement de la page
        if (hideBillingBtn.classList.contains('selected')) {
            billingFields.style.display = 'none';
        } else {
            billingFields.style.display = 'block';
        }

        // Lorsque l'utilisateur clique sur "Oui", masquer les champs de facturation
        hideBillingBtn.addEventListener('click', function () {
            billingFields.style.display = 'none';
            this.classList.add('selected');
            showBillingBtn.classList.remove('selected');
        });

        // Lorsque l'utilisateur clique sur "Non", afficher les champs de facturation
        showBillingBtn.addEventListener('click', function () {
            billingFields.style.display = 'block';
            this.classList.add('selected');
            hideBillingBtn.classList.remove('selected');
        });
    }

    // Fonction pour réappliquer les classes selected
    const reapplySelectedClasses = () => {
        var selectedShipping = localStorage.getItem('selectedShipping');
        var selectedShippingOption = localStorage.getItem('selectedShippingOption');
        var selectedShippingWrapper = document.querySelector('#' + selectedShipping);

        if (!selectedShipping || !selectedShippingWrapper) return;

        selectedShippingWrapper.classList.add('selected');

        if (!selectedShippingOption) return;

        if (selectedShipping === 'shipping-method-shipping') {
            selectedShippingWrapper.querySelectorAll('.km-shipping-option input[value="' + selectedShippingOption + '"]').forEach(function (optionInput) {
                optionInput.closest('.km-shipping-option').classList.add('selected');
            });
        }
    }

    const handleBillingFields = () => {
        const showBillingBtn = document.querySelector('.bool-action.false');
        const hideBillingBtn = document.querySelector('.bool-action.true');
        const billingFields = document.querySelector('.woocommerce-billing-fields');

        hideBillingBtn.addEventListener('click', function () {
            toggleBillingFieldsRequired(false);
            this.classList.add('selected');
            showBillingBtn.classList.remove('selected');
            billingFields.classList.remove('active');
        });

        showBillingBtn.addEventListener('click', function () {
            toggleBillingFieldsRequired(true);
            this.classList.add('selected');
            hideBillingBtn.classList.remove('selected');
            billingFields.classList.add('active');
        });

        // Initialisez l'état requis des champs de facturation en fonction du choix actuel
        if (hideBillingBtn.classList.contains('selected')) {
            toggleBillingFieldsRequired(false);
        } else {
            toggleBillingFieldsRequired(true);
        }
    }

    const toggleBillingFieldsRequired = (isRequired) => {
        const billingFields = [
            '#billing_first_name_field',
            '#billing_last_name_field',
            '#billing_address_1_field',
            '#billing_city_field',
            '#billing_postcode_field',
            '#billing_phone_field',
            '#billing_email_field'
        ];

        billingFields.forEach(field => {
            const billingField = document.querySelector(field);

            if (!billingField) {
                return;
            }

            const input = billingField.querySelector('input');

            if (isRequired) {
                billingField.classList.add('validate-required');
                billingField.classList.add('shopengine-checkout-form-billing');
            } else {
                billingField.classList.remove('shopengine-checkout-form-billing');
                billingField.classList.remove('validate-required');
            }

            input.required = isRequired;
        });

        // preventUpdateOnFieldChange('.shipping_address input#shipping_postcode');
    };

    const handleEnterKeydown = () => {
        const checkoutForm = document.querySelector('form.checkout');
        const paymentButton = document.querySelector('#custom_paiement_btn');

        checkoutForm.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                paymentButton.click();
            }
        });
    }

    // // Empêcher l'update_checkout pour certains champs
    // function preventUpdateOnFieldChange(selector) {
    //     document.querySelectorAll(selector).forEach(field => {
    //         field.addEventListener('change', event => {
    //             event.stopPropagation();

    //             kmAjaxCall('handle_shipping_postcode_change_on_checkout', { postcode: event.target.value }).then
    //                 (response => {
    //                     if (response.success) {
    //                         jQuery(document.body).trigger('update_checkout');
    //                     }
    //                 });
    //           // TODO : AJAX Contrôle si différent et l\actuelet si in or out 13
    //         });
    //     });
    // }
});

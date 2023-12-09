document.addEventListener('DOMContentLoaded', function () {
    const billingActions = document.querySelector('.woocommerce-billing-actions');
    const billingFields = document.querySelector('.woocommerce-billing-fields');
    const shippingSection = document.querySelector('.shipping_address');
    const stepShippingElements = document.querySelectorAll('.step-shipping');
    stepShippingElements.forEach(element => element.classList.add('active'));

    //On est obligé d'utilisé jQuery pour le checkout car il est chargé en AJAX et les events sont détectés par jQuery
    //Fist load, keep loading order !!!
    jQuery(document.body).on(
        "updated_checkout",
        function () {
            setTimeout(function () {
                reapplySelectedClasses();
                loadShippingMethods();
                handleEnterKeydown();
                checkoutNavigation();
                handleBillingFields();
                handleSelectedShippingMethod();
            }, 200);
        });

    jQuery(document.body).on('update_checkout', () => {
        showLoader('.shopengine-checkout-shipping-methods');
    });

    jQuery(document.body).on('updated_checkout', () => {
        hideLoader('.shopengine-checkout-shipping-methods');
    });

    function showLoader(selector) {
        const loaderHtml = `<div class="shopengine-loader"><div class="spinner"></div></div>`;
        jQuery(selector).append(loaderHtml);
    }

    function hideLoader(selector) {
        jQuery(selector).find('.shopengine-loader').remove();
    }

    const loadShippingMethods = () => {
        const wcShippingMethod = document.querySelector('.woocommerce-shipping-totals.shipping');
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

    const handleSelectedShippingMethod = () => {

        const selectedShippingMethod = document.querySelector('.woocommerce-shipping-methods.selected');

        console.log(selectedShippingMethod);
        if (!selectedShippingMethod || !selectedShippingMethod.id) return;

        if (selectedShippingMethod.id === 'shipping-method-shipping') {
            toggleAddressFieldsRequired('billing', false);
            toggleDriveConditionsRequired(false);
        }
        if (selectedShippingMethod.id === 'shipping-method-drive') {
            toggleAddressFieldsRequired('billing', true);
            toggleAddressFieldsRequired('shipping', false);
            toggleDriveConditionsRequired(true);
            handleBillingFields(selectedShippingMethod.id);
            loadDriveDateTimePicker();
        }
    }

    const handleShippingMethodClick = (shippingMethod, shippingOptions, shippingInputs, billingActions, billingFields, shippingSection) => {
        if (shippingMethod.classList.contains('selected')) return;

        const selectedShippingMethodId = shippingMethod.id;
        localStorage.setItem('selectedShipping', selectedShippingMethodId);
        document.querySelectorAll('.woocommerce-shipping-methods.selected').forEach(el => el.classList.remove('selected'));
        shippingMethod.classList.add('selected');

        if (selectedShippingMethodId === 'shipping-method-shipping') {
            reapplyShippingOption();
        }
        if (selectedShippingMethodId === 'shipping-method-drive') {
            document.querySelector('input#shipping_method_0_drive').click();
        }

        handleBillingFields(selectedShippingMethodId);
    }

    const handleShippingOptionClick = (option, shippingOptions) => {
        if (option.classList.contains('selected')) return;

        shippingOptions.forEach(opt => opt.classList.remove('selected'));
        option.classList.add('selected');

        const shippingInput = option.querySelector('input');
        localStorage.setItem('selectedShippingOption', shippingInput.value);

        // Trigger click on closest input radio
        shippingInput.click();
    }

    const reapplyShippingOption = () => {
        const selectedShippingOption = localStorage.getItem('selectedShippingOption');
        if (selectedShippingOption) {
            const selectedOptionInput = document.querySelector(`input[value="${selectedShippingOption}"]`);
            if (selectedOptionInput) {
                const selectedOption = selectedOptionInput.closest('.km-shipping-option');
                if (selectedOption) {
                    selectedOption.click()

                }
            }
        }
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

    const handleBillingFields = (selectedShippingMethod) => {
        const showBillingBtn = document.querySelector('.bool-action.false');
        const hideBillingBtn = document.querySelector('.bool-action.true');
        const billingFields = document.querySelector('.woocommerce-billing-fields');

        if (selectedShippingMethod === 'shipping-method-shipping') {
            billingActions.style.display = 'block';
            billingFields.classList.remove('active');
            shippingSection.style.display = 'block';
        }

        if (selectedShippingMethod === 'shipping-method-drive') {
            billingActions.style.display = 'none';
            billingFields.classList.add('active');
            shippingSection.style.display = 'none';
        }

        hideBillingBtn.addEventListener('click', function () {
            this.classList.add('selected');
            showBillingBtn.classList.remove('selected');
            billingFields.classList.remove('active');
        });

        showBillingBtn.addEventListener('click', function () {
            this.classList.add('selected');
            hideBillingBtn.classList.remove('selected');
            billingFields.classList.add('active');
        });
    }

    const toggleAddressFieldsRequired = (type, isRequired) => {
        const fields = [
            '#' + type + '_first_name_field',
            '#' + type + '_last_name_field',
            '#' + type + '_address_1_field',
            '#' + type + '_city_field',
            '#' + type + '_postcode_field',
            '#' + type + '_country_field',
            '#' + type + '_phone_field',
            '#' + type + '_email_field'
        ];

        fields.forEach(field => {
            const fieldElem = document.querySelector(field);
            if (!fieldElem) {
                return;
            }

            const input = fieldElem.querySelector('input, select');
            if (!input) {
                return;
            }

            if (isRequired) {
                fieldElem.classList.add('shopengine-checkout-form-' + type, 'validate-required');
            } else {
                fieldElem.classList.remove('shopengine-checkout-form-' + type, 'validate-required');
            }
            input.required = isRequired;
        });
    };

    const toggleDriveConditionsRequired = (isRequired) => {
        // Si la méthode de livraison est drive, alors on rend les options de livraison obligatoires
        const driveWrapperElem = document.querySelector('#shipping-method-drive');
        if (!driveWrapperElem) return;

        const mustValidateElems = driveWrapperElem.querySelectorAll('.must-validate');

        mustValidateElems.forEach((elem) => {
            if (isRequired) {
                elem.classList.add('validate-required');
            } else {
                elem.classList.remove('validate-required');
            }
        });
    }

    const loadDriveDateTimePicker = () => {

        let dateTimePickers = document.querySelectorAll('.drive-datetimepicker');

        dateTimePickers.forEach(function (dateTimePicker) {

            const dayInputs = document.querySelectorAll('.drive-datepicker-day .day');
            const timeSlots = document.querySelectorAll('.drive-datepicker-time .slot');
            const selectedDate = localStorage.getItem('driveDate');
            const selectedTime = localStorage.getItem('driveTime');

            const setActiveClass = (elements, activeElement) => {
                elements.forEach((element) => {
                    element.classList.remove('active');
                });
                activeElement.classList.add('active');
            };

            const handleDayClick = (day) => {
                setActiveClass(dayInputs, day);
                const chosenDate = day.dataset.date;
                const driveDate = dateTimePicker.querySelector('.drive_date');
                driveDate.value = chosenDate;
                dateTimePicker.querySelector('#drive-date-wrapper').classList.add('woocommerce-validated');
                localStorage.setItem('driveDate', chosenDate);
            };

            const handleSlotClick = (slot) => {
                setActiveClass(timeSlots, slot);
                const chosenTime = slot.dataset.time;
                const driveTime = dateTimePicker.querySelector('.drive_time');
                driveTime.value = chosenTime;
                dateTimePicker.querySelector('#drive-time-wrapper').classList.add('woocommerce-validated');
                localStorage.setItem('driveTime', chosenTime);
            };

            dayInputs.forEach((day) => {
                day.addEventListener('click', () => {
                    handleDayClick(day);
                });
            });

            timeSlots.forEach((slot) => {
                slot.addEventListener('click', () => {
                    handleSlotClick(slot);
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


            const reapplySelectedDateTime = () => {
                if (selectedDate) {

                    const selectedDay = dateTimePicker.querySelector('.drive-datepicker-day .day[data-date="' + selectedDate + '"]');
                    if (selectedDay) {
                        selectedDay.click();
                    }
                }

                if (selectedTime) {
                    const selectedSlot = dateTimePicker.querySelector('.drive-datepicker-time .slot[data-time="' + selectedTime + '"]');
                    if (selectedSlot) {
                        selectedSlot.click();
                    }
                }
            }
            reapplySelectedDateTime();
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

});

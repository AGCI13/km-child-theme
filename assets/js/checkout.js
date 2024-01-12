jQuery(document).ready(function ($) {
    const billingActions = document.querySelector('.woocommerce-billing-actions');
    const billingFields = document.querySelector('.woocommerce-billing-fields');
    const shippingSection = document.querySelector('.shipping_address');
    const stepShippingElements = document.querySelectorAll('.step-shipping');
    stepShippingElements.forEach(element => element.classList.add('active'));

    $('.step-cart').on('click', function () {
        window.location.href = window.location.origin + '/panier/';
    });

    //On est obligé d'utilisé $ pour le checkout car il est chargé en AJAX et les events sont détectés par $
    //Fist load, keep loading order !!!
    $(document.body).on(
        "updated_checkout",
        function () {
            setTimeout(function () {
                showCouponForm();
                loadShippingMethods();
                handleEnterKeydown();
                checkoutNavigation();
                handleBillingFields();
                handleSelectedShippingMethod();
            }, 200);
        });

    $(document.body).on('update_checkout', () => {
        maybeChangePostcode();
    });

    const maybeChangePostcode = () => {

        const shippingCountry = document.querySelector('#shipping_country');
        if (!shippingCountry) return;

        const shippingPostcode = document.querySelector('#shipping_postcode');
        if (!shippingPostcode) return;

        const cleanedShippingCountry = shippingCountry.value.trim();
        const cleanedShippingPostcode = shippingPostcode.value.trim();
        const shippingInputZipcode = cleanedShippingPostcode + '-' + cleanedShippingCountry;
        const cookieZipCode = getCookie('zip_code').trim();

        //If billing_postcode is equal to cookie zip_code, return
        if (shippingInputZipcode === cookieZipCode) {
            return;
        }

        // Get nonce_postcode
        const nonce = document.querySelector('#nonce_postcode');
        if (!nonce) return;

        console.log(cleanedShippingCountry);
        const data = {
            zip: cleanedShippingPostcode,
            country: cleanedShippingCountry,
            nonce_postcode: nonce.value,
        };


        kmAjaxCall('postcode_submission_handler', data)
            .then(response => {

                console.log(response);
                if (response.success) {
                    setCookie('zip_code', shippingInputZipcode, 30);
                    setCookie('shipping_zone', response.data, 30);
                    document.querySelector('.modal_pc_open_btn').textContent = cleanedShippingPostcode;
                } else {
                    if (response.data.message) {
                        // get element .validate-postcode
                        const shippingPostcodeInput = document.getElementById('shipping_postcode');

                        if (shippingPostcodeInput) {
                            const errorMessage = document.createElement('span');
                            errorMessage.classList.add('km-validation-info');

                            // Assurez-vous que response.data.message est une chaîne. Si c'est un objet, accédez à la propriété appropriée.
                            errorMessage.textContent = typeof response.data.message === 'string' ? response.data.message : 'Une erreur est survenue.';

                            shippingPostcodeInput.insertAdjacentElement('afterend', errorMessage);

                            // Remove errorMessage after 3 seconds.
                            setTimeout(() => {
                                errorMessage.remove();
                            }, 3000); // Notez le placement correct de la fermeture de parenthèse et de la virgule
                        }
                    }
                }
            })
    }

    $(document.body).on('update_checkout', () => {
        showLoader('.shopengine-checkout-shipping-methods');
    });

    $(document.body).on('updated_checkout', () => {
        hideLoader('.shopengine-checkout-shipping-methods');
    });

    const showLoader = (selector) => {
        const loaderHtml = `<div class="shopengine-loader"><div class="spinner"></div></div>`;
        $(selector).append(loaderHtml);
    }

    const hideLoader = (selector) => {
        $(selector).find('.shopengine-loader').remove();
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

    const copyShippingAddressToBillingAdress = () => {
        const shippingFirstName = document.querySelector('#shipping_first_name');
        const billingFirstName = document.querySelector('#billing_first_name');
        const shippingLastName = document.querySelector('#shipping_last_name');
        const billingLastName = document.querySelector('#billing_last_name');
        const shippingAddress = document.querySelector('#shipping_address_1');
        const billingAddress = document.querySelector('#billing_address_1');
        const shippingAddress2 = document.querySelector('#shipping_address_2');
        const billingAddress2 = document.querySelector('#billing_address_2');
        const shippingCity = document.querySelector('#shipping_city');
        const billingCity = document.querySelector('#billing_city');
        const shippingPostcode = document.querySelector('#shipping_postcode');
        const billingPostcode = document.querySelector('#billing_postcode');
        const shippingCountry = document.querySelector('#shipping_country');
        const billingCountry = document.querySelector('#billing_country');

        if (shippingFirstName && billingFirstName && billingFirstName.value === '') {
            billingFirstName.value = shippingFirstName.value;
        }

        if (shippingLastName && billingLastName && billingLastName.value === '') {
            billingLastName.value = shippingLastName.value;
        }

        if (shippingAddress && billingAddress && billingAddress.value === '') {
            billingAddress.value = shippingAddress.value;
        }
        if (shippingAddress2 && billingAddress2 && billingAddress2.value === '') {
            billingAddress2.value = shippingAddress2.value;
        }
        if (shippingCity && billingCity && billingCity.value === '') {
            billingCity.value = shippingCity.value;
        }
        if (shippingPostcode && billingPostcode && billingPostcode.value === '') {
            billingPostcode.value = shippingPostcode.value;
        }
        if (shippingCountry && billingCountry && billingCountry.value === '') {
            billingCountry.value = shippingCountry.value;
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

    const handleBillingFields = (selectedShippingMethod) => {
        const showBillingBtn = document.querySelector('.bool-action.false');
        const hideBillingBtn = document.querySelector('.bool-action.true');
        const billingFields = document.querySelector('.woocommerce-billing-fields__field-wrapper');

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
            toggleAddressFieldsRequired('billing', false)
        });

        showBillingBtn.addEventListener('click', function () {
            this.classList.add('selected');
            hideBillingBtn.classList.remove('selected');
            billingFields.classList.add('active');
            toggleAddressFieldsRequired('billing', true);
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
            const displayDatetimeElem = document.querySelector('.woocommerce-checkout-review-order-table .shipping-cost');
            const selectedStoredDate = localStorage.getItem('driveDate');
            const selectedStoredTime = localStorage.getItem('driveTime');

            let selectedDate = selectedStoredDate;
            let selectedTime = selectedStoredTime;

            const setActiveClass = (elements, activeElement) => {
                elements.forEach((element) => {
                    element.classList.remove('active');
                });
                activeElement.classList.add('active');
            };

            const setDriveTotalsInfo = () => {
                if (!selectedDate && !selectedTime) return;
                // change selectedDate  format to d/m/Y
                const dateParts = selectedDate.split('-');
                const formattedDate = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];
                displayDatetimeElem.innerHTML = 'le ' + formattedDate + ' à ' + selectedTime;
            }

            const handleDayClick = (day) => {
                setActiveClass(dayInputs, day);
                const chosenDate = day.dataset.date;
                const driveDate = dateTimePicker.querySelector('.drive_date');
                driveDate.value = chosenDate;
                selectedDate = chosenDate;
                dateTimePicker.querySelector('#drive-date-wrapper').classList.add('woocommerce-validated');
                localStorage.setItem('driveDate', chosenDate);
                setDriveTotalsInfo();

                if (day.innerText.includes('samedi')) {
                    document.querySelector('.time-slot.afternoon').classList.add('disabled');
                    document.querySelectorAll('.time-slot.afternoon .slot').forEach((slot) => {
                        slot.classList.remove('active');
                    });
                }
                else {
                    document.querySelector('.time-slot.afternoon').classList.remove('disabled');
                }
            };

            const handleSlotClick = (slot) => {
                setActiveClass(timeSlots, slot);
                const chosenTime = slot.dataset.time;
                const driveTime = dateTimePicker.querySelector('.drive_time');
                driveTime.value = chosenTime;
                selectedTime = chosenTime;
                dateTimePicker.querySelector('#drive-time-wrapper').classList.add('woocommerce-validated');
                localStorage.setItem('driveTime', chosenTime);
                setDriveTotalsInfo();
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

            $('form.checkout').on('checkout_place_order', function () {
                var driveDate = $('.drive_date').val();
                var driveTime = $('.drive_time').val();

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
                if (selectedStoredDate) {

                    const selectedDay = dateTimePicker.querySelector('.drive-datepicker-day .day[data-date="' + selectedStoredDate + '"]');
                    if (selectedDay) {
                        selectedDay.click();
                    }

                    if (selectedDay.innerText.includes('samedi')) {
                        document.querySelector('.time-slot.afternoon').classList.add('disabled');
                    }
                }

                if (selectedStoredTime) {
                    const selectedSlot = dateTimePicker.querySelector('.drive-datepicker-time .slot[data-time="' + selectedStoredTime + '"]');
                    if (selectedSlot) {
                        selectedSlot.click();
                    }
                }
            }
            reapplySelectedDateTime();
            setDriveTotalsInfo();
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
                validateCustomFields();
                copyShippingAddressToBillingAdress();
                deleteLocalStorage();
                setHiddenShippingFields();
                stepPayment.click();
                // Scroll to #checkout-nav element smoothly
                document.querySelector('#checkout-nav').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    }


    const setHiddenShippingFields = () => {

        //Detect if a shipping option is selected :
        const selectedShippingOption = document.querySelector('.km-shipping-option.selected');

        if (!selectedShippingOption) return;

        shipping_price = selectedShippingOption.getAttribute('data-shipping-price');
        shipping_sku = selectedShippingOption.getAttribute('data-shipping-sku');
        shipping_tax = selectedShippingOption.getAttribute('data-shipping-tax');

        if (shipping_price) {
            //set value of km-shipping-price input field 
            document.getElementById('km_shipping_price').value = shipping_price;
        }
        if (shipping_sku) {
            //set value of km-shipping-sku input field 
            document.getElementById('km_shipping_sku').value = shipping_sku;
        }

        if (shipping_tax) {
            //set value of km-shipping-tax input field
            document.getElementById('km_shipping_tax').value = shipping_tax;
        }
    }

    const deleteLocalStorage = () => {
        localStorage.removeItem('driveDate');
        localStorage.removeItem('driveTime');
        localStorage.removeItem('selectedShipping');
        localStorage.removeItem('selectedShippingOption');
    }

    const validateCustomFields = () => {
        const checkoutErrorsContainer = $('#km-checkout-errors');
        const multistepWrapper = $('.shopengine-steps-wrapper');
        let isValid = true;
        let errorMessages = {};

        // Reset error messages
        checkoutErrorsContainer.empty();
        $('.km-validation-info').remove();

        const updateErrorMessage = () => {
            let errorMessageHtml = '<ul>';
            Object.values(errorMessages).forEach(message => {
                errorMessageHtml += `<li>${message}</li>`;
            });
            errorMessageHtml += '</ul>';
            checkoutErrorsContainer.html(errorMessageHtml);

            if (Object.keys(errorMessages).length === 0) {
                checkoutErrorsContainer.hide();
            } else {
                checkoutErrorsContainer.show();
            }
        };

        // Validate required fields
        multistepWrapper.find('.validate-required, .validate-phone, .validate-email').each(function () {
            const fieldWrapper = $(this);
            const inputField = fieldWrapper.find('input');
            const fieldLabel = fieldWrapper.find('label').text().replace('*', '').trim();
            const fieldKey = fieldWrapper.attr('id') || inputField.attr('name'); // Use ID or name as a key
            const errorMessage = `Le champ "${fieldLabel}" n'est pas correctement rempli.`;

            if ((inputField.is(':checkbox') && !inputField.is(':checked')) || (inputField.val() === '')) {
                isValid = false;
                fieldWrapper.append('<span class="km-validation-info">Ce champ est requis</span>');
                errorMessages[fieldKey] = errorMessage;

                // Event listener to remove specific error message
                inputField.on('input change', function () {
                    fieldWrapper.find('.km-validation-info').remove();
                    delete errorMessages[fieldKey];
                    updateErrorMessage();
                });
            }
        });

        updateErrorMessage();
        return isValid;
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

});

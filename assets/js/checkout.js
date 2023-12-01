jQuery(document).ready(function ($) {
    //On est obligé d'utilisé jQuery pour le checkout car il est chargé en AJAX et les events sont détectés par jQuery

    document.querySelectorAll('.step-shipping').forEach(element => {
        element.classList.add('active');
    });

    setTimeout(() => {
        checkoutNavigation();
    }, 500);

    jQuery(document.body).on(
        "payment_method_selected update_checkout updated_checkout checkout_error applied_coupon_in_checkout removed_coupon_in_checkout adding_to_cart added_to_cart removed_from_cart wc_cart_button_updated cart_page_refreshed cart_totals_refreshed wc_fragments_loaded init_add_payment_method wc_cart_emptied updated_wc_div updated_cart_totals country_to_state_changed updated_shipping_method applied_coupon removed_coupon",
        function (e) {
            setTimeout(function () {
                loadShippingMethods();
                loadDriveDateTimePicker();
            }, 500);
        }
    );
});

const loadShippingMethods = () => {

    const wcShippingMethod = document.querySelector('.woocommerce-shipping-totals.shipping');

    // There is mutliple element with .km-shipping-header class. Eveytime we click on one of them, we remove the selected class on all children of .km-shipping-header with .select-shipping class and add it to the clicked one
    let shippingHeader = wcShippingMethod.querySelectorAll('.woocommerce-shipping-methods');
    shippingHeader.forEach(function (header) {
        header.addEventListener('click', function () {
            shippingHeader.forEach(function (header) {
                header.classList.remove('selected');
            });
            header.classList.add('selected');
            //Get inner text of header and set it to the hidden input field shipping_method
            let headerText = header.innerText;
            let shippingMethod = wcShippingMethod.querySelector('.shipping_method');
            shippingMethod.value = headerText;
        });

        let shippingOptions = wcShippingMethod.querySelectorAll('.km-shipping-option');
        shippingOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                shippingOptions.forEach(function (option) {
                    option.classList.remove('selected');
                });
                option.querySelector('input[type="radio"]').checked = true;
                option.classList.add('selected');               
            });
        });
    });
}

const loadDriveDateTimePicker = () => {

    let dateTimePickers = document.querySelectorAll('.drive-datetimepicker');

    dateTimePickers.forEach(function (dateTimePicker) {

        let dayInput = dateTimePicker.querySelectorAll('.day');
        let timeSlot = dateTimePicker.querySelectorAll('.slot');

        //When click on a particular day, remove active class on all other days and add active class on that day
        dayInput.forEach(function (day) {
            day.addEventListener('click', function () {
                dayInput.forEach(function (day) {
                    day.classList.remove('active');
                });
                day.classList.add('active');
                //Get inner text of day and set it to the hidden input field drive_date
                let dayText = day.innerText;
                let driveDate = dateTimePicker.querySelector('.drive_date');
                driveDate.value = dayText;
            });
        });

        //Same for slot
        timeSlot.forEach(function (slot) {
            slot.addEventListener('click', function () {
                timeSlot.forEach(function (slot) {
                    slot.classList.remove('active');
                });
                slot.classList.add('active');
                //Get inner text of day and set it to the hidden input field drive_date
                let slotText = slot.innerText;
                let driveTime = dateTimePicker.querySelector('.drive_time');
                driveTime.value = slotText;
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

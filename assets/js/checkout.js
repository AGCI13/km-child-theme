jQuery(document).ready(function ($) {
    //On est obligé d'utilisé jQuery pour le checkout car il est chargé en AJAX et les events sont détectés par jQuery

    document.querySelectorAll('.step-shipping').forEach(element => {
        element.classList.add('active');
    });
    
    togglShippingAdress();

    //Fist load
    $(document.body).on(
        "updated_checkout",
        function () {
            setTimeout(function () {
                checkoutNavigation();
                loadShippingMethods();
                loadDriveDateTimePicker();
                reapplySelectedClasses();
                setDebugClosable();
            }, 200);
        });
});

const loadShippingMethods = () => {

    const wcShippingMethod = document.querySelector('.woocommerce-shipping-totals.shipping');

    // There is mutliple element with .km-shipping-header class. Eveytime we click on one of them, we remove the selected class on all children of .km-shipping-header with .select-shipping class and add it to the clicked one
    let shippingMethods = wcShippingMethod.querySelectorAll('.woocommerce-shipping-methods');
    let shippingOptions = wcShippingMethod.querySelectorAll('.km-shipping-option');
    let shippingInputs = wcShippingMethod.querySelectorAll('input[type="radio"].shipping_method');

    //When click on a particular header
    shippingMethods.forEach(function (shippingMethod) {
        shippingMethod.addEventListener('click', function () {
            //if this element has selected class, return
            if (shippingMethod.classList.contains('selected')) {
                return;
            }

            //Save selected shipping method in local storage
            localStorage.setItem('selectedShipping', shippingMethod.id);

            shippingMethods.forEach(function (shippingMethod) {
                shippingMethod.classList.remove('selected');
            });
            shippingMethod.classList.add('selected');

            //Check if header [data-shipping] attribute is equal to "drive"
            if (shippingMethod.id === 'shipping-method-drive') {
                //Remove selected class from all radio button
                shippingOptions.forEach(function (option) {
                    option.classList.remove('selected');
                });

                // Réinitialiser les options de livraison
                shippingInputs.forEach(function (input) {
                    input.checked = false;
                });

                // Stocker la sélection dans localStorage
                localStorage.setItem('selectedShippingOption', '');

                shippingMethod.querySelector('input[type="radio"]').checked = true;

                //Update checkout
                jQuery(document.body).trigger('update_checkout');
            }
        });

        //When click on a particular option, remove selected class on all other options and add selected class on that option
        shippingOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                if (option.classList.contains('selected')) {
                    return;
                }

                // Supprimer la classe selected de toutes les options de livraison
                shippingOptions.forEach(function (option) {
                    option.classList.remove('selected');
                });
                // Ajouter la classe selected à l'option de livraison sélectionnée
                option.classList.add('selected');

                // Cocher l'option de livraison sélectionnée
                const shippingInput = option.querySelector('input[type="radio"]');
                shippingInput.checked = true;

                // Stocker la sélection dans localStorage
                localStorage.setItem('selectedShippingOption', shippingInput.value);

                //Update checkout
                jQuery(document.body).trigger('update_checkout');
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

const togglShippingAdress = () => {
    const billingFields = document.querySelector('.woocommerce-billing-fields');
    const showBillingBtn = document.querySelector('.bool-action.false');
    const hideBillingBtn = document.querySelector('.bool-action.true');

    // Définir la visibilité initiale des champs de facturation au chargement de la page
    const toggleBillingFields = () => {
        if (hideBillingBtn.classList.contains('selected')) {
            billingFields.style.display = 'none';
        } else {
            billingFields.style.display = 'block';
        }
    };

    toggleBillingFields(); // Appeler la fonction pour définir l'état initial

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

    if (selectedShipping) {
        document.querySelector('#' + selectedShipping).classList.add('selected');
    }

    if (selectedShippingOption) {
        document.querySelectorAll('.km-shipping-option input[value="' + selectedShippingOption + '"]').forEach(function (optionInput) {
            optionInput.closest('.km-shipping-option').classList.add('selected');
        });
    }
}

const setDebugClosable = () => {
    document.querySelectorAll('.modal-debug-close').forEach(element => {
        element.addEventListener('click', () => {
            document.getElementById('km-shipping-info-debug').style.display = 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    attachAjaxUpdateListener();
    numberInputPlusMinus();
    movePiOverallEstimateCart();
    showCouponForm();
    emailMarketingBox();
});

/**
 * Attach ajax update listener
 * @returns {void}
 */
const attachAjaxUpdateListener = () => {
    jQuery(document.body).on('updated_cart_totals', function () {
        numberInputPlusMinus(); // Ajoutez cette ligne
    });
}

/**
 * Add minus and plus buttons to number inputs
 */
const numberInputPlusMinus = () => {
    const numberInputs = document.querySelectorAll('.woocommerce-cart-form .quantity input[type="number"]');

    numberInputs.forEach(input => {
        // Création du bouton "-"
        const minusButton = document.createElement('span');
        minusButton.classList.add('qty-modifier', 'minus');
        minusButton.innerHTML = `-`;
        minusButton.onclick = () => {
            input.value = Math.max(0, input.value - 1);
            triggerChangeEvent(input);
        };

        // Création du bouton "+"
        const plusButton = document.createElement('span');
        plusButton.classList.add('qty-modifier', 'plus');
        plusButton.innerHTML = `+`;
        plusButton.onclick = () => {
            input.value = parseInt(input.value) + 1;
            triggerChangeEvent(input);
        };

        // Insertion des boutons
        input.parentNode.insertBefore(minusButton, input);
        input.parentNode.appendChild(plusButton);


        // Mise à jour du panier
        const handleMouseOut = () => {
            setTimeout(() => {
                jQuery("[name='update_cart']").trigger("click");
            }, 200);
        };
        minusButton.addEventListener('mouseout', handleMouseOut);
        plusButton.addEventListener('mouseout', handleMouseOut);

    });
}

/**
 * Trigger change event
 * @param {HTMLElement} element
 */
const triggerChangeEvent = (element) => {
    const event = new Event('change', {
        'bubbles': true,
        'cancelable': true
    });
    element.dispatchEvent(event);
}

/**
 * Move the Pi Overall Estimate Cart
 */
const movePiOverallEstimateCart = () => {
    // Sélectionner l'élément 'tr' à déplacer
    const piOverallEstimateCart = document.getElementById('pi-overall-estimate-cart');

    // Sélectionner l'élément 'tr' après lequel l'élément sera déplacé
    const shippingInfo = document.querySelector('.shipping-info');

    // S'assurer que les deux éléments existent
    if (piOverallEstimateCart && shippingInfo && shippingInfo.parentNode) {
        // Insérer 'piOverallEstimateCart' après 'shippingInfo'
        shippingInfo.parentNode.insertBefore(piOverallEstimateCart, shippingInfo.nextSibling);
    }
}

const emailMarketingBox = () => {
    const marketingWrapper = document.getElementById('km-customer-email-marketing');

    if (!marketingWrapper) {
        return;
    }

    confirmBtn = marketingWrapper.querySelector('#km-send-marketing-email');
    if (!confirmBtn) {
        return;
    }

    confirmBtn.addEventListener('click', (e) => {
        e.preventDefault();

        console.log('click');

        const emailInput = marketingWrapper.querySelector('input[name="km_cart_discount_email"]');
        const email = emailInput ? emailInput.value : '';

        if (!email) {
            marketingWrapper.innerHTML = 'E-mail manquant.';
            return;
        }

        const nonce = marketingWrapper.querySelector('input[name="km_cart_discount_email_nonce"]').value;
        confirmBtn.disabled = true;

        //Create a div to display the loader
        loaderElement = document.createElement('div');
        loaderElement.classList.add('km-spinner');
        marketingWrapper.appendChild(loaderElement);

        kmAjaxCall('discount_cart_form', { discount_email: email, discount_cart_nonce: nonce })
            .then(response => {
                loaderElement.remove();

                console.log(response.data);
                if (response.success === true) {
                    successMessages = document.createElement('div');
                    successMessages.classList.add('woocommerce-success');
                    marketingWrapper.innerHTML = response.data;
                } else {
                    //Create a div to display the error message
                    errorMessages = document.createElement('div');
                    errorMessages.classList.add('woocommerce-error');
                    errorMessages.innerHTML = response.data;
                    marketingWrapper.appendChild(errorMessages);
                    setTimeout(() => {
                        marketingWrapper.removeChild(errorMessages);
                    }, 3000);
                }
                confirmBtn.disabled = false;
            });
    });
}

jQuery(document).ready(function ($) {
    // Patch bug lorsque le panier est vide, rechargement de la page
    function removeCartItem() {
        var count = $('.cart_item td.product-remove').length;
        if (count === 1) {
            $('.product-remove .remove').off('click').on('click', function (event) {
                event.preventDefault();
                document.querySelector('.clear-cart').click();
            });
        }
    }
    removeCartItem();
    $(document.body).on('updated_cart_totals', function () {
        removeCartItem();
        showCouponForm();
    });
});

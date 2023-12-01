
document.addEventListener('DOMContentLoaded', () => {
    moveUpdateCartButton();
    attachAjaxUpdateListener();
    numberInputPlusMinus();
    movePiOverallEstimateCart();
    showCouponForm();
});

/**
 * Attach ajax update listener
 * @returns {void}
 */
const attachAjaxUpdateListener = () => {
    jQuery(document.body).on('updated_cart_totals', function () {
        moveUpdateCartButton();
        numberInputPlusMinus(); // Ajoutez cette ligne
    });
}

/**
 * Move the update cart button
 * @returns {void}
 */
const moveUpdateCartButton = () => {
    // Sélectionner le formulaire woocommerce-cart-form
    const cartFormActions = document.querySelector('.cart-actions');

    // Sélectionner le bouton update_cart
    const updateCartButton = document.querySelector('[name="update_cart"]');

    if (cartFormActions && updateCartButton) {
        // Insérer le bouton juste après l'ouverture du formulaire
        updateCartButton.classList.remove('button');
        updateCartButton.classList.add('cart-action-link', 'clear-cart');

        const icon = document.createElement('img');
        icon.src = themeObject.themeUrl + '/assets/img/reset-arrow.svg';
        icon.alt = 'Icone flèche tournante vers le haut';

        cartFormActions.appendChild(updateCartButton, cartFormActions.firstChild);
        updateCartButton.insertBefore(icon, updateCartButton.firstChild);
    }
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
        minusButton.innerHTML = `<img src="${themeObject.themeUrl + '/assets/img/minus.svg'}" alt="Moins">`;
        minusButton.onclick = () => {
            input.value = Math.max(0, input.value - 1);
            triggerChangeEvent(input);
        };

        // Création du bouton "+"
        const plusButton = document.createElement('span');
        plusButton.classList.add('qty-modifier', 'plus');
        plusButton.innerHTML = `<img src="${themeObject.themeUrl + '/assets/img/plus.svg'}" alt="Plus">`;
        plusButton.onclick = () => {
            input.value = parseInt(input.value) + 1;
            triggerChangeEvent(input);
        };

        // Insertion des boutons
        input.parentNode.insertBefore(minusButton, input);
        input.parentNode.appendChild(plusButton);
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

const showCouponForm = () => {
    couponLabel = document.querySelector('#km-coupon-label');

    if (couponLabel) {
        couponLabel.addEventListener('click', () => {
            couponLabel.classList.add('active');
            couponLabel.attributes['data-title'].value = 'Entrez votre code promo';
        });
    }
}

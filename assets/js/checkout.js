document.addEventListener("DOMContentLoaded", (event) => {

    document.querySelectorAll('.step-shipping').forEach(element => {
        element.classList.add('active');
    });
    
    setTimeout(() => {
        checkoutNavigation();
    }, 500);
});

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

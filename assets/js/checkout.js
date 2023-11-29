document.addEventListener("DOMContentLoaded", (event) => {
    setTimeout(() => {
        checkoutNavigation();
    }, 1000);
});

const checkoutNavigation = () => {
    const verif_bullet_col = document.getElementById('verif_bullet');
    const verif_bullet = verif_bullet_col.querySelector(".elementor-widget-wrap");
    const pay_bullet_col = document.getElementById('pay_bullet');
    const pay_bullet = pay_bullet_col.querySelector(".elementor-widget-wrap");
    const navbar_info = document.querySelector(".shopengine-multistep-navbar")
    const step0 = navbar_info.querySelector('.step-0');
    const step1 = navbar_info.querySelector('.step-1');
    const domain = 'https://' + window.location.hostname;
    const little_bullet = '/wp-content/uploads/2022/12/Group-35.png';
    const big_bullet = '/wp-content/uploads/2022/12/Group-34.png';
    const custom_paiement_btn = document.getElementById('custom_paiement_btn');


    verif_bullet_col.addEventListener('click', function (e) {
        step0.getElementsByTagName('div')[0].click();
    });

    pay_bullet_col.addEventListener('click', function (e) {
        step1.getElementsByTagName('div')[0].click();
    });

    custom_paiement_btn.addEventListener('click', function (e) {
        step1.getElementsByTagName('div')[0].click();
    });

    function handleMutations(mutationsList, observer) {
        mutationsList.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (step0.classList.contains('active')) {
                    verif_bullet.style.backgroundImage = "url('" + domain + big_bullet + "')";
                    pay_bullet.style.backgroundImage = "url('" + domain + little_bullet + "')";
                } else {
                    verif_bullet.style.backgroundImage = "url('" + domain + little_bullet + "')";
                    pay_bullet.style.backgroundImage = "url('" + domain + big_bullet + "')";
                }
            }
        });
    }
    const observer = new MutationObserver(handleMutations);
    const config = { attributes: true, attributeFilter: ['class'] };
    observer.observe(step0, config);
}
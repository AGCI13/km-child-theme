document.addEventListener('DOMContentLoaded', function () {

    let modal_pc_open_btn = document.getElementById('modal_pc_open_btn');
    let modal_pc_wrapper = document.getElementById('modal_pc_wrapper');
    let modal_pc_close_btn = document.getElementById("modal_pc_close_btn");
    let form_modal = document.getElementById('form_postcode');

    modal_pc_open_btn.addEventListener('click', function () {
        modal_pc_wrapper.classList.add('active');
    });

    if (modal_pc_close_btn !== null) {
        modal_pc_close_btn.addEventListener('click', function () {
            modal_pc_wrapper.classList.remove('active');
        });
    }

    form_modal.addEventListener('submit', function (e) {
        e.preventDefault();
        km_submit_cp(e);
    });
});

function km_submit_cp(e) {
    let country_modal = document.getElementById('country').value;
    let zip_modal = document.getElementById('zip_code').value;
    let modal_pc_wrapper = document.getElementById('modal_pc_wrapper');
    let label_modal_postcode = document.getElementById('zip_code_label');

    if (zip_modal.length < 5 && country_modal === 'FR' || zip_modal.length < 4 && country_modal === 'BE') {
        label_modal_postcode.textContent = 'Veuillez rentrez un code postal valide';
        return;
    }

    const data = {
        zip: zip_modal,
        country: country_modal,
        nonce_header_postcode: document.getElementById('nonce_header_postcode').value,
    };

    ajaxCall('get_shipping_zone_id_from_zip', data)
        .then(response => {
            if (response.data) {
                //Traiement de la réponse
                modal_pc_wrapper.style.display = 'none';
                setCookie('zip_code', zip_modal + '-' + country_modal, 1);
                setCookie('shipping_zone', response.data, 1);
                window.location.href = window.location.href.split('?')[0];
            } else {
                label_modal_postcode.textContent = response.error;
            }
        })
        .catch(error => {
            label_modal_postcode.textContent = 'Erreur : ' + error.message;
        });
}

function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}
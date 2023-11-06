document.addEventListener('DOMContentLoaded', function () {

    let cp_btn_modal = document.getElementById('cp_btn_modal');
    let background_modal_cp = document.getElementById('background_modal_cp');
    let cross_modal = document.querySelector("#modal_cp > span");
    let form_modal = document.getElementById('form_cp');

    if (!getCookie('zip_code')) {
        background_modal_cp.style.display = 'block';
        cross_modal.style.cursor = 'not-allowed';
    }

    cp_btn_modal.addEventListener('click', function () {
        background_modal_cp.style.display = 'block';
    });

    cross_modal.addEventListener('click', function () {
        if (getCookie('zip_code')) {
            background_modal_cp.style.display = 'none';
        }
    });

    form_modal.addEventListener('submit', function (e) {
        e.preventDefault();
        km_submit_cp(e);
    });
});

function km_submit_cp(e) {
    let country_modal = document.getElementById('country').value;
    let zip_modal = document.getElementById('zip_code').value;
    let background_modal_cp = document.getElementById('background_modal_cp');
    let label_modal_cp = document.getElementById('zip_code_label');

    if (zip_modal.length < 5 && country_modal === 'FR' || zip_modal.length < 4 && country_modal === 'BE') {
        label_modal_cp.textContent = 'Veuillez rentrez un code postal valide';
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
                background_modal_cp.style.display = 'none';
                setCookie('zip_code', zip_modal + '-' + country_modal, 1);
                setCookie('shipping_zone', response.data, 1);
                document.location.reload();
            } else {
                label_modal_cp.textContent = response.error;
            }
        })
        .catch(error => {
            label_modal_cp.textContent = 'Network error in "km_submit_cp" method : ' + error.message;
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
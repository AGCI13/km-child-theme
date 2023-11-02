jQuery(document).ready(function () {

    let cp_btn_modal = document.getElementById('cp_btn_modal');
    let background_modal_cp = document.getElementById('background_modal_cp');
    let cross_modal = document.querySelector("#modal_cp > span");
    let submit_modal_cp = document.getElementById('submit_btn_modal_cp');
    let form_modal = document.getElementById('form_cp');

    if (!getCookie('zip_code')) {
        background_modal_cp.style.display = 'block';
        cross_modal.style.cursor = 'not-allowed';
    }

    cp_btn_modal.addEventListener('click',function (e) {
        background_modal_cp.style.display = 'block';
    });

    cross_modal.addEventListener('click',function (e) {
        if (getCookie('zip_code')) {
            background_modal_cp.style.display = 'none';
        }
    });

    submit_modal_cp.addEventListener('click',submit_cp);
    form_modal.addEventListener('submit',submit_cp);
});

function submit_cp(e){
    let country_modal = document.getElementById('country').value;
    let zip_modal = document.getElementById('zip_code').value;
    let background_modal_cp = document.getElementById('background_modal_cp');
    let label_modal_cp = document.getElementById('zip_code_label');

    if ( zip_modal.length < 5 && country_modal === 'FR') {
        label_modal_cp.textContent = 'Veuillez rentrez un code postal valide'
    }else if( zip_modal.length < 4 && country_modal === 'BE' ){
        label_modal_cp.textContent = 'Veuillez rentrez un code postal valide'
    }else if(window.location.href.indexOf('commander') !== -1) {
        label_modal_cp.textContent = 'Vous ne pouvez pas modifier votre code postal à partir de ce moment';
    }
    else{
        const data = {
            action: 'get_shipping_zone',
            zip: zip_modal,
            country: country_modal,
        };

        jQuery.post(e.target.dataset.ajaxurl, data, function (response) {

            if (response.data !== null) {
                console.log(response.data);
                setCookie('shipping_zone', response.data, 1);
            }
        });
        setCookie('zip_code',zip_modal +'-'+ country_modal,1);
        background_modal_cp.style.display = 'none';
        setTimeout(function() {
            document.location.reload();
        }, 1000);

    }
}

function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for(let i = 0; i <ca.length; i++) {
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
    let expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}
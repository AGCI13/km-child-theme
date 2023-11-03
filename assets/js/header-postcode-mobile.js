jQuery(document).ready(function () {

    let cp_btn_modal = document.getElementById('cp_btn_modal_m');
    let background_modal_cp = document.getElementById('background_modal_cp_m');
    let cross_modal = document.querySelector("#modal_cp_m > span");
    let submit_modal_cp = document.getElementById('submit_btn_modal_cp_m');
    let form_modal = document.getElementById('form_cp_m');

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

    submit_modal_cp.addEventListener('click',submit_cp_m);
    form_modal.addEventListener('submit',submit_cp_m);

});


function submit_cp_m(){
    let country_modal = document.getElementById('country_m').value;
    let zip_modal = document.getElementById('zip_code_m').value;
    let background_modal_cp = document.getElementById('background_modal_cp_m');
    let label_modal_cp = document.getElementById('zip_code_label_m');

    if ( zip_modal.length < 5) {
        label_modal_cp.textContent = 'Veuillez rentrez un code postal valide'
    }else {
        setCookie('zip_code',zip_modal +'-'+ country_modal,1);
        background_modal_cp.style.display = 'none';
        document.location.reload();
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
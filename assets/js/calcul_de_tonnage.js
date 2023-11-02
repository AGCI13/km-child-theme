jQuery(document).ready(function () {

    document.getElementById('img_info_bull_density').addEventListener('click', info_bull)
    document.getElementById('img_close_info_bull_density').addEventListener('click', info_bull)
    document.getElementById('submit_calcul_de_tonnage').addEventListener('click', send_form_tonnage)
    document.getElementById('reset_calcul_de_tonnage').addEventListener('click', reset_form_calcul_de_tonnage)


});


function info_bull() {
    let info_bull_density = document.getElementById('info_bull_density');
    if (info_bull_density.classList.contains('open')) {
        info_bull_density.style.display = 'none';
    } else {
        info_bull_density.style.display = 'block';
    }
    info_bull_density.classList.toggle('open');
}


function send_form_tonnage(){
    let form = document.getElementById('calcul_de_tonnage');
    let dataForm = new FormData(form);
    let pageForm = document.querySelector('.calcul_de_tonnage > .form_calcul_de_tonnage ');
    let pageResult = document.querySelector('.calcul_de_tonnage > .result_calcul_de_tonnage ');
    let imgForm = document.querySelector('.calcul_de_tonnage > .img_calcul_de_tonnage_form ');
    let imgResult = document.querySelector('.calcul_de_tonnage > .img_calcul_de_tonnage_result ');


    const formDataObj = Object.fromEntries(dataForm.entries());
    formDataObj.action = 'calcul_tonnage'

    if (!form.reportValidity()) {
        return;
    }

    jQuery.ajax({
        url: frontend_ajax_object.ajaxurl,
        data: formDataObj,
        success: function (data) {
            let poids = document.getElementById('poids');
            let bag = document.getElementById('bag');
            let longueur_cm = document.getElementById('longueur_cm');
            let longueur_m = document.getElementById('longueur_m');
            let largeur_cm = document.getElementById('largeur_cm');
            let largeur_m = document.getElementById('largeur_m');
            let epaiseur_cm = document.getElementById('epaiseur_cm');
            let densite_value = document.getElementById('densite_value');

            poids.textContent = data.data.res + ' ' + data.data.unit;
            bag.textContent = data.data.conditionnement;
            longueur_cm.textContent = data.data.lon;
            longueur_m.textContent = Math.round((data.data.lon/100)*100)/100;
            largeur_cm.textContent = data.data.lar;
            largeur_m.textContent = Math.round((data.data.lar/100)*100)/100;
            epaiseur_cm.textContent = data.data.epa;
            densite_value.textContent = data.data.den;

            pageForm.style.display = 'none'
            pageResult.style.display = 'block'
            imgForm.style.display = 'none'
            imgResult.style.display = 'block'

        },
        error: function (data) {
            console.log('ERREUR');
        }
    });
}

function reset_form_calcul_de_tonnage(){
    let pageForm = document.querySelector('.calcul_de_tonnage > .form_calcul_de_tonnage ');
    let pageResult = document.querySelector('.calcul_de_tonnage > .result_calcul_de_tonnage ');
    let imgForm = document.querySelector('.calcul_de_tonnage > .img_calcul_de_tonnage_form ');
    let imgResult = document.querySelector('.calcul_de_tonnage > .img_calcul_de_tonnage_result ');
    pageForm.style.display = 'block'
    pageResult.style.display = 'none'
    imgForm.style.display = 'block'
    imgResult.style.display = 'none'
}
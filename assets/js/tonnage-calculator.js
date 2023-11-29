document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('img_info_bull_density').addEventListener('click', info_bull);
    document.getElementById('img_close_info_bull_density').addEventListener('click', info_bull);
    document.getElementById('submit_tonnage_calculator').addEventListener('click', send_form_tonnage);
    document.getElementById('reset_tonnage_calculator').addEventListener('click', reset_form_tonnage_calculator);
});

function info_bull() {
    let info_bull_density = document.getElementById('info_bull_density');
    info_bull_density.style.display = info_bull_density.classList.contains('open') ? 'none' : 'block';
    info_bull_density.classList.toggle('open');
}

async function send_form_tonnage() {
    let form = document.getElementById('tonnage_calculator');
    let dataForm = new FormData(form);

    const formDataObj = Object.fromEntries(dataForm.entries());
    formDataObj.action = 'tonnage_calculation';

    if (!form.reportValidity()) {
        return;
    }

    try {
        let response = await kmAjaxCall(formDataObj.action, formDataObj);
        updateResultDisplay(response.data);
    } catch (error) {
        console.log('ERREUR', error);
    }
}

function updateResultDisplay(data) {

    let poids = document.getElementById('poids');
    let bag = document.getElementById('bag');
    let longueur_cm = document.getElementById('longueur_cm');
    let longueur_m = document.getElementById('longueur_m');
    let largeur_cm = document.getElementById('largeur_cm');
    let largeur_m = document.getElementById('largeur_m');
    let epaiseur_cm = document.getElementById('epaiseur_cm');
    let densite_value = document.getElementById('densite_value');

    poids.textContent = data.res + ' ' + data.unit;
    bag.textContent = data.conditionnement;
    longueur_cm.textContent = data.lon;
    longueur_m.textContent = Math.round((data.lon / 100) * 100) / 100;
    largeur_cm.textContent = data.lar;
    largeur_m.textContent = Math.round((data.lar / 100) * 100) / 100;
    epaiseur_cm.textContent = data.epa;
    densite_value.textContent = data.den;


    // Gérer l'affichage des pages et des images
    toggleDisplay('.tonnage_calculator > .form_tonnage_calculator ', false);
    toggleDisplay('.tonnage_calculator > .result_tonnage_calculator ', true);
    toggleDisplay('.tonnage_calculator > .img_tonnage_calculator_form ', false);
    toggleDisplay('.tonnage_calculator > .img_tonnage_calculator_result ', true);
}

function reset_form_tonnage_calculator() {
    // Gérer l'affichage des pages et des images
    toggleDisplay('.tonnage_calculator > .form_tonnage_calculator ', true);
    toggleDisplay('.tonnage_calculator > .result_tonnage_calculator ', false);
    toggleDisplay('.tonnage_calculator > .img_tonnage_calculator_form ', true);
    toggleDisplay('.tonnage_calculator > .img_tonnage_calculator_result ', false);
}
function toggleDisplay(selector, show) {
    let element = document.querySelector(selector);
    element.style.display = show ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const filterSlidingBars = document.querySelectorAll('.km-product-filters_sliding-bar');
    const fromSlider = document.querySelector('#fromSlider');
    const toSlider = document.querySelector('#toSlider');
    const fromInput = document.querySelector('#fromInput');
    const toInput = document.querySelector('#toInput');
    const fromDisplay = document.querySelector('#fromDisplay');
    const toDisplay = document.querySelector('#toDisplay');
    const closeButton = document.querySelector('.km-product-filters_close');
   
    let originalGrilleCategorieContent = null;

    closeButton.addEventListener('click', () => {
        const filterBar = document.querySelector('.km-product-filters_sliding-bar');
        document.body.classList.remove('modal-open');
        filterBar.classList.remove('open');
    });

    filterSlidingBars.forEach((filterBar, barIndex) => {
        const filterForm = filterBar.querySelector('.km-product-filters__form');
        const checkboxes = filterBar.querySelectorAll('input[type="checkbox"]');
        const wcOrdering = document.querySelectorAll('.woocommerce-ordering')[barIndex];
        const resetBtn = document.querySelectorAll('input[type="reset"]')[barIndex];

        let link = document.createElement('a');
        let sep = document.createElement('span');

        sep.classList.add('filters-separator');
        sep.textContent = '|';
        wcOrdering.prepend(sep);

        link.textContent = 'Filtrer';
        link.classList.add('product-filters-link');
        wcOrdering.prepend(link);

        link.addEventListener('click', (e) => {
            e.stopPropagation();
            document.body.classList.toggle('modal-open');
            filterBar.classList.toggle('open');

            if(!filterBar.classList.contains('active')){
                filterBar.classList.add('active');
            }
        });

        document.body.addEventListener('click', (e) => {
            if (document.body.classList.contains('modal-open') && !filterBar.contains(e.target)) {
                document.body.classList.remove('modal-open');
                filterBar.classList.remove('open');
            }
        });

        filterBar.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        resetBtn.addEventListener('click', (e) => {
            e.preventDefault();
            checkboxes.forEach((checkbox) => {
                if (checkbox.checked) {
                    checkbox.closest('.km-product-filters__item').classList.remove('checked');
                    checkbox.checked = false
                }
            });
            resetRangeSlider();

            if (originalGrilleCategorieContent !== null) {
                const grilleCategorie = document.querySelector('.grille-categorie');
                if (grilleCategorie) {
                    grilleCategorie.innerHTML = originalGrilleCategorieContent;
                }
            }
    
        });

        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleLoading(e, true);
            let dataForm = new FormData(filterForm);

            const formDataObj = {};
            dataForm.forEach((value, key) => {
                // Si la clé finit par '[]', c'est un tableau
                if (key.endsWith('[]')) {
                    // Retirer les '[]' pour obtenir la clé réelle
                    key = key.slice(0, -2);
            
                    // Initialiser la clé avec un tableau si elle n'existe pas
                    if (!formDataObj.hasOwnProperty(key)) {
                        formDataObj[key] = [];
                    }
                    // Ajouter la valeur au tableau
                    formDataObj[key].push(value);
                } else {
                    // Pour les champs non-tableaux, assigner directement la valeur
                    formDataObj[key] = value;
                }
            });

            if (originalGrilleCategorieContent === null) {
                const grilleCategorie = document.querySelector('.grille-categorie');
                if (grilleCategorie) {
                    originalGrilleCategorieContent = grilleCategorie.innerHTML;
                }
            }

            // kmAjaxCall est une fonction que vous devez définir
            kmAjaxCall('filter_archive_products', formDataObj, {}).then((response) => {
                if (response.success) {
                    const product_wrapper = document.querySelector('.products'); // Si plusieurs, ajustez la logique
                    const resultCount = document.querySelector('.woocommerce-result-count');
                    const pagination = document.querySelector('.woocommerce-pagination');

                    // if (response.data.found_results_count > 0) {
                    //     document.body.classList.remove('modal-open');
                    //     filterBar.classList.remove('open');
                    // }

                    if (product_wrapper) {
                        product_wrapper.innerHTML = response.data.content_html;
                    }

                    if (resultCount) {
                        resultCount.textContent = response.data.found_results_html;
                    }

                    if (pagination) {
                        pagination.remove();
                    }
                }
                handleLoading(e, false);
            }).catch((error) => {
                filterForm.textContent = error.message;
                handleLoading(e, false);
            });
        });

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    this.closest('.km-product-filters__item').classList.add('checked');
                } else {
                    this.closest('.km-product-filters__item').classList.remove('checked');
                }
            });
        });
    });

    /* Start Range Slider */
    function controlFromInput(fromSlider, fromInput, toInput, controlSlider) {
        const [from, to] = getParsed(fromInput, toInput);
        fillSlider(fromInput, toInput, '#C6C6C6', '#25daa5', controlSlider);
        if (from > to) {
            fromSlider.value = to;
            fromInput.value = to;
        } else {
            fromSlider.value = from;
        }
    }

    function controlToInput(toSlider, fromInput, toInput, controlSlider) {
        const [from, to] = getParsed(fromInput, toInput);
        fillSlider(fromInput, toInput, '#C6C6C6', '#BE9E67', controlSlider);
        setToggleAccessible(toInput);
        if (from <= to) {
            toSlider.value = to;
            toInput.value = to;
        } else {
            toInput.value = from;
        }
    }

    function controlFromSlider(fromSlider, toSlider, fromInput) {
        const [from, to] = getParsed(fromSlider, toSlider);
        fillSlider(fromSlider, toSlider, '#C6C6C6', '#BE9E67', toSlider);
        if (from > to) {
            fromSlider.value = to;
            fromInput.value = to;
            fromDisplay.textContent = to;
        } else {
            fromInput.value = from;
            fromDisplay.textContent = from;
        }
    }

    function controlToSlider(fromSlider, toSlider, toInput) {
        const [from, to] = getParsed(fromSlider, toSlider);
        fillSlider(fromSlider, toSlider, '#C6C6C6', '#BE9E67', toSlider);
        setToggleAccessible(toSlider);
        if (from <= to) {
            toSlider.value = to;
            toInput.value = to;
            toDisplay.textContent = to;
        } else {
            toInput.value = from;
            toSlider.value = from;
            toDisplay.textContent = from;
        }
    }

    function getParsed(currentFrom, currentTo) {
        const from = parseInt(currentFrom.value, 10);
        const to = parseInt(currentTo.value, 10);
        return [from, to];
    }

    function fillSlider(from, to, sliderColor, rangeColor, controlSlider) {
        const rangeDistance = to.max - to.min;
        const fromPosition = from.value - to.min;
        const toPosition = to.value - to.min;
        controlSlider.style.background = `linear-gradient(
            to right,
            ${sliderColor} 0%,
            ${sliderColor} ${(fromPosition) / (rangeDistance) * 100}%,
            ${rangeColor} ${((fromPosition) / (rangeDistance)) * 100}%,
            ${rangeColor} ${(toPosition) / (rangeDistance) * 100}%, 
            ${sliderColor} ${(toPosition) / (rangeDistance) * 100}%, 
            ${sliderColor} 100%)`;
    }

    function setToggleAccessible(currentTarget) {
        const toSlider = document.querySelector('#toSlider');
        if (Number(currentTarget.value) <= 0) {
            toSlider.style.zIndex = 2;
        } else {
            toSlider.style.zIndex = 0;
        }
    }

    fillSlider(fromSlider, toSlider, '#C6C6C6', '#BE9E67', toSlider);
    setToggleAccessible(toSlider);

    fromSlider.oninput = () => controlFromSlider(fromSlider, toSlider, fromInput);
    toSlider.oninput = () => controlToSlider(fromSlider, toSlider, toInput);
    fromInput.oninput = () => controlFromInput(fromSlider, fromInput, toInput, toSlider);
    toInput.oninput = () => controlToInput(toSlider, fromInput, toInput, toSlider);

    fromSlider.onchange = () => controlFromSlider(fromSlider, toSlider, fromInput);
    toSlider.onchange = () => controlToSlider(fromSlider, toSlider, toInput);
    fromInput.onchange = () => controlFromInput(fromSlider, fromInput, toInput, toSlider);
    toInput.onchange = () => controlToInput(toSlider, fromInput, toInput, toSlider);

    function resetRangeSlider() {
        fromInput.value = fromInput.min;
        toInput.value = toInput.max;
        fromDisplay.textContent = fromInput.min;
        toDisplay.textContent = toInput.max;

        var event = new Event('change');
        toDisplay.dispatchEvent(event);
        fromDisplay.dispatchEvent(event);
        fromInput.dispatchEvent(event);
        toInput.dispatchEvent(event);
    }
});

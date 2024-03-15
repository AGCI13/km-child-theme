
document.addEventListener('DOMContentLoaded', () => {
    const googleMetabox = document.getElementById('google-shopping-export-metabox')
    const googleExportList = googleMetabox.querySelector('#google-shopping-export-list');
    const emptyExportBtn = googleMetabox.querySelector('#clear-csv-files');
    const btnSpinner = googleMetabox.querySelector('.spinner');

    if (!googleMetabox || !emptyExportBtn) return;

    emptyExportBtn.addEventListener('click', (e) => {
        e.preventDefault();

        //Make the spinner like this one undefined
        btnSpinner.classList.add('is-active');
        emptyExportBtn.setAttribute('disabled', 'disabled');

        kmAjaxCall('clear_csv_files', {})
            .then(response => {
                if (response.success) {
                    let message = document.createElement('p');
                    message.innerHTML = response.data;
                    message.style.color = 'green';
                    googleExportList.innerHTML = '';
                    googleExportList.appendChild(message);
                    btnSpinner.classList.remove('is-active');
                    setTimeout(() => {
                        message.remove();
                    }, 3000);
                }
                else {
                    let message = document.createElement('p');
                    message.innerHTML = response.data;
                    message.style.color = 'red';
                    googleExportList.appendChild(message);
                    btnSpinner.classList.remove('is-active');
                    emptyExportBtn.setAttribute('disabled', '');
                }
            })
    });
});

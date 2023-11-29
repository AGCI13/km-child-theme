const kmAjaxCall = async (action, data = null) => {

    let params = new URLSearchParams();
    params.append('action', action);

    if (data) {
        for (let key in data) {
            if (Array.isArray(data[key])) {
                data[key].forEach((item) => {
                    params.append(key, item);
                });
            } else {
                params.append(key, data[key]);
            }
        }
    }
    
    let response = await fetch(km_ajax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    });
    return await response.json();
}

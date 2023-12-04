const kmAjaxCall = (action, data = null) => {
    return new Promise((resolve, reject) => {
        let params = {};
        params['action'] = action;

        if (data) {
            for (let key in data) {
                if (Array.isArray(data[key])) {
                    data[key].forEach((item) => {
                        if (!params[key]) params[key] = [];
                        params[key].push(item);
                    });
                } else {
                    params[key] = data[key];
                }
            }
        }

        jQuery.ajax({
            url: km_ajax.ajaxurl,
            type: 'POST',
            data: params,
            success: function(response) {
                resolve(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                reject(new Error(textStatus));
            }
        });
    });
};

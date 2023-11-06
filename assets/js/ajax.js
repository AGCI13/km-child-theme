const ajaxCall = async (action, data = null) => {
    try {
        let params = new URLSearchParams();
        params.append('action', action);

        if (data) {
            for (let key in data) {
                params.append(key, data[key]);
            }
        }
        let response = await fetch(km_ajax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params
        });

        let responseData = await response.json();
        console.log(responseData);

        if (responseData.success) {
            return { data: responseData.data };
        } else {
            throw new Error(responseData.data.message || 'Une erreur est survenue dans la requête AJAX.');
        }
    } catch (error) {
        return { success: false, message: error.message };
    }
}
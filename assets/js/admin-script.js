jQuery(document).ready(function($) {
    var $daysField = $('#woocommerce_drive_unavailable_days');
    var $dateField = $('#woocommerce_drive_unavailable_dates');
    var selectedDays = $daysField.val().split(',').map(function(day) {
        return day.trim().toLowerCase();
    });
    
    // Création du sélecteur de jours de la semaine
    var daysOfWeek = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    var $dayPickerContainer = $('<div id="day-picker-container"></div>');
    $daysField.before($dayPickerContainer);

    daysOfWeek.forEach(function (day, index) {
        var dayLowerCase = day.toLowerCase();
        var isChecked = selectedDays.includes(dayLowerCase);
        var $checkbox = $('<input>', {
            type: 'checkbox',
            id: 'day-' + index,
            value: dayLowerCase,
            class: 'day-checkbox',
            checked: isChecked
        });

        var $label = $('<label>', {
            for: 'day-' + index,
            text: day
        });

        $dayPickerContainer.append($checkbox).append($label);
    });

    $('.day-checkbox').on('change', function() {
        var selectedDays = $('.day-checkbox:checked').map(function() {
            return this.value;
        }).get();

        $daysField.val(selectedDays.join(','));
    });

    // Création du conteneur de datepicker
    var $datepickerContainer = $('<div id="datepicker-container"></div>');
    $dateField.before($datepickerContainer);

    var updateSelectedDates = function(selectedDates) {
        $dateField.val(selectedDates.join(','));
        $datepickerContainer.datepicker('refresh');
    };

    $datepickerContainer.datepicker({
        dateFormat: 'yy-mm-dd',
        numberOfMonths: 2,
        minDate: 0,
        inline: true,
        beforeShowDay: function(date) {
            var selectedDates = $dateField.val().split(',');
            var stringDate = $.datepicker.formatDate('yy-mm-dd', date);
            return [true, selectedDates.includes(stringDate) ? "ui-state-highlight" : "", ""];
        },
        onSelect: function(dateText) {
            var selectedDates = $dateField.val().split(',');
            var indexOfDate = selectedDates.indexOf(dateText);
            if (indexOfDate > -1) selectedDates.splice(indexOfDate, 1);
            else selectedDates.push(dateText);
            updateSelectedDates(selectedDates);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('add-interval').addEventListener('click', function() {
        let lastMax = getLastMaxValue();
        let intervalDiv = document.createElement('div');
        intervalDiv.className = 'interval';
        intervalDiv.innerHTML = `<input type="number" class="min-value" value="${lastMax}" placeholder="Min" />
                                 <input type="number" class="max-value" placeholder="Max" />`;
        document.getElementById('interval-selector').appendChild(intervalDiv);
        updateHiddenInput();
    });

    document.getElementById('interval-selector').addEventListener('change', function(e) {
        if (e.target.classList.contains('max-value')) {
            updateHiddenInput();
        }
    });
});

function getLastMaxValue() {
    let intervals = document.querySelectorAll('.interval');
    if (intervals.length === 0) {
        return 0;
    }
    return intervals[intervals.length - 1].querySelector('.max-value').value;
}

function updateHiddenInput() {
    let values = [];
    document.querySelectorAll('.interval').forEach(interval => {
        let min = interval.querySelector('.min-value').value;
        let max = interval.querySelector('.max-value').value;
        values.push(min + '-' + max);
    });
    document.getElementById('interval-values').value = values.join(',');
}

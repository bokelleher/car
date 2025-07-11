document.addEventListener("DOMContentLoaded", function () {
    const sanitize = val => parseInt(val) || 0;
    const ctx = document.getElementById('inPersonChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.carReportDates || [],
                datasets: [{
                    label: 'In-Person Attendance',
                    data: window.carInPersonData || [],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});

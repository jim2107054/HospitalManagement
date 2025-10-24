// Charts for Hospital Management Dashboard
class ChartManager {
    constructor() {
        this.charts = {};
        this.initCharts();
    }

    initCharts() {
        // Initialize charts when page loads
        this.createAppointmentStatusChart();
        this.createBloodGroupChart();
        this.createDepartmentChart();
    }

    createAppointmentStatusChart() {
        const ctx = document.getElementById('appointmentStatusChart');
        if (!ctx) return;

        this.charts.appointmentStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Scheduled', 'Completed', 'Cancelled', 'No-Show'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        '#3498db',
                        '#27ae60',
                        '#e74c3c',
                        '#f39c12'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createBloodGroupChart() {
        const ctx = document.getElementById('bloodGroupChart');
        if (!ctx) return;

        this.charts.bloodGroup = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'],
                datasets: [{
                    label: 'Patient Count',
                    data: [0, 0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#3498db',
                        '#2980b9',
                        '#e74c3c',
                        '#c0392b',
                        '#27ae60',
                        '#229954',
                        '#f39c12',
                        '#d68910'
                    ],
                    borderColor: [
                        '#2980b9',
                        '#21618c',
                        '#c0392b',
                        '#a93226',
                        '#229954',
                        '#1e8449',
                        '#d68910',
                        '#b7950b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    createDepartmentChart() {
        const ctx = document.getElementById('departmentChart');
        if (!ctx) return;

        this.charts.department = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Number of Doctors',
                    data: [],
                    backgroundColor: [
                        '#3498db',
                        '#27ae60',
                        '#e74c3c',
                        '#f39c12',
                        '#9b59b6',
                        '#1abc9c'
                    ],
                    borderColor: [
                        '#2980b9',
                        '#229954',
                        '#c0392b',
                        '#d68910',
                        '#8e44ad',
                        '#16a085'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    updateCharts(data) {
        if (data.appointment_status && this.charts.appointmentStatus) {
            this.updateAppointmentStatusChart(data.appointment_status);
        }

        if (data.blood_groups && this.charts.bloodGroup) {
            this.updateBloodGroupChart(data.blood_groups);
        }

        if (data.departments && this.charts.department) {
            this.updateDepartmentChart(data.departments);
        }
    }

    updateAppointmentStatusChart(data) {
        const chart = this.charts.appointmentStatus;
        const statusCounts = [
            data.Scheduled || 0,
            data.Completed || 0,
            data.Cancelled || 0,
            data['No-Show'] || 0
        ];

        chart.data.datasets[0].data = statusCounts;
        chart.update();
    }

    updateBloodGroupChart(data) {
        const chart = this.charts.bloodGroup;
        const bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
        const counts = bloodGroups.map(bg => data[bg] || 0);

        chart.data.datasets[0].data = counts;
        chart.update();
    }

    updateDepartmentChart(data) {
        const chart = this.charts.department;
        const departments = Object.keys(data);
        const doctorCounts = Object.values(data);

        chart.data.labels = departments;
        chart.data.datasets[0].data = doctorCounts;
        
        // Update colors if needed
        const colors = [
            '#3498db', '#27ae60', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c',
            '#34495e', '#95a5a6', '#e67e22', '#2ecc71', '#f1c40f', '#e74c3c'
        ];
        
        chart.data.datasets[0].backgroundColor = colors.slice(0, departments.length);
        chart.data.datasets[0].borderColor = colors.slice(0, departments.length).map(color => {
            // Darken the color for border
            return color.replace('3498db', '2980b9')
                       .replace('27ae60', '229954')
                       .replace('e74c3c', 'c0392b')
                       .replace('f39c12', 'd68910')
                       .replace('9b59b6', '8e44ad')
                       .replace('1abc9c', '16a085');
        });
        
        chart.update();
    }

    // Create dynamic charts for specific pages
    createPatientAgeDistributionChart(containerId, data) {
        const ctx = document.getElementById(containerId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['0-10', '11-20', '21-30', '31-40', '41-50', '51-60', '61-70', '71+'],
                datasets: [{
                    label: 'Number of Patients',
                    data: data || [0, 0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    createAppointmentTrendChart(containerId, data) {
        const ctx = document.getElementById(containerId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Appointments',
                    data: data.appointments || [],
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    createDoctorWorkloadChart(containerId, data) {
        const ctx = document.getElementById(containerId);
        if (!ctx) return;

        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: data.doctors || [],
                datasets: [{
                    label: 'Appointments This Week',
                    data: data.appointments || [],
                    backgroundColor: 'rgba(155, 89, 182, 0.2)',
                    borderColor: '#9b59b6',
                    pointBackgroundColor: '#9b59b6',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#9b59b6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Utility method to destroy a chart
    destroyChart(chartName) {
        if (this.charts[chartName]) {
            this.charts[chartName].destroy();
            delete this.charts[chartName];
        }
    }

    // Method to resize all charts
    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            chart.resize();
        });
    }
}

// Initialize chart manager
const chartManager = new ChartManager();

// Update dashboard to use chart manager
if (window.dashboard) {
    window.dashboard.initCharts = () => chartManager.initCharts();
    window.dashboard.updateCharts = (data) => chartManager.updateCharts(data);
}

// Handle window resize
window.addEventListener('resize', () => {
    chartManager.resizeCharts();
});

// Export for global use
window.chartManager = chartManager;
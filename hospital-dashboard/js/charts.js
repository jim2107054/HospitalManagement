// Charts for Hospital Management Dashboard - Using PIE CHARTS
class ChartManager {
    constructor() {
        this.charts = {};
        this.isReady = false;
        
        // Wait for DOM to be ready before initializing
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initialize();
            });
        } else {
            this.initialize();
        }
    }

    initialize() {
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }
        
        console.log('Initializing charts...');
        this.initCharts();
        this.isReady = true;
    }

    initCharts() {
        // Initialize charts when page loads
        this.createAppointmentStatusChart();
        this.createBloodGroupChart();
        this.createDepartmentChart();
        this.createGenderChart();
        this.createAgeGroupChart();
        this.createRevenueChart();
        console.log('Charts initialized successfully');
    }

    createAppointmentStatusChart() {
        const ctx = document.getElementById('appointmentStatusChart');
        if (!ctx) {
            console.warn('appointmentStatusChart canvas not found');
            return;
        }

        try {
            this.charts.appointmentStatus = new Chart(ctx, {
                type: 'pie',
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
            console.log('Appointment status chart created');
        } catch (error) {
            console.error('Error creating appointment status chart:', error);
        }
    }

    createBloodGroupChart() {
        const ctx = document.getElementById('bloodGroupChart');
        if (!ctx) return;

        this.charts.bloodGroup = new Chart(ctx, {
            type: 'pie',
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
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
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
                                return `${label}: ${value} patients (${percentage}%)`;
                            }
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
            type: 'pie',
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
                        '#1abc9c',
                        '#34495e',
                        '#95a5a6'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
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
                                return `${label}: ${value} doctors (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createGenderChart() {
        const ctx = document.getElementById('genderChart');
        if (!ctx) return;

        this.charts.gender = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female', 'Other'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        '#3498db',
                        '#e91e63',
                        '#9c27b0'
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
                                return `${label}: ${value} patients (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createAgeGroupChart() {
        const ctx = document.getElementById('ageGroupChart');
        if (!ctx) return;

        this.charts.ageGroup = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Under 18', '18-30', '31-50', '51-65', 'Over 65'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#ff6b6b',
                        '#4ecdc4',
                        '#45b7d1',
                        '#96ceb4',
                        '#feca57'
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
                            padding: 15,
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
                                return `${label}: ${value} patients (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    createRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        this.charts.revenue = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#1abc9c',
                        '#3498db',
                        '#9b59b6',
                        '#e74c3c',
                        '#f39c12',
                        '#27ae60',
                        '#34495e',
                        '#95a5a6'
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
                            padding: 15,
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
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    updateCharts(data) {
        if (!this.isReady) {
            console.warn('Charts not ready yet, skipping update');
            return;
        }

        try {
            console.log('Updating charts with data:', data);

            if (data.appointment_status && this.charts.appointmentStatus) {
                this.updateAppointmentStatusChart(data.appointment_status);
            }

            if (data.blood_groups && this.charts.bloodGroup) {
                this.updateBloodGroupChart(data.blood_groups);
            }

            if (data.departments && this.charts.department) {
                this.updateDepartmentChart(data.departments);
            }

            if (data.gender_distribution && this.charts.gender) {
                this.updateGenderChart(data.gender_distribution);
            }

            if (data.age_groups && this.charts.ageGroup) {
                this.updateAgeGroupChart(data.age_groups);
            }

            if (data.department_revenue && this.charts.revenue) {
                this.updateRevenueChart(data.department_revenue);
            }

            console.log('Charts updated successfully');
        } catch (error) {
            console.error('Error updating charts:', error);
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
        chart.update();
    }

    updateGenderChart(data) {
        const chart = this.charts.gender;
        const genderCounts = [
            data.Male || 0,
            data.Female || 0,
            data.Other || 0
        ];

        chart.data.datasets[0].data = genderCounts;
        chart.update();
    }

    updateAgeGroupChart(data) {
        const chart = this.charts.ageGroup;
        const ageGroups = ['Under 18', '18-30', '31-50', '51-65', 'Over 65'];
        const counts = ageGroups.map(ag => data[ag] || 0);

        chart.data.datasets[0].data = counts;
        chart.update();
    }

    updateRevenueChart(data) {
        const chart = this.charts.revenue;
        const departments = Object.keys(data);
        const revenues = Object.values(data);

        chart.data.labels = departments;
        chart.data.datasets[0].data = revenues;
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
// Hospital Management Dashboard - Main JavaScript
console.log('main.js: Script loaded');

class HospitalDashboard {
    constructor() {
        console.log('main.js: Dashboard constructor called');
        this.currentPage = 'overview';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadOverviewData();
        this.initCharts();
    }

    setupEventListeners() {
        // Navigation menu click handlers
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.currentTarget.getAttribute('data-page');
                this.switchPage(page);
            });
        });

        // Sidebar toggle
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', this.toggleSidebar);
        }

        // Modal close on outside click
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeCrudModal();
            }
        });
    }

    switchPage(page) {
        // Remove active class from all nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Add active class to current nav link
        document.querySelector(`[data-page="${page}"]`).classList.add('active');

        // Hide all pages
        document.querySelectorAll('.page-content').forEach(content => {
            content.classList.remove('active');
        });

        // Show current page
        const currentPageElement = document.getElementById(`${page}-page`);
        if (currentPageElement) {
            currentPageElement.classList.add('active');
        }

        // Update page title
        const pageTitle = document.getElementById('page-title');
        const titles = {
            'overview': 'Overview',
            'patients': 'Patients',
            'departments': 'Departments',
            'doctors': 'Doctors',
            'appointments': 'Appointments',
            'medical-reports': 'Medical Reports'
        };
        pageTitle.textContent = titles[page] || 'Dashboard';

        this.currentPage = page;

        // Load page-specific data
        this.loadPageData(page);
    }

    async loadPageData(page) {
        this.showLoading();

        try {
            switch (page) {
                case 'overview':
                    await this.loadOverviewData();
                    break;
                case 'patients':
                    await this.loadPatientsData();
                    break;
                case 'departments':
                    await this.loadDepartmentsData();
                    break;
                case 'doctors':
                    await this.loadDoctorsData();
                    break;
                case 'appointments':
                    await this.loadAppointmentsData();
                    break;
                case 'medical-reports':
                    await this.loadMedicalReportsData();
                    break;
            }
        } catch (error) {
            console.error('Error loading page data:', error);
            this.showError('Error loading data. Please try again.');
        } finally {
            this.hideLoading();
        }
    }

    async loadOverviewData() {
        try {
            const response = await fetch('php/overview.php');
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Overview API response:', text);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                throw new Error('Invalid JSON response from server');
            }

            if (data.success) {
                // Update main stats
                document.getElementById('total-patients').textContent = data.stats.total_patients || 0;
                document.getElementById('total-doctors').textContent = data.stats.total_doctors || 0;
                document.getElementById('total-appointments').textContent = data.stats.total_appointments || 0;
                document.getElementById('total-departments').textContent = data.stats.total_departments || 0;

                // Update additional stats
                document.getElementById('today-appointments').textContent = data.stats.today_appointments || 0;
                document.getElementById('week-patients').textContent = data.stats.week_patients || 0;
                document.getElementById('upcoming-appointments').textContent = data.stats.upcoming_appointments || 0;
                document.getElementById('avg-consultation-fee').textContent = '$' + (data.stats.avg_consultation_fee || 0);

                // Update charts
                this.updateCharts(data.charts);
            } else {
                console.warn('API returned success: false', data);
                // Set default values
                this.setDefaultOverviewStats();
            }
        } catch (error) {
            console.error('Error loading overview data:', error);
            this.setDefaultOverviewStats();
        }
    }

    setDefaultOverviewStats() {
        document.getElementById('total-patients').textContent = '0';
        document.getElementById('total-doctors').textContent = '0';
        document.getElementById('total-appointments').textContent = '0';
        document.getElementById('total-departments').textContent = '0';
        document.getElementById('today-appointments').textContent = '0';
        document.getElementById('week-patients').textContent = '0';
        document.getElementById('upcoming-appointments').textContent = '0';
        document.getElementById('avg-consultation-fee').textContent = '$0.00';
    }

    async loadPatientsData() {
        try {
            const response = await fetch('php/patients.php?action=list');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Patients API response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse patients JSON:', text);
                throw new Error('Invalid JSON response from patients API');
            }

            if (data.success && data.patients && Array.isArray(data.patients)) {
                // Update patient stats
                document.getElementById('patients-total').textContent = data.stats?.total || 0;
                document.getElementById('patients-male').textContent = data.stats?.male || 0;
                document.getElementById('patients-female').textContent = data.stats?.female || 0;
                document.getElementById('patients-today').textContent = data.stats?.today || 0;

                // Update patients table
                this.updatePatientsTable(data.patients);
            } else {
                console.warn('Patients API returned invalid data:', data);
                // Set default values
                document.getElementById('patients-total').textContent = '0';
                document.getElementById('patients-male').textContent = '0';
                document.getElementById('patients-female').textContent = '0';
                document.getElementById('patients-today').textContent = '0';
                this.updatePatientsTable([]);
            }
        } catch (error) {
            console.error('Error loading patients data:', error);
        }
    }

    async loadDepartmentsData() {
        try {
            const response = await fetch('php/departments.php?action=list');
            const data = await response.json();

            if (data.success) {
                // Update department stats
                document.getElementById('departments-total').textContent = data.stats.total || 0;
                document.getElementById('departments-with-head').textContent = data.stats.with_head || 0;
                document.getElementById('departments-doctors').textContent = data.stats.doctors || 0;
                document.getElementById('departments-active').textContent = data.stats.total || 0;

                // Update departments table
                this.updateTable('departments', data.data);
                
                // Load dropdown options for other filters
                this.loadDepartmentDropdowns(data.data);
            }
        } catch (error) {
            console.error('Error loading departments data:', error);
        }
    }

    async loadDoctorsData() {
        try {
            const response = await fetch('php/doctors.php?action=list');
            const data = await response.json();

            if (data.success) {
                // Update doctor stats
                document.getElementById('doctors-total').textContent = data.stats.total || 0;
                document.getElementById('doctors-available').textContent = data.stats.available || 0;
                document.getElementById('doctors-specializations').textContent = data.stats.specializations || 0;
                document.getElementById('doctors-appointments-today').textContent = data.stats.appointments_today || 0;

                // Update doctors table
                this.updateTable('doctors', data.data);
                
                // Load dropdown options
                this.loadDoctorDropdowns(data.data);
            }
        } catch (error) {
            console.error('Error loading doctors data:', error);
        }
    }

    async loadAppointmentsData() {
        try {
            const response = await fetch('php/appointments.php?action=list');
            const data = await response.json();

            if (data.success) {
                // Update appointment stats
                document.getElementById('appointments-total').textContent = data.stats.total || 0;
                document.getElementById('appointments-today').textContent = data.stats.today || 0;
                document.getElementById('appointments-scheduled').textContent = data.stats.scheduled || 0;
                document.getElementById('appointments-completed').textContent = data.stats.completed || 0;
                document.getElementById('appointments-upcoming').textContent = data.stats.upcoming || 0;

                // Update appointments table
                this.updateTable('appointments', data.data);
                
                // Load dropdown options
                this.loadAppointmentDropdowns();
            }
        } catch (error) {
            console.error('Error loading appointments data:', error);
        }
    }

    async loadMedicalReportsData() {
        try {
            const response = await fetch('php/medical-reports.php?action=list');
            const data = await response.json();

            if (data.success) {
                // Update medical reports stats
                document.getElementById('reports-total').textContent = data.stats.total || 0;
                document.getElementById('reports-today').textContent = data.stats.today || 0;
                document.getElementById('reports-this-week').textContent = data.stats.this_week || 0;
                document.getElementById('reports-follow-ups').textContent = data.stats.follow_ups || 0;
                document.getElementById('reports-unique-patients').textContent = data.stats.unique_patients || 0;

                // Update medical reports table
                this.updateTable('medical-reports', data.data);
                
                // Load dropdown options
                this.loadMedicalReportDropdowns();
            }
        } catch (error) {
            console.error('Error loading medical reports data:', error);
        }
    }

    async loadDepartmentsPage() {
        const departmentsPage = document.getElementById('departments-page');
        departmentsPage.innerHTML = `
            <div class="page-header">
                <h2>Departments Management</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="dashboard.openCrudModal('departments', 'create')">
                        <i class="fas fa-plus"></i> Add Department
                    </button>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <h3 id="departments-total">0</h3>
                    <p>Total Departments</p>
                </div>
                <div class="stat-item">
                    <h3 id="departments-with-head">0</h3>
                    <p>With Head Doctor</p>
                </div>
                <div class="stat-item">
                    <h3 id="departments-doctors">0</h3>
                    <p>Total Doctors</p>
                </div>
            </div>

            <div class="filters-section">
                <h3>Filter Departments</h3>
                <div class="filters-grid">
                    <input type="text" id="filter-dept-search" class="filter-input" placeholder="Search by name or location...">
                    <button class="btn btn-secondary" onclick="dashboard.applyFilters('departments')">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button class="btn btn-outline" onclick="dashboard.clearFilters('departments')">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table" id="departments-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Head Doctor</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="departments-table-body">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        `;
    }

    async loadDoctorsPage() {
        const doctorsPage = document.getElementById('doctors-page');
        doctorsPage.innerHTML = `
            <div class="page-header">
                <h2>Doctors Management</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="dashboard.openCrudModal('doctors', 'create')">
                        <i class="fas fa-plus"></i> Add Doctor
                    </button>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <h3 id="doctors-total">0</h3>
                    <p>Total Doctors</p>
                </div>
                <div class="stat-item">
                    <h3 id="doctors-available">0</h3>
                    <p>Available Today</p>
                </div>
                <div class="stat-item">
                    <h3 id="doctors-specializations">0</h3>
                    <p>Specializations</p>
                </div>
            </div>

            <div class="filters-section">
                <h3>Filter Doctors</h3>
                <div class="filters-grid">
                    <select id="filter-department" class="filter-select">
                        <option value="">All Departments</option>
                    </select>
                    <select id="filter-specialization" class="filter-select">
                        <option value="">All Specializations</option>
                    </select>
                    <input type="text" id="filter-doctor-search" class="filter-input" placeholder="Search by name, email...">
                    <button class="btn btn-secondary" onclick="dashboard.applyFilters('doctors')">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button class="btn btn-outline" onclick="dashboard.clearFilters('doctors')">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table" id="doctors-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Experience</th>
                            <th>Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="doctors-table-body">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        `;
    }

    async loadAppointmentsPage() {
        const appointmentsPage = document.getElementById('appointments-page');
        appointmentsPage.innerHTML = `
            <div class="page-header">
                <h2>Appointments Management</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="dashboard.openCrudModal('appointments', 'create')">
                        <i class="fas fa-plus"></i> Add Appointment
                    </button>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <h3 id="appointments-total">0</h3>
                    <p>Total Appointments</p>
                </div>
                <div class="stat-item">
                    <h3 id="appointments-today">0</h3>
                    <p>Today's Appointments</p>
                </div>
                <div class="stat-item">
                    <h3 id="appointments-scheduled">0</h3>
                    <p>Scheduled</p>
                </div>
                <div class="stat-item">
                    <h3 id="appointments-completed">0</h3>
                    <p>Completed</p>
                </div>
            </div>

            <div class="filters-section">
                <h3>Filter Appointments</h3>
                <div class="filters-grid">
                    <select id="filter-appointment-status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="No-Show">No-Show</option>
                    </select>
                    <input type="date" id="filter-appointment-date" class="filter-input">
                    <input type="text" id="filter-appointment-search" class="filter-input" placeholder="Search patient or doctor...">
                    <button class="btn btn-secondary" onclick="dashboard.applyFilters('appointments')">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button class="btn btn-outline" onclick="dashboard.clearFilters('appointments')">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table" id="appointments-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="appointments-table-body">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        `;
    }

    async loadMedicalReportsPage() {
        const medicalReportsPage = document.getElementById('medical-reports-page');
        medicalReportsPage.innerHTML = `
            <div class="page-header">
                <h2>Medical Reports Management</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="dashboard.openCrudModal('medical-reports', 'create')">
                        <i class="fas fa-plus"></i> Add Medical Report
                    </button>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <h3 id="reports-total">0</h3>
                    <p>Total Reports</p>
                </div>
                <div class="stat-item">
                    <h3 id="reports-today">0</h3>
                    <p>Today's Reports</p>
                </div>
                <div class="stat-item">
                    <h3 id="reports-follow-ups">0</h3>
                    <p>Follow-ups Due</p>
                </div>
            </div>

            <div class="filters-section">
                <h3>Filter Medical Reports</h3>
                <div class="filters-grid">
                    <input type="date" id="filter-report-date-from" class="filter-input" placeholder="Date From">
                    <input type="date" id="filter-report-date-to" class="filter-input" placeholder="Date To">
                    <input type="text" id="filter-report-search" class="filter-input" placeholder="Search patient, doctor, diagnosis...">
                    <button class="btn btn-secondary" onclick="dashboard.applyFilters('medical-reports')">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button class="btn btn-outline" onclick="dashboard.clearFilters('medical-reports')">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table" id="medical-reports-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Visit Date</th>
                            <th>Diagnosis</th>
                            <th>Treatment</th>
                            <th>Follow-up</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="medical-reports-table-body">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        `;
    }

    async loadDataTable(type) {
        try {
            const response = await fetch(`php/${type}.php?action=list`);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log(`${type} API response:`, text);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error(`Failed to parse JSON for ${type}:`, text);
                throw new Error(`Invalid JSON response from ${type} API`);
            }

            if (data.success && Array.isArray(data.data)) {
                this.updateTable(type, data.data);
                if (data.stats) {
                    this.updateStats(type, data.stats);
                }
            } else {
                console.warn(`${type} API returned invalid data:`, data);
                // Set empty table
                this.updateTable(type, []);
            }
        } catch (error) {
            console.error(`Error loading ${type} data:`, error);
            // Set empty table on error
            this.updateTable(type, []);
        }
    }

    updatePatientsTable(patients) {
        const tbody = document.getElementById('patients-table-body');
        if (!tbody) {
            console.warn('Patients table body not found');
            return;
        }

        if (!patients || !Array.isArray(patients)) {
            console.warn('Invalid patients data:', patients);
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No data available</td></tr>';
            return;
        }

        if (patients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No patients found</td></tr>';
            return;
        }

        tbody.innerHTML = patients.map(patient => `
            <tr>
                <td>${patient.id}</td>
                <td>${patient.name}</td>
                <td>${this.formatDate(patient.date_of_birth)}</td>
                <td>${patient.gender}</td>
                <td>${patient.phone || '-'}</td>
                <td>${patient.email || '-'}</td>
                <td>${patient.blood_group || '-'}</td>
                <td>${this.formatDateTime(patient.registered_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" onclick="viewRecord('patients', ${patient.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="editRecord('patients', ${patient.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteRecord('patients', ${patient.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    updateTable(type, data) {
        const tbody = document.getElementById(`${type}-table-body`);
        if (!tbody) {
            console.warn(`Table body element not found: ${type}-table-body`);
            return;
        }

        // Handle empty or invalid data
        if (!data || !Array.isArray(data)) {
            console.warn(`Invalid data for ${type} table:`, data);
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">No data available</td></tr>';
            return;
        }

        let html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="100%" class="text-center">No records found</td></tr>';
        } else {
            data.forEach(item => {
                switch (type) {
                    case 'departments':
                        html += this.generateDepartmentRow(item);
                        break;
                    case 'doctors':
                        html += this.generateDoctorRow(item);
                        break;
                    case 'appointments':
                        html += this.generateAppointmentRow(item);
                        break;
                    case 'medical-reports':
                        html += this.generateMedicalReportRow(item);
                        break;
                    default:
                        console.warn(`Unknown table type: ${type}`);
                }
            });
        }

        tbody.innerHTML = html;
    }

    generateDepartmentRow(dept) {
        return `
            <tr>
                <td>${dept.id}</td>
                <td>${dept.name}</td>
                <td>${dept.description || '-'}</td>
                <td>${dept.head_doctor_name || '-'}</td>
                <td>${dept.contact_number || '-'}</td>
                <td>${dept.location || '-'}</td>
                <td>${dept.doctor_count || 0}</td>
                <td>${this.formatDateTime(dept.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" onclick="dashboard.viewRecord('departments', ${dept.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="dashboard.editRecord('departments', ${dept.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="dashboard.deleteRecord('departments', ${dept.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    generateDoctorRow(doctor) {
        return `
            <tr>
                <td>${doctor.id}</td>
                <td>${doctor.name}</td>
                <td>${doctor.specialization}</td>
                <td>${doctor.department_name}</td>
                <td>${doctor.phone || '-'}</td>
                <td>${doctor.email || '-'}</td>
                <td>${doctor.experience_years || '-'} years</td>
                <td>$${doctor.consultation_fee || '0.00'}</td>
                <td>
                    ${doctor.available_from && doctor.available_to ? 
                        `${doctor.available_from} - ${doctor.available_to}` : 
                        'Not set'}
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" onclick="viewRecord('doctors', ${doctor.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="editRecord('doctors', ${doctor.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteRecord('doctors', ${doctor.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    generateAppointmentRow(appointment) {
        return `
            <tr>
                <td>${appointment.id}</td>
                <td>${appointment.patient_name}</td>
                <td>${appointment.doctor_name}</td>
                <td>${appointment.department_name || '-'}</td>
                <td>${this.formatDate(appointment.appointment_date)}</td>
                <td>${appointment.appointment_time}</td>
                <td><span class="status-badge status-${appointment.status.toLowerCase().replace('-', '')}">${appointment.status}</span></td>
                <td>${appointment.reason_for_visit || '-'}</td>
                <td>$${appointment.consultation_fee || '0.00'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" onclick="viewRecord('appointments', ${appointment.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="editRecord('appointments', ${appointment.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteRecord('appointments', ${appointment.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    generateMedicalReportRow(report) {
        return `
            <tr>
                <td>${report.id}</td>
                <td>${report.patient_name}</td>
                <td>${report.doctor_name}</td>
                <td>${report.department_name || '-'}</td>
                <td>${this.formatDate(report.visit_date)}</td>
                <td class="diagnosis-cell" title="${report.diagnosis}">${this.truncateText(report.diagnosis, 50)}</td>
                <td class="treatment-cell" title="${report.treatment_plan || ''}">${this.truncateText(report.treatment_plan || '-', 40)}</td>
                <td>
                    ${report.follow_up_date ? 
                        `<span class="follow-up-date ${this.isFollowUpOverdue(report.follow_up_date) ? 'overdue' : 'upcoming'}">${this.formatDate(report.follow_up_date)}</span>` : 
                        '-'}
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" onclick="viewRecord('medical-reports', ${report.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="editRecord('medical-reports', ${report.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteRecord('medical-reports', ${report.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    updateStats(type, stats) {
        Object.keys(stats).forEach(key => {
            const element = document.getElementById(`${type}-${key}`);
            if (element) {
                element.textContent = stats[key];
            }
        });
    }

    async applyFilters(type) {
        this.showLoading();
        try {
            const filters = this.getFilters(type);
            const response = await fetch(`php/${type}.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'filter',
                    ...filters
                })
            });

            const data = await response.json();
            if (data.success) {
                // Update table based on type
                if (type === 'patients') {
                    this.updatePatientsTable(data.data);
                } else {
                    this.updateTable(type, data.data);
                }
                
                // Show SQL display button and store SQL code
                if (data.sql_code) {
                    this.showSQLDisplay(type);
                    this.storeSQLCode(type, data.sql_code);
                }
            }
        } catch (error) {
            console.error('Error applying filters:', error);
        } finally {
            this.hideLoading();
        }
    }

    showSQLDisplay(type) {
        const sqlDisplay = document.getElementById(`${type}-sql-display`);
        if (sqlDisplay) {
            sqlDisplay.style.display = 'block';
        }
    }

    storeSQLCode(type, sqlCode) {
        // Store SQL code for later display
        if (!window.sqlCodes) {
            window.sqlCodes = {};
        }
        window.sqlCodes[type] = sqlCode;
    }

    showSQLCode(type) {
        if (window.sqlCodes && window.sqlCodes[type]) {
            document.getElementById('sql-code-display').textContent = window.sqlCodes[type];
            document.getElementById('sql-modal').style.display = 'block';
        } else {
            alert('No SQL code available. Please apply filters first.');
        }
    }

    getFilters(type) {
        const filters = {};
        
        switch (type) {
            case 'patients':
                filters.gender = document.getElementById('filter-gender')?.value || '';
                filters.blood_group = document.getElementById('filter-blood-group')?.value || '';
                filters.date_from = document.getElementById('filter-date-from')?.value || '';
                filters.date_to = document.getElementById('filter-date-to')?.value || '';
                filters.search = document.getElementById('filter-search')?.value || '';
                break;
            case 'departments':
                filters.search = document.getElementById('filter-dept-search')?.value || '';
                break;
            case 'doctors':
                filters.department = document.getElementById('filter-department')?.value || '';
                filters.specialization = document.getElementById('filter-specialization')?.value || '';
                filters.search = document.getElementById('filter-doctor-search')?.value || '';
                break;
            case 'appointments':
                filters.status = document.getElementById('filter-appointment-status')?.value || '';
                filters.date = document.getElementById('filter-appointment-date')?.value || '';
                filters.search = document.getElementById('filter-appointment-search')?.value || '';
                break;
            case 'medical-reports':
                filters.date_from = document.getElementById('filter-report-date-from')?.value || '';
                filters.date_to = document.getElementById('filter-report-date-to')?.value || '';
                filters.search = document.getElementById('filter-report-search')?.value || '';
                break;
        }
        
        return filters;
    }

    clearFilters(type) {
        switch (type) {
            case 'patients':
                document.getElementById('filter-gender').value = '';
                document.getElementById('filter-blood-group').value = '';
                document.getElementById('filter-date-from').value = '';
                document.getElementById('filter-date-to').value = '';
                document.getElementById('filter-search').value = '';
                break;
            case 'departments':
                document.getElementById('filter-dept-search').value = '';
                break;
            case 'doctors':
                document.getElementById('filter-department').value = '';
                document.getElementById('filter-specialization').value = '';
                document.getElementById('filter-doctor-search').value = '';
                break;
            case 'appointments':
                document.getElementById('filter-appointment-status').value = '';
                document.getElementById('filter-appointment-date').value = '';
                document.getElementById('filter-appointment-search').value = '';
                break;
            case 'medical-reports':
                document.getElementById('filter-report-date-from').value = '';
                document.getElementById('filter-report-date-to').value = '';
                document.getElementById('filter-report-search').value = '';
                break;
        }
        
        this.loadDataTable(type);
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
    }

    showLoading() {
        document.getElementById('loading-overlay').style.display = 'flex';
    }

    hideLoading() {
        document.getElementById('loading-overlay').style.display = 'none';
    }

    showError(message) {
        alert(message); // Replace with better error handling
    }

    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    formatTime(timeString) {
        if (!timeString) return '-';
        const time = new Date(`2000-01-01 ${timeString}`);
        return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Helper methods for enhanced display
    truncateText(text, maxLength) {
        return text && text.length > maxLength ? text.substring(0, maxLength) + '...' : text || '';
    }

    getDepartmentCount(doctors) {
        const departments = new Set();
        doctors.forEach(doctor => {
            if (doctor.department_id) departments.add(doctor.department_id);
        });
        return departments.size;
    }

    isFollowUpOverdue(followUpDate) {
        const today = new Date();
        const followUp = new Date(followUpDate);
        return followUp < today;
    }

    getAvailabilityStatus(doctor) {
        const schedule = doctor.schedule || '9:00-17:00';
        const currentTime = new Date();
        const currentHour = currentTime.getHours();
        
        // Basic availability check
        if (currentHour >= 9 && currentHour < 17) {
            return 'Available';
        } else {
            return 'Off Duty';
        }
    }

    getAppointmentStatusClass(status) {
        const statusClasses = {
            'scheduled': 'status-scheduled',
            'completed': 'status-completed',
            'cancelled': 'status-cancelled',
            'pending': 'status-pending'
        };
        return statusClasses[status.toLowerCase()] || 'status-pending';
    }

    // Enhanced filtering method to handle all filter types
    getFilters(module) {
        const filterForm = document.querySelector(`#${module}-page .filter-section form`);
        if (!filterForm) return {};

        const formData = new FormData(filterForm);
        const filters = {};

        // Convert FormData to object, handling empty values
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                filters[key] = value;
            }
        }

        return filters;
    }

    // Load dropdown options for enhanced filtering
    async loadFilterDropdowns(module) {
        try {
            if (module === 'doctors') {
                await this.loadDepartmentDropdown('doctor-department-filter');
            } else if (module === 'appointments') {
                await this.loadDoctorDropdown('appointment-doctor-filter');
                await this.loadDepartmentDropdown('appointment-department-filter');
            } else if (module === 'medical-reports') {
                await this.loadDoctorDropdown('report-doctor-filter');
                await this.loadDepartmentDropdown('report-department-filter');
            }
        } catch (error) {
            console.error('Error loading filter dropdowns:', error);
        }
    }

    async loadDepartmentDropdown(selectId) {
        try {
            const response = await fetch('/hospital-dashboard/api/departments.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">All Departments</option>';
                    data.data.forEach(dept => {
                        select.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
                    });
                }
            }
        } catch (error) {
            console.error('Error loading departments:', error);
        }
    }

    async loadDoctorDropdown(selectId) {
        try {
            const response = await fetch('/hospital-dashboard/api/doctors.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">All Doctors</option>';
                    data.data.forEach(doctor => {
                        select.innerHTML += `<option value="${doctor.id}">${doctor.first_name} ${doctor.last_name}</option>`;
                    });
                }
            }
        } catch (error) {
            console.error('Error loading doctors:', error);
        }
    }

    // Placeholder methods for CRUD operations - will be overridden by crud.js
    openCrudModal(type, action, id = null) {
        console.log(`Opening ${action} modal for ${type}`, id);
        // Use the global CrudManager instance
        if (window.crudManager) {
            window.crudManager.openModal(type, action, id);
        } else {
            console.error('CrudManager not available');
            alert('CRUD functionality not available. Please refresh the page.');
        }
    }

    closeCrudModal() {
        // Use the global CrudManager instance
        if (window.crudManager) {
            window.crudManager.closeModal();
        } else {
            const modal = document.getElementById('crud-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    }

    viewRecord(type, id) {
        console.log(`Viewing ${type} record:`, id);
        // Use CrudManager to open in view mode
        if (window.crudManager) {
            window.crudManager.openModal(type, 'view', id);
        } else {
            alert('View functionality not available');
        }
    }

    editRecord(type, id) {
        console.log(`Editing ${type} record:`, id);
        // Use CrudManager to open in edit mode
        if (window.crudManager) {
            window.crudManager.openModal(type, 'edit', id);
        } else {
            alert('Edit functionality not available');
        }
    }

    deleteRecord(type, id) {
        if (confirm('Are you sure you want to delete this record?')) {
            console.log(`Deleting ${type} record:`, id);
            // Use CrudManager to delete record
            if (window.crudManager) {
                window.crudManager.deleteRecord(type, id);
            } else {
                alert('Delete functionality not available');
            }
        }
    }

    initCharts() {
        // Chart initialization will be handled in charts.js
    }

    updateCharts(data) {
        if (window.chartManager && data) {
            // Update appointment status chart
            if (data.appointmentStatus && window.chartManager.charts.appointmentStatus) {
                window.chartManager.charts.appointmentStatus.data.datasets[0].data = data.appointmentStatus.data;
                window.chartManager.charts.appointmentStatus.update();
            }

            // Update department distribution chart
            if (data.departmentDistribution && window.chartManager.charts.department) {
                window.chartManager.charts.department.data.labels = data.departmentDistribution.labels;
                window.chartManager.charts.department.data.datasets[0].data = data.departmentDistribution.data;
                window.chartManager.charts.department.update();
            }

            // Update blood group distribution chart
            if (data.bloodGroupDistribution && window.chartManager.charts.bloodGroup) {
                window.chartManager.charts.bloodGroup.data.datasets[0].data = data.bloodGroupDistribution.data;
                window.chartManager.charts.bloodGroup.update();
            }

            // Update age distribution chart
            if (data.ageDistribution && window.chartManager.charts.ageGroup) {
                window.chartManager.charts.ageGroup.data.datasets[0].data = data.ageDistribution.data;
                window.chartManager.charts.ageGroup.update();
            }

            // Update gender distribution chart
            if (data.genderDistribution && window.chartManager.charts.gender) {
                window.chartManager.charts.gender.data.datasets[0].data = data.genderDistribution.data;
                window.chartManager.charts.gender.update();
            }

            // Update experience distribution chart (revenue chart placeholder)
            if (data.experienceDistribution && window.chartManager.charts.revenue) {
                window.chartManager.charts.revenue.data.labels = data.experienceDistribution.labels;
                window.chartManager.charts.revenue.data.datasets[0].data = data.experienceDistribution.data;
                window.chartManager.charts.revenue.update();
            }
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('main.js: DOMContentLoaded event fired');
    
    // Add a delay to ensure all scripts are loaded
    setTimeout(() => {
        try {
            window.dashboard = new HospitalDashboard();
            console.log('main.js: Dashboard instance created and assigned to window.dashboard');
            
            // Verify dashboard is working
            if (window.dashboard && typeof window.dashboard.init === 'function') {
                console.log('✅ Dashboard initialized successfully');
            } else {
                console.error('❌ Dashboard initialization failed');
            }
        } catch (error) {
            console.error('Error initializing dashboard:', error);
        }
    }, 100);
});

// Global functions for inline event handlers
function openCrudModal(type, action, id = null) {
    console.log('Global openCrudModal called with:', { type, action, id });
    
    // Check if dashboard is available
    if (!window.dashboard) {
        console.error('Dashboard not available');
        alert('Dashboard not loaded yet. Please refresh the page and try again.');
        return;
    }
    
    // Check if crudManager is available
    if (!window.crudManager) {
        console.error('CrudManager not available');
        alert('CRUD system not loaded. Please refresh the page and try again.');
        return;
    }
    
    try {
        window.dashboard.openCrudModal(type, action, id);
    } catch (error) {
        console.error('Error opening modal:', error);
        alert('Error opening form: ' + error.message);
    }
}

function closeCrudModal() {
    window.dashboard.closeCrudModal();
}

function applyFilters(type) {
    window.dashboard.applyFilters(type);
}

function clearFilters(type) {
    window.dashboard.clearFilters(type);
}

// Action button functions
function viewRecord(type, id) {
    window.dashboard.viewRecord(type, id);
}

function editRecord(type, id) {
    window.dashboard.editRecord(type, id);
}

function deleteRecord(type, id) {
    window.dashboard.deleteRecord(type, id);
}

// SQL Display Functions
function toggleSQLDisplay(type) {
    window.dashboard.showSQLCode(type);
}

function closeSQLModal() {
    document.getElementById('sql-modal').style.display = 'none';
}

function copySQLCode() {
    const sqlCode = document.getElementById('sql-code-display').textContent;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(sqlCode).then(() => {
            alert('SQL code copied to clipboard!');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = sqlCode;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('SQL code copied to clipboard!');
    }
}
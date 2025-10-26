// Hospital Management Dashboard - Main JavaScript (REFACTORED - NO CLIENT-SIDE FILTERING/SORTING)
console.log('main.js: Script loaded - REFACTORED VERSION');

class HospitalDashboard {
    constructor() {
        console.log('main.js: Dashboard constructor called');
        this.currentPage = 'overview';
        
        // Store SQL queries for each module
        this.lastSQLQueries = {
            patients: '',
            departments: '',
            doctors: '',
            appointments: '',
            'medical-reports': ''
        };
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        
        // Wait a bit for Chart.js and charts.js to load, then load data
        setTimeout(() => {
            this.loadOverviewData();
        }, 100);
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
        
        // Setup form submit handlers for all filter forms
        this.setupFilterFormHandlers();
    }
    
    setupFilterFormHandlers() {
        // Patients filter form
        const patientsForm = document.getElementById('patients-filter-form');
        if (patientsForm) {
            patientsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters('patients');
            });
        }
        
        // Add similar handlers for other modules when ready
        // This will be set up when those pages are loaded
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
        console.log('Starting loadOverviewData...');
        try {
            console.log('Fetching overview data from php/overview.php');
            const response = await fetch('php/overview.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Overview API response text:', text);
            
            let data;
            try {
                data = JSON.parse(text);
                console.log('Parsed overview data:', data);
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                throw new Error('Invalid JSON response from server');
            }

            if (data.success) {
                console.log('Overview data loaded successfully, updating stats...');
                
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

                console.log('Stats updated, now updating charts...');
                console.log('Chart data:', data.charts);
                
                // Update charts
                this.updateCharts(data.charts);
                
                console.log('Overview data loading completed');
            } else {
                console.warn('API returned success: false', data);
                this.setDefaultOverviewStats();
            }
        } catch (error) {
            console.error('Error loading overview data:', error);
            console.error('Error stack:', error.stack);
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

                // Update patients table (data is already filtered and sorted by PHP)
                this.updatePatientsTable(data.patients);
                
                // Store SQL code for display
                if (data.sql_code) {
                    this.lastSQLQueries['patients'] = data.sql_code;
                    console.log('Stored SQL for patients:', data.sql_code);
                }
                
                // Load filter options
                await this.loadFilterOptions('patients');
            } else {
                console.warn('Patients API returned invalid data:', data);
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

                // Update departments table (data is already sorted by PHP)
                this.updateTable('departments', data.data);
                
                // Store SQL code for display
                if (data.sql_code) {
                    this.lastSQLQueries['departments'] = data.sql_code;
                    console.log('Stored SQL for departments:', data.sql_code);
                }
                
                // Load filter options
                await this.loadFilterOptions('departments');
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

                // Update doctors table (data is already sorted by PHP)
                this.updateTable('doctors', data.data);
                
                // Store SQL code for display
                if (data.sql_code) {
                    this.lastSQLQueries['doctors'] = data.sql_code;
                    console.log('Stored SQL for doctors:', data.sql_code);
                }
                
                // Load filter options
                await this.loadFilterOptions('doctors');
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

                // Update appointments table (data is already sorted by PHP)
                this.updateTable('appointments', data.data);
                
                // Store SQL code for display
                if (data.sql_code) {
                    this.lastSQLQueries['appointments'] = data.sql_code;
                    console.log('Stored SQL for appointments:', data.sql_code);
                }
                
                // Load filter options
                await this.loadFilterOptions('appointments');
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

                // Update medical reports table (data is already sorted by PHP)
                this.updateTable('medical-reports', data.data);
                
                // Store SQL code for display
                if (data.sql_code) {
                    this.lastSQLQueries['medical-reports'] = data.sql_code;
                    console.log('Stored SQL for medical-reports:', data.sql_code);
                }
                
                // Load filter options
                await this.loadFilterOptions('medical-reports');
            }
        } catch (error) {
            console.error('Error loading medical reports data:', error);
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

        // Display data exactly as returned from PHP (already filtered and sorted)
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

        if (!data || !Array.isArray(data)) {
            console.warn(`Invalid data for ${type} table:`, data);
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">No data available</td></tr>';
            return;
        }

        let html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="100%" class="text-center">No records found</td></tr>';
        } else {
            // Display data exactly as returned from PHP (already filtered and sorted)
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

    // REFACTORED: This now ONLY sends form data to PHP, no client-side processing
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
                // Update table with PHP-filtered and sorted data
                if (type === 'patients') {
                    // Patients API returns data in 'data' field for filter action
                    this.updatePatientsTable(data.data || data.patients);
                } else {
                    this.updateTable(type, data.data);
                }
                
                // Store SQL code for display
                if (data.sql_code) {
                    this.lastSQLQueries[type] = data.sql_code;
                    console.log(`Stored SQL for ${type}:`, data.sql_code);
                }
            }
        } catch (error) {
            console.error('Error applying filters:', error);
        } finally {
            this.hideLoading();
        }
    }

    // Get filter values from form inputs (NO PROCESSING - just collection)
    getFilters(type) {
        const filters = {};
        
        switch (type) {
            case 'patients':
                filters.name = document.getElementById('filter-patient-name')?.value || '';
                filters.gender = document.getElementById('filter-patient-gender')?.value || '';
                filters.blood_group = document.getElementById('filter-patient-blood-group')?.value || '';
                filters.phone = document.getElementById('filter-patient-phone')?.value || '';
                filters.email = document.getElementById('filter-patient-email')?.value || '';
                filters.birth_year = document.getElementById('filter-patient-birth-year')?.value || '';
                filters.registered_date = document.getElementById('filter-patient-registered-date')?.value || '';
                filters.sort_by = document.getElementById('sort-patient-by')?.value || 'name';
                filters.sort_order = document.getElementById('sort-patient-order')?.value || 'ASC';
                break;
            case 'departments':
                filters.name = document.getElementById('filter-dept-name')?.value || '';
                filters.location = document.getElementById('filter-dept-location')?.value || '';
                filters.contact_number = document.getElementById('filter-dept-contact')?.value || '';
                filters.head_doctor_name = document.getElementById('filter-dept-head-doctor')?.value || '';
                filters.created_date = document.getElementById('filter-dept-created-date')?.value || '';
                filters.sort_by = document.getElementById('sort-dept-by')?.value || 'name';
                filters.sort_order = document.getElementById('sort-dept-order')?.value || 'ASC';
                break;
            case 'doctors':
                filters.name = document.getElementById('filter-doctor-name')?.value || '';
                filters.specialization = document.getElementById('filter-doctor-specialization')?.value || '';
                filters.department = document.getElementById('filter-doctor-department')?.value || '';
                filters.phone = document.getElementById('filter-doctor-phone')?.value || '';
                filters.email = document.getElementById('filter-doctor-email')?.value || '';
                filters.experience = document.getElementById('filter-doctor-experience')?.value || '';
                filters.fee = document.getElementById('filter-doctor-fee')?.value || '';
                filters.availability = document.getElementById('filter-doctor-availability')?.value || '';
                filters.sort_by = document.getElementById('sort-doctor-by')?.value || 'name';
                filters.sort_order = document.getElementById('sort-doctor-order')?.value || 'ASC';
                break;
            case 'appointments':
                filters.patient_name = document.getElementById('filter-appointment-patient')?.value || '';
                filters.doctor_name = document.getElementById('filter-appointment-doctor')?.value || '';
                filters.department_name = document.getElementById('filter-appointment-department')?.value || '';
                filters.date_from = document.getElementById('filter-appointment-date-from')?.value || '';
                filters.date_to = document.getElementById('filter-appointment-date-to')?.value || '';
                filters.status = document.getElementById('filter-appointment-status')?.value || '';
                filters.sort_by = document.getElementById('sort-appointment-by')?.value || 'appointment_date';
                filters.sort_order = document.getElementById('sort-appointment-order')?.value || 'DESC';
                break;
            case 'medical-reports':
                filters.patient_name = document.getElementById('filter-report-patient')?.value || '';
                filters.doctor_name = document.getElementById('filter-report-doctor')?.value || '';
                filters.department_name = document.getElementById('filter-report-department')?.value || '';
                filters.date_from = document.getElementById('filter-report-date-from')?.value || '';
                filters.date_to = document.getElementById('filter-report-date-to')?.value || '';
                filters.follow_up_status = document.getElementById('filter-report-follow-up')?.value || '';
                filters.sort_by = document.getElementById('sort-report-by')?.value || 'visit_date';
                filters.sort_order = document.getElementById('sort-report-order')?.value || 'DESC';
                break;
        }
        
        return filters;
    }

    clearFilters(type) {
        const form = document.querySelector(`#${type}-page form`);
        if (form) {
            form.reset();
        }
        
        // Reload data without filters
        this.loadPageData(type);
    }

    async loadFilterOptions(type) {
        try {
            const response = await fetch(`php/${type}.php?action=get_filter_options`);
            const data = await response.json();
            
            if (data.success && data.data) {
                this.populateFilterOptions(type, data.data);
            }
        } catch (error) {
            console.error(`Error loading filter options for ${type}:`, error);
        }
    }

    populateFilterOptions(type, options) {
        switch (type) {
            case 'patients':
                this.populateSelectOptions('filter-patient-name', options.names || []);
                this.populateSelectOptions('filter-patient-phone', options.phones || []);
                this.populateSelectOptions('filter-patient-email', options.emails || []);
                this.populateSelectOptions('filter-patient-birth-year', options.birth_years || []);
                this.populateSelectOptions('filter-patient-registered-date', options.registered_dates || []);
                break;
            case 'departments':
                this.populateSelectOptions('filter-dept-name', options.names || []);
                this.populateSelectOptions('filter-dept-location', options.locations || []);
                this.populateSelectOptions('filter-dept-contact', options.contact_numbers || []);
                this.populateSelectOptions('filter-dept-head-doctor', options.head_doctor_names || []);
                this.populateSelectOptions('filter-dept-created-date', options.created_dates || []);
                break;
            case 'doctors':
                this.populateSelectOptions('filter-doctor-name', options.names || []);
                this.populateSelectOptions('filter-doctor-specialization', options.specializations || []);
                this.populateSelectOptions('filter-doctor-department', options.departments || []);
                this.populateSelectOptions('filter-doctor-phone', options.phones || []);
                this.populateSelectOptions('filter-doctor-email', options.emails || []);
                this.populateSelectOptions('filter-doctor-experience', options.experience_years || []);
                this.populateSelectOptions('filter-doctor-fee', options.consultation_fees || []);
                break;
            case 'appointments':
                this.populateSelectOptions('filter-appointment-patient', options.patient_names || []);
                this.populateSelectOptions('filter-appointment-doctor', options.doctor_names || []);
                this.populateSelectOptions('filter-appointment-department', options.department_names || []);
                break;
            case 'medical-reports':
                this.populateSelectOptions('filter-report-patient', options.patient_names || []);
                this.populateSelectOptions('filter-report-doctor', options.doctor_names || []);
                this.populateSelectOptions('filter-report-department', options.department_names || []);
                break;
        }
    }

    populateSelectOptions(selectId, options) {
        const selectElement = document.getElementById(selectId);
        if (!selectElement) return;

        const firstOption = selectElement.querySelector('option').textContent;
        selectElement.innerHTML = `<option value="">${firstOption}</option>`;
        
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option;
            selectElement.appendChild(optionElement);
        });
    }

    async exportToCSV(type) {
        try {
            this.showLoading();
            
            const filters = this.getFilters(type);
            const formData = {
                action: 'export_csv',
                ...filters
            };
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `php/${type}.php`;
            form.style.display = 'none';
            
            Object.keys(formData).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = formData[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            this.showSuccessMessage(`${this.getTypeName(type)} data exported successfully!`);
            
        } catch (error) {
            console.error('Error exporting CSV:', error);
            this.showErrorMessage('Error exporting data. Please try again.');
        } finally {
            this.hideLoading();
        }
    }

    showSuccessMessage(message) {
        const notification = document.createElement('div');
        notification.className = 'notification success';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    showErrorMessage(message) {
        const notification = document.createElement('div');
        notification.className = 'notification error';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #e74c3c;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    getTypeName(type) {
        const names = {
            'patients': 'Patients',
            'departments': 'Departments',
            'doctors': 'Doctors',
            'appointments': 'Appointments',
            'medical-reports': 'Medical Reports'
        };
        return names[type] || type;
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
        alert(message);
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

    truncateText(text, maxLength) {
        return text && text.length > maxLength ? text.substring(0, maxLength) + '...' : text || '';
    }

    isFollowUpOverdue(followUpDate) {
        const today = new Date();
        const followUp = new Date(followUpDate);
        return followUp < today;
    }

    openCrudModal(type, action, id = null) {
        console.log(`Opening ${action} modal for ${type}`, id);
        if (window.crudManager) {
            window.crudManager.openModal(type, action, id);
        } else {
            console.error('CrudManager not available');
            alert('CRUD functionality not available. Please refresh the page.');
        }
    }

    closeCrudModal() {
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
        if (window.crudManager) {
            window.crudManager.openModal(type, 'view', id);
        } else {
            alert('View functionality not available');
        }
    }

    editRecord(type, id) {
        console.log(`Editing ${type} record:`, id);
        if (window.crudManager) {
            window.crudManager.openModal(type, 'edit', id);
        } else {
            alert('Edit functionality not available');
        }
    }

    deleteRecord(type, id) {
        if (confirm('Are you sure you want to delete this record?')) {
            console.log(`Deleting ${type} record:`, id);
            if (window.crudManager) {
                window.crudManager.deleteRecord(type, id);
            } else {
                alert('Delete functionality not available');
            }
        }
    }

    updateCharts(data) {
        console.log('updateCharts called with data:', data);
        console.log('window.chartManager available:', !!window.chartManager);
        
        if (window.chartManager && data) {
            console.log('Calling chartManager.updateCharts...');
            window.chartManager.updateCharts(data);
        } else {
            console.warn('chartManager not available or no data provided');
        }
    }

    // SQL Code Display Methods
    showSQLCode(module) {
        console.log('=== showSQLCode DEBUG ===');
        console.log('1. Module requested:', module);
        console.log('2. this.lastSQLQueries object:', this.lastSQLQueries);
        console.log('3. All keys in lastSQLQueries:', Object.keys(this.lastSQLQueries));
        console.log('4. SQL for this module:', this.lastSQLQueries[module]);
        
        const sqlQuery = this.lastSQLQueries[module];
        
        if (sqlQuery && sqlQuery.trim() !== '') {
            console.log('5. SQL query found, length:', sqlQuery.length);
            
            const sqlDisplay = document.getElementById('sql-popup-code-display');
            console.log('6. sql-popup-code-display element:', sqlDisplay);
            
            if (sqlDisplay) {
                const formattedSQL = this.formatSQL(sqlQuery);
                console.log('7. Formatted SQL:', formattedSQL);
                
                sqlDisplay.textContent = formattedSQL;
                console.log('8. textContent set, current value:', sqlDisplay.textContent);
            } else {
                console.error('ERROR: sql-popup-code-display element not found!');
            }
            
            const sqlPopup = document.getElementById('sql-popup');
            if (sqlPopup) {
                sqlPopup.style.display = 'flex';
                console.log('9. SQL popup displayed');
            } else {
                console.error('ERROR: sql-popup element not found!');
            }
        } else {
            console.warn('WARNING: No SQL query available for module:', module);
            console.warn('lastSQLQueries state:', JSON.stringify(this.lastSQLQueries, null, 2));
            alert(`No SQL query available for ${module}.\n\nPlease:\n1. Load the page data first, OR\n2. Apply some filters\n\nCheck browser console for details.`);
        }
        
        console.log('=== showSQLCode END ===');
    }

    formatSQL(sql) {
        return sql
            .replace(/SELECT/gi, 'SELECT')
            .replace(/FROM/gi, '\nFROM')
            .replace(/WHERE/gi, '\nWHERE')
            .replace(/AND/gi, '\n  AND')
            .replace(/OR/gi, '\n  OR')
            .replace(/ORDER BY/gi, '\nORDER BY')
            .replace(/GROUP BY/gi, '\nGROUP BY')
            .replace(/HAVING/gi, '\nHAVING')
            .replace(/LIMIT/gi, '\nLIMIT');
    }

    closeSQLPopup() {
        document.getElementById('sql-popup').style.display = 'none';
    }

    copySQLCode() {
        const sqlCode = document.getElementById('sql-popup-code-display').textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(sqlCode).then(() => {
                alert('SQL code copied to clipboard!');
            });
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = sqlCode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('SQL code copied to clipboard!');
        }
    }
}

// Global functions for HTML onclick events
function showSQLCode(module) {
    if (window.dashboard) {
        window.dashboard.showSQLCode(module);
    } else {
        alert('Dashboard not available');
    }
}

function closeSQLPopup() {
    if (window.dashboard) {
        window.dashboard.closeSQLPopup();
    } else {
        document.getElementById('sql-popup').style.display = 'none';
    }
}

function copySQLCode() {
    if (window.dashboard) {
        window.dashboard.copySQLCode();
    } else {
        alert('Dashboard not available');
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('main.js: DOMContentLoaded event fired');
    
    setTimeout(() => {
        try {
            window.dashboard = new HospitalDashboard();
            console.log('main.js: Dashboard instance created and assigned to window.dashboard');
            
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
    
    if (!window.dashboard) {
        console.error('Dashboard not available');
        alert('Dashboard not loaded yet. Please refresh the page and try again.');
        return;
    }
    
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

function viewRecord(type, id) {
    window.dashboard.viewRecord(type, id);
}

function editRecord(type, id) {
    window.dashboard.editRecord(type, id);
}

function deleteRecord(type, id) {
    window.dashboard.deleteRecord(type, id);
}

function toggleSQLDisplay(type) {
    window.dashboard.showSQLCode(type);
}

function closeSQLModal() {
    document.getElementById('sql-modal').style.display = 'none';
}

function exportToCSV(type) {
    if (window.dashboard) {
        window.dashboard.exportToCSV(type);
    } else {
        alert('Dashboard not available');
    }
}

// Logout function
async function logout() {
    if (!confirm('Are you sure you want to logout?')) {
        return;
    }
    
    try {
        const response = await fetch('php/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'logout'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Clear any stored data
            localStorage.removeItem('currentUser');
            
            // Redirect to login page
            window.location.href = 'login.html';
        } else {
            alert('Logout failed. Please try again.');
        }
    } catch (error) {
        console.error('Logout error:', error);
        // Even if logout fails on server, redirect to login
        window.location.href = 'login.html';
    }
}

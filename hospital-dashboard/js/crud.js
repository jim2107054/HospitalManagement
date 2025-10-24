// CRUD Operations for Hospital Management Dashboard
console.log('crud.js: Script loaded');

class CrudManager {
    constructor() {
        console.log('crud.js: CrudManager constructor called');
        this.currentType = null;
        this.currentAction = null;
        this.currentId = null;
    }

    async openModal(type, action, id = null) {
        console.log('CrudManager.openModal called with:', { type, action, id });
        
        this.currentType = type;
        this.currentAction = action;
        this.currentId = id;

        const modal = document.getElementById('crud-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');

        if (!modal || !modalTitle || !modalBody) {
            console.error('Modal elements not found in DOM');
            alert('Modal elements not found. Please refresh the page.');
            return;
        }

        // Set modal title
        const titles = {
            'create': `Add New ${this.getTypeName(type)}`,
            'edit': `Edit ${this.getTypeName(type)}`,
            'view': `View ${this.getTypeName(type)}`
        };
        modalTitle.textContent = titles[action] || 'Form';

        try {
            console.log('Generating form for:', type, action);
            // Generate form based on type and action
            modalBody.innerHTML = await this.generateForm(type, action, id);
            console.log('Form generated successfully');

            // Setup modal footer based on action
            this.setupModalFooter(modal, action);

            // Show modal
            modal.style.display = 'block';
            console.log('Modal should now be visible');

            // Load existing data for edit action
            if (action === 'edit' && id) {
                await this.loadExistingData(type, id);
            }
        } catch (error) {
            console.error('Error opening modal:', error);
            modalBody.innerHTML = `<p class="error">Error loading form: ${error.message}. Please try again.</p>`;
            modal.style.display = 'block';
        }
    }

    async generateForm(type, action, id) {
        const forms = {
            'patients': this.generatePatientsForm(),
            'departments': await this.generateDepartmentsForm(),
            'doctors': await this.generateDoctorsForm(),
            'appointments': await this.generateAppointmentsForm(),
            'medical-reports': await this.generateMedicalReportsForm()
        };

        return forms[type] || '<p>Form not available</p>';
    }

    setupModalFooter(modal, action) {
        const modalFooter = modal.querySelector('.modal-footer');
        if (!modalFooter) return;

        if (action === 'view') {
            modalFooter.innerHTML = `
                <button class="btn btn-secondary" onclick="crudManager.closeModal()">Close</button>
            `;
        } else {
            modalFooter.innerHTML = `
                <button class="btn btn-secondary" onclick="crudManager.closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="crudManager.submitForm()">${action === 'create' ? 'Create' : 'Update'}</button>
            `;
        }
    }

    generatePatientsForm() {
        return `
            <form id="crud-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" class="form-control">
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact_name">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_phone">Emergency Contact Phone</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="insurance_number">Insurance Number</label>
                    <input type="text" id="insurance_number" name="insurance_number" class="form-control">
                </div>
            </form>
        `;
    }

    async generateDepartmentsForm() {
        const doctors = await this.loadDoctors();
        
        return `
            <form id="crud-form">
                <div class="form-group">
                    <label for="name">Department Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="head_doctor_id">Head Doctor</label>
                    <select id="head_doctor_id" name="head_doctor_id" class="form-control">
                        <option value="">Select Head Doctor</option>
                        ${doctors.map(doctor => `<option value="${doctor.id}">${doctor.name}</option>`).join('')}
                    </select>
                </div>
            </form>
        `;
    }

    async generateDoctorsForm() {
        const departments = await this.loadDepartments();
        
        return `
            <form id="crud-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Doctor Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="specialization">Specialization *</label>
                        <input type="text" id="specialization" name="specialization" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department_id">Department *</label>
                        <select id="department_id" name="department_id" class="form-control" required>
                            <option value="">Select Department</option>
                            ${departments.map(dept => `<option value="${dept.id}">${dept.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="license_number">License Number *</label>
                        <input type="text" id="license_number" name="license_number" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="experience_years">Experience (Years)</label>
                        <input type="number" id="experience_years" name="experience_years" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label for="consultation_fee">Consultation Fee</label>
                        <input type="number" id="consultation_fee" name="consultation_fee" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="available_from">Available From</label>
                        <input type="time" id="available_from" name="available_from" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="available_to">Available To</label>
                        <input type="time" id="available_to" name="available_to" class="form-control">
                    </div>
                </div>
            </form>
        `;
    }

    async generateAppointmentsForm() {
        const [patients, doctors] = await Promise.all([
            this.loadPatients(),
            this.loadDoctors()
        ]);
        
        return `
            <form id="crud-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Patient *</label>
                        <select id="patient_id" name="patient_id" class="form-control" required>
                            <option value="">Select Patient</option>
                            ${patients.map(patient => `<option value="${patient.id}">${patient.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="doctor_id">Doctor *</label>
                        <select id="doctor_id" name="doctor_id" class="form-control" required>
                            <option value="">Select Doctor</option>
                            ${doctors.map(doctor => `<option value="${doctor.id}">${doctor.name} - ${doctor.specialization}</option>`).join('')}
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Appointment Date *</label>
                        <input type="date" id="appointment_date" name="appointment_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Appointment Time *</label>
                        <input type="time" id="appointment_time" name="appointment_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="No-Show">No-Show</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="consultation_fee">Consultation Fee</label>
                        <input type="number" id="consultation_fee" name="consultation_fee" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reason_for_visit">Reason for Visit</label>
                    <textarea id="reason_for_visit" name="reason_for_visit" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
            </form>
        `;
    }

    async generateMedicalReportsForm() {
        const [patients, doctors, appointments] = await Promise.all([
            this.loadPatients(),
            this.loadDoctors(),
            this.loadAppointments()
        ]);
        
        return `
            <form id="crud-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Patient *</label>
                        <select id="patient_id" name="patient_id" class="form-control" required>
                            <option value="">Select Patient</option>
                            ${patients.map(patient => `<option value="${patient.id}">${patient.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="doctor_id">Doctor *</label>
                        <select id="doctor_id" name="doctor_id" class="form-control" required>
                            <option value="">Select Doctor</option>
                            ${doctors.map(doctor => `<option value="${doctor.id}">${doctor.name} - ${doctor.specialization}</option>`).join('')}
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_id">Related Appointment</label>
                        <select id="appointment_id" name="appointment_id" class="form-control">
                            <option value="">Select Appointment (Optional)</option>
                            ${appointments.map(apt => `<option value="${apt.id}">Appointment #${apt.id} - ${apt.appointment_date}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="visit_date">Visit Date *</label>
                        <input type="date" id="visit_date" name="visit_date" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="diagnosis">Diagnosis *</label>
                    <textarea id="diagnosis" name="diagnosis" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="symptoms">Symptoms</label>
                    <textarea id="symptoms" name="symptoms" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="treatment_plan">Treatment Plan</label>
                    <textarea id="treatment_plan" name="treatment_plan" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="medication_prescribed">Medication Prescribed</label>
                    <textarea id="medication_prescribed" name="medication_prescribed" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="follow_up_date">Follow-up Date</label>
                        <input type="date" id="follow_up_date" name="follow_up_date" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="medical_notes">Medical Notes</label>
                    <textarea id="medical_notes" name="medical_notes" class="form-control" rows="4"></textarea>
                </div>
            </form>
        `;
    }

    async loadExistingData(type, id) {
        try {
            const response = await fetch(`php/${type}.php?action=get&id=${id}`);
            const data = await response.json();

            if (data.success && data.data) {
                this.populateForm(data.data);
            }
        } catch (error) {
            console.error('Error loading existing data:', error);
        }
    }

    populateForm(data) {
        Object.keys(data).forEach(key => {
            const field = document.getElementById(key);
            if (field) {
                field.value = data[key] || '';
            }
        });
    }

    async submitForm() {
        const form = document.getElementById('crud-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            window.dashboard.showLoading();
            
            const url = `php/${this.currentType}.php`;
            const method = 'POST';
            
            data.action = this.currentAction;
            if (this.currentAction === 'edit') {
                data.id = this.currentId;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.closeModal();
                window.dashboard.loadDataTable(this.currentType);
                this.showSuccessMessage(result.message || 'Operation completed successfully');
            } else {
                this.showErrorMessage(result.message || 'Operation failed');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            this.showErrorMessage('An error occurred. Please try again.');
        } finally {
            window.dashboard.hideLoading();
        }
    }

    closeModal() {
        console.log('CrudManager.closeModal called');
        const modal = document.getElementById('crud-modal');
        if (modal) {
            modal.style.display = 'none';
            console.log('Modal hidden');
        } else {
            console.error('Modal element not found');
        }
        this.currentType = null;
        this.currentAction = null;
        this.currentId = null;
    }

    async deleteRecord(type, id) {
        if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            return;
        }

        try {
            window.dashboard.showLoading();
            
            const response = await fetch(`php/${type}.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: id
                })
            });

            const result = await response.json();

            if (result.success) {
                window.dashboard.loadDataTable(type);
                this.showSuccessMessage('Record deleted successfully');
            } else {
                this.showErrorMessage(result.message || 'Failed to delete record');
            }
        } catch (error) {
            console.error('Error deleting record:', error);
            this.showErrorMessage('An error occurred while deleting the record');
        } finally {
            window.dashboard.hideLoading();
        }
    }

    async viewRecord(type, id) {
        try {
            const response = await fetch(`php/${type}.php?action=get&id=${id}`);
            const data = await response.json();

            if (data.success && data.data) {
                this.showViewModal(type, data.data);
            }
        } catch (error) {
            console.error('Error loading record:', error);
        }
    }

    showViewModal(type, data) {
        const modal = document.getElementById('crud-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');

        modalTitle.textContent = `View ${this.getTypeName(type)}`;
        modalBody.innerHTML = this.generateViewContent(type, data);

        // Hide save button for view mode
        const modalFooter = modal.querySelector('.modal-footer');
        modalFooter.innerHTML = `
            <button class="btn btn-secondary" onclick="crudManager.closeModal()">Close</button>
        `;

        modal.style.display = 'block';
    }

    generateViewContent(type, data) {
        // Generate read-only view of the data
        let html = '<div class="view-content">';
        
        Object.keys(data).forEach(key => {
            if (key !== 'id') {
                const label = this.formatLabel(key);
                const value = data[key] || '-';
                html += `
                    <div class="view-item">
                        <strong>${label}:</strong> ${value}
                    </div>
                `;
            }
        });
        
        html += '</div>';
        return html;
    }

    formatLabel(key) {
        return key.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    getTypeName(type) {
        const names = {
            'patients': 'Patient',
            'departments': 'Department',
            'doctors': 'Doctor',
            'appointments': 'Appointment',
            'medical-reports': 'Medical Report'
        };
        return names[type] || type;
    }

    showSuccessMessage(message) {
        // Create a simple success notification
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
        // Create a simple error notification
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

    // Helper methods to load data for dropdowns
    async loadDepartments() {
        try {
            const response = await fetch('php/departments.php?action=list');
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            return [];
        }
    }

    async loadDoctors() {
        try {
            const response = await fetch('php/doctors.php?action=list');
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            return [];
        }
    }

    async loadPatients() {
        try {
            const response = await fetch('php/patients.php?action=list');
            const data = await response.json();
            return data.success ? data.patients : [];
        } catch (error) {
            return [];
        }
    }

    async loadAppointments() {
        try {
            const response = await fetch('php/appointments.php?action=list');
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            return [];
        }
    }
}

// Initialize CRUD manager
console.log('crud.js: Creating CrudManager instance');
const crudManager = new CrudManager();
window.crudManager = crudManager;
console.log('crud.js: CrudManager instance created and assigned to window.crudManager:', crudManager);

// Verification function
function verifyCrudSetup() {
    const modal = document.getElementById('crud-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    
    console.log('CRUD Setup Verification:');
    console.log('Modal element:', modal ? 'Found' : 'NOT FOUND');
    console.log('Modal title element:', modalTitle ? 'Found' : 'NOT FOUND');
    console.log('Modal body element:', modalBody ? 'Found' : 'NOT FOUND');
    console.log('CrudManager instance:', crudManager ? 'Created' : 'NOT CREATED');
    
    return modal && modalTitle && modalBody && crudManager;
}

// Global functions for HTML event handlers
function openCrudModal(type, action, id = null) {
    console.log('Global openCrudModal called:', type, action, id);
    
    // Verify setup before proceeding
    if (!verifyCrudSetup()) {
        console.error('CRUD setup verification failed');
        return;
    }
    
    crudManager.openModal(type, action, id);
}

function closeCrudModal() {
    console.log('Global closeCrudModal called');
    crudManager.closeModal();
}

function submitCrudForm() {
    console.log('Global submitCrudForm called');
    crudManager.submitForm();
}

// Make functions available on window object
window.openCrudModal = openCrudModal;
window.closeCrudModal = closeCrudModal;
window.submitCrudForm = submitCrudForm;
window.verifyCrudSetup = verifyCrudSetup;

// Add a test function to verify modal works
window.testModal = function() {
    console.log('Test modal function called');
    openCrudModal('patients', 'create');
};

// Update dashboard methods to use CRUD manager
document.addEventListener('DOMContentLoaded', function() {
    // Wait for dashboard to be available
    function connectToDashboard() {
        if (window.dashboard) {
            window.dashboard.openCrudModal = (type, action, id) => crudManager.openModal(type, action, id);
            window.dashboard.deleteRecord = (type, id) => crudManager.deleteRecord(type, id);
            window.dashboard.viewRecord = (type, id) => crudManager.viewRecord(type, id);
            window.dashboard.closeCrudModal = () => crudManager.closeModal();
            console.log('CRUD manager connected to dashboard');
        } else {
            // Retry in 100ms if dashboard not ready
            setTimeout(connectToDashboard, 100);
        }
    }
    connectToDashboard();
});
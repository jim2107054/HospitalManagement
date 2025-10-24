-- Hospital Management System Database Schema
-- Created: October 24, 2025

-- Create database
CREATE DATABASE IF NOT EXISTS hospital_management;
USE hospital_management;

-- Departments Table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL COMMENT 'e.g., Cardiology, Neurology, Emergency',
    description TEXT,
    head_doctor_id INT,
    contact_number VARCHAR(20),
    location VARCHAR(200) COMMENT 'Physical location in hospital',
    created_at DATE DEFAULT (CURRENT_DATE)
);

-- Doctors Table
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL COMMENT 'Medical specialty',
    department_id INT NOT NULL,
    phone VARCHAR(15) UNIQUE,
    email VARCHAR(100) UNIQUE,
    license_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Medical license ID',
    experience_years INT,
    consultation_fee DECIMAL(10,2),
    available_from TIME COMMENT 'Daily availability start time',
    available_to TIME COMMENT 'Daily availability end time',
    joined_at DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Patients Table
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    phone VARCHAR(15) UNIQUE,
    email VARCHAR(100),
    address TEXT,
    blood_group ENUM('A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(15),
    insurance_number VARCHAR(50) UNIQUE,
    registered_at DATE DEFAULT (CURRENT_DATE)
);

-- Appointments Table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-Show') DEFAULT 'Scheduled',
    reason_for_visit TEXT,
    consultation_fee DECIMAL(10,2) COMMENT 'Fee charged for this appointment',
    notes TEXT COMMENT 'Additional appointment notes',
    created_at DATE DEFAULT (CURRENT_DATE),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Medical Records Table
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    diagnosis TEXT NOT NULL COMMENT 'Medical diagnosis',
    symptoms TEXT COMMENT 'Patient reported symptoms',
    treatment_plan TEXT COMMENT 'Prescribed treatment plan',
    medication_prescribed TEXT COMMENT 'Prescribed medications',
    visit_date DATE NOT NULL,
    follow_up_date DATE COMMENT 'Next scheduled follow-up',
    medical_notes TEXT COMMENT 'Doctor notes and observations',
    created_at DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Add foreign key for department head doctor
ALTER TABLE departments ADD FOREIGN KEY (head_doctor_id) REFERENCES doctors(id) ON DELETE SET NULL;

-- Insert Sample Data for Testing

-- Sample Departments
INSERT INTO departments (name, description, contact_number, location) VALUES
('Cardiology', 'Heart and cardiovascular diseases treatment', '555-0101', 'Building A, Floor 2'),
('Neurology', 'Brain and nervous system disorders', '555-0102', 'Building B, Floor 3'),
('Emergency', '24/7 emergency medical services', '555-0103', 'Building A, Ground Floor'),
('Pediatrics', 'Medical care for children', '555-0104', 'Building C, Floor 1'),
('Orthopedics', 'Bone, joint and muscle treatment', '555-0105', 'Building B, Floor 2'),
('Oncology', 'Cancer treatment and care', '555-0106', 'Building C, Floor 3');

-- Sample Doctors
INSERT INTO doctors (name, specialization, department_id, phone, email, license_number, experience_years, consultation_fee, available_from, available_to) VALUES
('Dr. John Smith', 'Cardiologist', 1, '555-1001', 'john.smith@hospital.com', 'MD001', 15, 150.00, '09:00:00', '17:00:00'),
('Dr. Sarah Johnson', 'Neurologist', 2, '555-1002', 'sarah.johnson@hospital.com', 'MD002', 12, 180.00, '08:00:00', '16:00:00'),
('Dr. Michael Brown', 'Emergency Medicine', 3, '555-1003', 'michael.brown@hospital.com', 'MD003', 8, 120.00, '00:00:00', '23:59:59'),
('Dr. Emily Davis', 'Pediatrician', 4, '555-1004', 'emily.davis@hospital.com', 'MD004', 10, 100.00, '08:30:00', '16:30:00'),
('Dr. Robert Wilson', 'Orthopedic Surgeon', 5, '555-1005', 'robert.wilson@hospital.com', 'MD005', 18, 200.00, '09:00:00', '15:00:00'),
('Dr. Lisa Anderson', 'Oncologist', 6, '555-1006', 'lisa.anderson@hospital.com', 'MD006', 14, 220.00, '10:00:00', '18:00:00');

-- Update departments with head doctors
UPDATE departments SET head_doctor_id = 1 WHERE id = 1;
UPDATE departments SET head_doctor_id = 2 WHERE id = 2;
UPDATE departments SET head_doctor_id = 3 WHERE id = 3;
UPDATE departments SET head_doctor_id = 4 WHERE id = 4;
UPDATE departments SET head_doctor_id = 5 WHERE id = 5;
UPDATE departments SET head_doctor_id = 6 WHERE id = 6;

-- Sample Patients
INSERT INTO patients (name, date_of_birth, gender, phone, email, address, blood_group, emergency_contact_name, emergency_contact_phone, insurance_number) VALUES
('Alice Johnson', '1985-03-15', 'Female', '555-2001', 'alice.johnson@email.com', '123 Main St, City', 'A+', 'Bob Johnson', '555-2101', 'INS001'),
('Mark Williams', '1978-07-22', 'Male', '555-2002', 'mark.williams@email.com', '456 Oak Ave, City', 'B+', 'Susan Williams', '555-2102', 'INS002'),
('Emma Brown', '1992-11-08', 'Female', '555-2003', 'emma.brown@email.com', '789 Pine Rd, City', 'O+', 'David Brown', '555-2103', 'INS003'),
('James Davis', '1965-01-30', 'Male', '555-2004', 'james.davis@email.com', '321 Elm St, City', 'AB+', 'Mary Davis', '555-2104', 'INS004'),
('Sophie Miller', '1995-05-18', 'Female', '555-2005', 'sophie.miller@email.com', '654 Maple Dr, City', 'A-', 'Tom Miller', '555-2105', 'INS005'),
('Daniel Wilson', '1988-09-12', 'Male', '555-2006', 'daniel.wilson@email.com', '987 Cedar Ln, City', 'O-', 'Anna Wilson', '555-2106', 'INS006'),
('Olivia Garcia', '1983-12-03', 'Female', '555-2007', 'olivia.garcia@email.com', '147 Birch Ave, City', 'B-', 'Carlos Garcia', '555-2107', 'INS007');

-- Sample Appointments
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, reason_for_visit, consultation_fee, notes) VALUES
(1, 1, '2025-10-25', '10:00:00', 'Scheduled', 'Chest pain and palpitations', 150.00, 'First-time consultation'),
(2, 2, '2025-10-25', '11:30:00', 'Scheduled', 'Frequent headaches', 180.00, 'Follow-up visit'),
(3, 4, '2025-10-26', '09:15:00', 'Scheduled', 'Child vaccination', 100.00, 'Annual vaccination schedule'),
(4, 5, '2025-10-26', '14:00:00', 'Completed', 'Knee pain', 200.00, 'X-ray recommended'),
(5, 6, '2025-10-27', '15:30:00', 'Scheduled', 'Oncology consultation', 220.00, 'Cancer screening'),
(6, 1, '2025-10-28', '09:30:00', 'Scheduled', 'Heart checkup', 150.00, 'Regular checkup'),
(7, 3, '2025-10-24', '22:15:00', 'Completed', 'Accident injury', 120.00, 'Emergency case');

-- Sample Medical Records
INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, symptoms, treatment_plan, medication_prescribed, visit_date, follow_up_date, medical_notes) VALUES
(4, 5, 4, 'Mild arthritis in right knee', 'Joint pain, stiffness, difficulty walking', 'Physical therapy, anti-inflammatory medication', 'Ibuprofen 400mg twice daily', '2025-10-24', '2025-11-24', 'Patient shows signs of improvement with current treatment'),
(7, 3, 7, 'Minor laceration on left arm', 'Bleeding, pain from fall', 'Wound cleaning, stitches, tetanus shot', 'Antibiotics for infection prevention', '2025-10-24', '2025-10-31', 'Wound healing properly, stitches to be removed in 7 days');

-- Create indexes for better performance
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_patients_name ON patients(name);
CREATE INDEX idx_doctors_department ON doctors(department_id);
CREATE INDEX idx_medical_records_patient ON medical_records(patient_id);
CREATE INDEX idx_medical_records_visit_date ON medical_records(visit_date);
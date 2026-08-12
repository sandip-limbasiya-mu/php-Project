-- =====================================================
-- Hospital Appointment Booking System Database
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS hospital_appointment;
USE hospital_appointment;

-- =====================================================
-- 1. Users Table (Stores Admin and Patient data)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    age INT NOT NULL,
    address TEXT NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. Doctors Table (Stores Doctor information)
-- =====================================================
CREATE TABLE IF NOT EXISTS doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    availability VARCHAR(100) NOT NULL,
    fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. Appointments Table (Stores Booking details)
-- =====================================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR(20) NOT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- =====================================================
-- Insert Sample Data - Admin User
-- =====================================================
-- NOTE: Passwords will be set by running fix_passwords.php in browser AFTER import
INSERT INTO users (name, email, password, phone, gender, age, address, role) VALUES
('Admin User', 'admin@hospital.com', 'temp_password', '9876543210', 'Male', 35, 'Hospital Street, Medical City', 'admin');

-- =====================================================
-- Insert Sample Data - Patient Users
-- =====================================================
-- NOTE: Passwords will be set by running fix_passwords.php in browser AFTER import
INSERT INTO users (name, email, password, phone, gender, age, address, role) VALUES
('Rajesh Kumar', 'rajesh@email.com', 'temp_password', '9812345678', 'Male', 28, '123 Main Road, Delhi', 'patient'),
('Priya Sharma', 'priya@email.com', 'temp_password', '9887654321', 'Female', 24, '45 Green Park, Mumbai', 'patient'),
('Amit Verma', 'amit@email.com', 'temp_password', '9876512340', 'Male', 45, '78 Civil Lines, Pune', 'patient');

-- =====================================================
-- Insert Sample Data - Doctors
-- =====================================================
INSERT INTO doctors (name, specialization, contact, email, availability, fee) VALUES
('Dr. Anil Gupta', 'General Physician', '9800011111', 'anil.gupta@hospital.com', 'Mon-Fri: 9AM-5PM', 500.00),
('Dr. Sunita Reddy', 'Cardiologist', '9800022222', 'sunita.reddy@hospital.com', 'Mon, Wed, Fri: 10AM-4PM', 1500.00),
('Dr. Rahul Mehta', 'Dermatologist', '9800033333', 'rahul.mehta@hospital.com', 'Tue, Thu, Sat: 9AM-1PM', 800.00),
('Dr. Neha Singh', 'Pediatrician', '9800044444', 'neha.singh@hospital.com', 'Daily: 8AM-12PM', 600.00),
('Dr. Suresh Nair', 'Orthopedic', '9800055555', 'suresh.nair@hospital.com', 'Mon-Fri: 2PM-8PM', 1200.00);

-- =====================================================
-- Insert Sample Data - Appointments
-- =====================================================
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES
(2, 1, '2026-07-30', '10:00 AM', 'Fever and cold for 3 days', 'approved'),
(3, 2, '2026-07-31', '11:30 AM', 'Chest pain and high blood pressure', 'pending'),
(4, 3, '2026-08-01', '09:30 AM', 'Skin allergy and rashes', 'pending');

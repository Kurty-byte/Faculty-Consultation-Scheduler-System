-- FCSS Database - Clean Version for Navicat
-- Drop database if exists and create fresh

DROP DATABASE IF EXISTS fcss_database;
CREATE DATABASE fcss_database;
USE fcss_database;

-- Departments table
CREATE TABLE departments (
  department_id int AUTO_INCREMENT PRIMARY KEY,
  department_name varchar(100) NOT NULL UNIQUE,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
  user_id int AUTO_INCREMENT PRIMARY KEY,
  password_hash varchar(255) NOT NULL,
  first_name varchar(50) NOT NULL,
  last_name varchar(50) NOT NULL,
  middle_name varchar(50),
  birthdate date NOT NULL,
  address varchar(255) NOT NULL,
  email varchar(100) NOT NULL UNIQUE,
  phone_number varchar(20) NOT NULL,
  role enum('faculty','student') NOT NULL,
  profile_picture varchar(255),
  is_active tinyint(1) DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL
);

-- Faculty table
CREATE TABLE faculty (
  faculty_id int AUTO_INCREMENT PRIMARY KEY,
  user_id int NOT NULL,
  department_id int NOT NULL,
  office_email varchar(100) NOT NULL,
  office_phone_number varchar(20) NOT NULL,
  status enum('active','on leave','inactive') DEFAULT 'active',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- Students table
CREATE TABLE students (
  student_id int AUTO_INCREMENT PRIMARY KEY,
  user_id int NOT NULL,
  department_id int NOT NULL,
  year_level int NOT NULL,
  academic_year varchar(9) NOT NULL,
  enrollment_status enum('regular','irregular','shiftee','returnee') NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- Consultation hours table (NEW SYSTEM)
CREATE TABLE consultation_hours (
  consultation_hour_id int AUTO_INCREMENT PRIMARY KEY,
  faculty_id int NOT NULL,
  day_of_week enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  start_time time NOT NULL,
  end_time time NOT NULL,
  is_active tinyint(1) DEFAULT 1,
  notes text,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
);

-- Availability schedules table (LEGACY SUPPORT)
CREATE TABLE availability_schedules (
  schedule_id int AUTO_INCREMENT PRIMARY KEY,
  faculty_id int NOT NULL,
  day_of_week enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  start_time time NOT NULL,
  end_time time NOT NULL,
  is_active tinyint(1) DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
);

-- Appointments table
CREATE TABLE appointments (
  appointment_id int AUTO_INCREMENT PRIMARY KEY,
  schedule_id int NOT NULL,
  student_id int NOT NULL,
  appointment_date date NOT NULL,
  start_time time NOT NULL,
  end_time time NOT NULL,
  slot_duration int DEFAULT 30,
  remarks text,
  is_approved tinyint(1) DEFAULT 0,
  is_cancelled tinyint(1) DEFAULT 0,
  modality enum('physical','virtual') NOT NULL,
  platform varchar(150),
  location varchar(150),
  appointed_on timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_on timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (schedule_id) REFERENCES availability_schedules(schedule_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Appointment history table
CREATE TABLE appointment_history (
  history_id int AUTO_INCREMENT PRIMARY KEY,
  appointment_id int NOT NULL,
  status_change enum('created','approved','rejected','cancelled','completed') NOT NULL,
  changed_by_user_id int NOT NULL,
  notes text,
  changed_at timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by_user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
  notification_id int AUTO_INCREMENT PRIMARY KEY,
  user_id int NOT NULL,
  appointment_id int NOT NULL,
  notification_type enum('appointment_request','appointment_approved','appointment_rejected','appointment_cancelled'),
  message text NOT NULL,
  is_read tinyint(1) DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);

-- Insert sample departments
INSERT INTO departments (department_name) VALUES 
('Computer Science'),
('Information Technology'),
('Engineering');

-- Insert sample users (password is 'password')
INSERT INTO users (password_hash, first_name, last_name, birthdate, address, email, phone_number, role) VALUES
('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '1980-01-01', '123 Faculty St', 'john.doe@university.edu', '+1234567890', 'faculty'),
('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '2000-01-01', '456 Student Ave', 'jane.smith@student.edu', '+0987654321', 'student');

-- Link users to their roles
INSERT INTO faculty (user_id, department_id, office_email, office_phone_number) VALUES
(1, 1, 'john.doe@cs.university.edu', '+1234567890');

INSERT INTO students (user_id, department_id, year_level, academic_year, enrollment_status) VALUES
(2, 1, 3, '2024-2025', 'regular');
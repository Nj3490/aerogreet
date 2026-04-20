-- ============================================================
-- Aero Greet India — Database Schema
-- Run this once via install.php or phpMyAdmin
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Airports master table
CREATE TABLE IF NOT EXISTS airports (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(10)    UNIQUE NOT NULL,
  city         VARCHAR(100)   NOT NULL,
  name         VARCHAR(255)   NOT NULL,
  state        VARCHAR(100),
  dom_price    DECIMAL(10,2),
  intl_price   DECIMAL(10,2),
  porter_price DECIMAL(10,2)  DEFAULT 2385.00,
  buggy_price  DECIMAL(10,2),
  img_url      TEXT,
  active       TINYINT(1)     DEFAULT 1,
  created_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin accounts
CREATE TABLE IF NOT EXISTS admins (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  UNIQUE NOT NULL,
  email         VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('super','admin','staff') DEFAULT 'admin',
  last_login    TIMESTAMP    NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User accounts (optional — customers can register to track bookings)
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255),
  first_name    VARCHAR(100),
  last_name     VARCHAR(100),
  phone         VARCHAR(50),
  verified      TINYINT(1)   DEFAULT 0,
  last_login    TIMESTAMP    NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer rollup table for admin panel
CREATE TABLE IF NOT EXISTS customers (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(190),
  email         VARCHAR(255) UNIQUE NOT NULL,
  phone         VARCHAR(50),
  total_bookings INT         DEFAULT 0,
  last_booking  DATETIME     NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_email (email),
  INDEX idx_customer_last_booking (last_booking)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auth sessions
CREATE TABLE IF NOT EXISTS sessions (
  token        VARCHAR(128) PRIMARY KEY,
  entity_id    INT          NOT NULL,
  entity_type  ENUM('admin','user') NOT NULL,
  expires_at   TIMESTAMP    NOT NULL,
  ip           VARCHAR(45),
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_entity  (entity_id, entity_type),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings
CREATE TABLE IF NOT EXISTS bookings (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ref          VARCHAR(25)  UNIQUE NOT NULL,
  -- airport
  airport_code VARCHAR(10),
  airport_name TEXT,
  -- service
  service_type VARCHAR(100),
  flight_type  VARCHAR(30),
  -- customer
  first_name   VARCHAR(100),
  last_name    VARCHAR(100),
  email        VARCHAR(255),
  phone        VARCHAR(50),
  passengers   VARCHAR(10),
  -- standard flight
  flight_no    VARCHAR(60),
  travel_date  DATE,
  flight_time  TIME,
  service_datetime DATETIME NULL,
  terminal     VARCHAR(20),
  -- transit fields
  is_transit   TINYINT(1)   DEFAULT 0,
  arr_flight_no VARCHAR(50),
  arr_date     DATE,
  arr_time     TIME,
  arr_from     VARCHAR(100),
  dep_flight_no VARCHAR(50),
  dep_date     DATE,
  dep_time     TIME,
  dep_to       VARCHAR(100),
  -- pricing
  price        VARCHAR(100),
  addons       TEXT,
  supplier_name   VARCHAR(150),
  supplier_cost   DECIMAL(10,2),
  selling_price   DECIMAL(10,2),
  invoice_number  VARCHAR(80),
  voucher_file    VARCHAR(255),
  voucher_generated_at DATETIME NULL,
  currency     VARCHAR(10)  DEFAULT 'INR',
  -- meta
  special_req  TEXT,
  source_url   TEXT,
  admin_notes  TEXT,
  status       ENUM('Pending','In Queue','Confirmed','Paid','Completed','Cancelled') DEFAULT 'Pending',
  email_sent   TINYINT(1)   DEFAULT 0,
  user_id      INT          DEFAULT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email     (email),
  INDEX idx_status    (status),
  INDEX idx_airport   (airport_code),
  INDEX idx_created   (created_at),
  INDEX idx_ref       (ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Price change audit log
CREATE TABLE IF NOT EXISTS price_history (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  airport_code VARCHAR(10),
  field_name   VARCHAR(50),
  old_value    DECIMAL(10,2),
  new_value    DECIMAL(10,2),
  changed_by   VARCHAR(100),
  changed_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_code (airport_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email send log
CREATE TABLE IF NOT EXISTS email_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  booking_ref  VARCHAR(25),
  recipient    VARCHAR(255),
  subject      VARCHAR(255),
  status       ENUM('sent','failed') DEFAULT 'sent',
  method       VARCHAR(50),
  error_msg    TEXT,
  sent_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ref (booking_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site settings key/value
CREATE TABLE IF NOT EXISTS settings (
  k            VARCHAR(100) PRIMARY KEY,
  v            TEXT,
  updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact form submissions
CREATE TABLE IF NOT EXISTS contacts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  first_name   VARCHAR(100),
  last_name    VARCHAR(100),
  email        VARCHAR(255),
  phone        VARCHAR(50),
  subject      VARCHAR(200),
  message      TEXT,
  replied      TINYINT(1)   DEFAULT 0,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ── v2 additions (run ALTER if upgrading existing install) ──────

ALTER TABLE bookings
  ADD COLUMN IF NOT EXISTS supplier_name   VARCHAR(150) DEFAULT NULL AFTER addons,
  ADD COLUMN IF NOT EXISTS supplier_cost   DECIMAL(10,2) DEFAULT NULL AFTER supplier_name,
  ADD COLUMN IF NOT EXISTS selling_price   DECIMAL(10,2) DEFAULT NULL AFTER supplier_cost,
  ADD COLUMN IF NOT EXISTS invoice_number  VARCHAR(80)  DEFAULT NULL AFTER selling_price,
  ADD COLUMN IF NOT EXISTS service_datetime DATETIME DEFAULT NULL AFTER flight_time,
  ADD COLUMN IF NOT EXISTS voucher_file    VARCHAR(255) DEFAULT NULL AFTER invoice_number,
  ADD COLUMN IF NOT EXISTS voucher_generated_at DATETIME DEFAULT NULL AFTER voucher_file;

ALTER TABLE bookings
  MODIFY COLUMN status ENUM('Pending','In Queue','Confirmed','Paid','Completed','Cancelled') DEFAULT 'Pending';

-- Email template storage
CREATE TABLE IF NOT EXISTS email_templates (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(80) UNIQUE NOT NULL,
  label        VARCHAR(120),
  subject      VARCHAR(255),
  header_note  TEXT,
  footer_note  TEXT,
  custom_note  TEXT,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default client confirmation template
INSERT IGNORE INTO email_templates(slug,label,subject,header_note,footer_note,custom_note) VALUES
('client_confirmation',
 'Client Booking Confirmation',
 'Booking Received — {ref} | Aero Greet India',
 'Thank you for booking with Aero Greet India. We have received your request and will confirm within 2 hours via Email and WhatsApp.',
 'For urgent queries call +91 95368 96071 or WhatsApp us.',
 '');

-- Site media / image manager
CREATE TABLE IF NOT EXISTS site_media (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(100) UNIQUE NOT NULL,
  label        VARCHAR(150),
  url          TEXT,
  section      VARCHAR(80) DEFAULT 'general',
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default entries
INSERT IGNORE INTO site_media(slug,label,section,url) VALUES
('logo_url','Site Logo URL','branding',''),
('hero_1','Hero Slide 1','hero','https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1920&q=85'),
('hero_2','Hero Slide 2','hero','https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=1920&q=85'),
('hero_3','Hero Slide 3','hero','https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=1920&q=85'),
('hero_4','Hero Slide 4','hero','https://images.unsplash.com/photo-1483450388369-9ed95738483c?w=1920&q=85'),
('hero_5','Hero Slide 5','hero','https://images.unsplash.com/photo-1569154941061-e231b4725ef1?w=1920&q=85'),
('about_banner','About Page Banner','pages',''),
('contact_banner','Contact Page Banner','pages','');

-- Razorpay / payment settings keys in settings table
INSERT IGNORE INTO settings(k,v) VALUES
('razorpay_enabled','0'),
('razorpay_key_id',''),
('razorpay_key_secret',''),
('razorpay_currency','INR');

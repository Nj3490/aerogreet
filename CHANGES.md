# Aero Greet India — v2 Changes & Setup Guide

## What Was Fixed

### 1. Header — Login Icon (replaces "My Bookings" button)
- Desktop nav now shows a **user icon + "Login"** button (right side) linking to `/dashboard/`
- Mobile burger menu shows **"Login / Track Booking"** as the first item
- Footer Company column now includes **"Login / Track Booking"** link
- Updated across ALL 100+ pages automatically

### 2. Contact Page — Email Fix
- Contact form now reads SMTP settings from the **admin settings panel** (database), not just config.php
- Go to **Admin → Settings** and enter your correct SMTP credentials
- Sends to `admin_to` recipients stored in settings (default: `sales@aerogreetindia.com,admin@travelblooper.com`)

### 3. Transit Booking Form — Complete Fix
- Transit form now shows **two sections**:
  - ✈ **Arriving Flight Details** (flight no., from city, arrival date, arrival time)
  - 🔄 **Connecting / Departing Flight Details** (flight no., to city, departure date, departure time)
- Fields appear **only when "Transit Meet & Greet"** is selected
- Date/time inputs now display correctly with `color-scheme: dark`

### 4. Client Confirmation Email — Beautiful Template
- Client now receives a **fully formatted HTML email** with:
  - Aero Greet India logo/header
  - Booking reference badge
  - Full booking details table (all fields)
  - Transit flight details section (if applicable)
  - "What happens next" steps
  - Contact details (phone, WhatsApp, email)
  - Company footer

### 5. Email Template Management from Admin
- New **Admin → Email Templates** section
- Edit the client confirmation email's:
  - Subject line (supports `{ref}` placeholder)
  - Opening message / header text
  - Custom note / special instructions
  - Footer note
- Changes take effect immediately on next booking

### 6. Consistent Header & Footer
- All pages (main, airport pages, blog, privacy, terms, refund) now have the same nav and Login button
- 109 pages updated via automated script

### 7. Dashboard — Booking Tracking Fixed
- Track by Ref + Email works without login
- Logged-in users see all their bookings
- Bookings linked automatically by email (no registration required to view bookings)
- Added "← Back to Site" link in dashboard nav

### 8. Admin Panel — Fully Rebuilt
- **Status filters**: filter by Pending/Confirmed/Paid/Completed/Cancelled (status tabs + dropdown)
- **Airport filter**: filter by airport code
- **Service filter**: Arrival / Departure / Transit
- **Flight type filter**: Domestic / International
- **Invoice number**: shown in bookings table, editable in booking modal
- **Customer data**: now auto-saved from every booking form submission (for marketing)
- Customers panel now includes phone search and CSV export

### 9. Admin Booking — Supplier & Financial Details
In the booking edit modal:
- **Supplier Name**: who is handling the greeter service
- **Supplier Cost (₹)**: what you pay the supplier
- **Selling Price (₹)**: what the client pays
- **Profit/Loss**: calculated automatically and shown in the table
- **Invoice Number**: link the booking to an invoice

### 10. Image Manager (Admin → Image Manager)
- Manage all website images from admin panel
- Update **Hero slide images** (5 slides)
- Update **Site Logo URL**
- Update **page banners** (About, Contact)
- Add custom entries with any slug/label
- Filter by section (Branding, Hero, Pages, General)
- Live preview of image URLs

### 11. Razorpay Payment Gateway (Admin → Razorpay / Payment)
- **Toggle ON/OFF** from admin dashboard
- Enter **Razorpay Key ID** and **Key Secret**
- When **disabled** (default): only sends emails, no payment
- When **enabled**: booking form opens Razorpay checkout, booking confirmed only after payment, status auto-set to "Paid"

---

## Database Upgrade (Existing Installs)

If upgrading from v1, run these SQL commands in phpMyAdmin:

```sql
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS supplier_name VARCHAR(150) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS supplier_cost DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS selling_price DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(80) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS email_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) UNIQUE NOT NULL,
  label VARCHAR(120),
  subject VARCHAR(255),
  header_note TEXT,
  footer_note TEXT,
  custom_note TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO email_templates(slug,label,subject,header_note,footer_note,custom_note) VALUES
('client_confirmation','Client Booking Confirmation','Booking Received — {ref} | Aero Greet India',
'Thank you for booking with Aero Greet India. We have received your request and are checking the availability of meet & greet slots. We will contact you shortly via Email and WhatsApp to confirm your booking.',
'For urgent queries call +91 95368 96071 or WhatsApp us.','');

CREATE TABLE IF NOT EXISTS site_media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) UNIQUE NOT NULL,
  label VARCHAR(150),
  url TEXT,
  section VARCHAR(80) DEFAULT 'general',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO site_media(slug,label,section,url) VALUES
('logo_url','Site Logo URL','branding',''),
('hero_1','Hero Slide 1','hero','https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1920&q=85'),
('hero_2','Hero Slide 2','hero',''),('hero_3','Hero Slide 3','hero',''),
('hero_4','Hero Slide 4','hero',''),('hero_5','Hero Slide 5','hero',''),
('about_banner','About Page Banner','pages',''),('contact_banner','Contact Page Banner','pages','');

INSERT IGNORE INTO settings(k,v) VALUES
('razorpay_enabled','0'),('razorpay_key_id',''),('razorpay_key_secret',''),('razorpay_currency','INR');
```

**Or simply re-run `install.php`** — it now handles all v2 upgrades safely.

---

## First Steps After Upload

1. Run `install.php` to set up database and admin account
2. Go to **Admin → Settings** → enter your SMTP details
3. Go to **Admin → Email Templates** → review/update client confirmation email text
4. Go to **Admin → Image Manager** → verify all hero images are loading
5. Test a booking from the homepage to verify emails arrive
6. Delete `install.php` when done

SHINE GUARD HULO - IoT SMART STREETLIGHT SYSTEM
===============================================

Installation Instructions:
--------------------------

1. DATABASE SETUP:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database: "Hulo"
   - Import file: database_schema.sql
   - Default admin credentials:
     Username: admin
     Password: admin123

2. FILE PLACEMENT:
   - Copy all files to: C:\xampp\htdocs\shine_guard_hulo\
   - Or your web server directory

3. START SERVICES:
   - Start Apache
   - Start MySQL

4. ACCESS SYSTEM:
   - Login: http://localhost/shine_guard_hulo/login.php
   - Dashboard: http://localhost/shine_guard_hulo/dashboard.php

FILES INCLUDED:
--------------
✓ dbconnect.php - Database connection
✓ database_schema.sql - Complete database schema
✓ login.php - Modern login page
✓ login_process.php - Authentication handler
✓ dashboard.php - Main dashboard (overview)
✓ streetlights.php - Streetlight management
✓ cctv.php - CCTV monitoring
✓ logout.php - Logout handler

FEATURES IMPLEMENTED:
--------------------
✓ Secure authentication with password hashing
✓ Role-based access control (Admin, Operator, Maintenance)
✓ Real-time streetlight monitoring (32 nodes)
✓ Bulk control (Turn all ON/OFF)
✓ Schedule presets (time-based automation)
✓ CCTV integration with NVR storage
✓ Predictive maintenance alerts
✓ Activity logging
✓ Energy consumption tracking
✓ Mobile responsive design

DATABASE TABLES:
---------------
✓ users - Authentication & roles
✓ streetlights - 32 nodes with locations
✓ sensor_data - Real-time readings
✓ alerts - Predictive maintenance
✓ maintenance_logs - Work orders
✓ cctv_cameras - 4 cameras configured
✓ cctv_footage - NVR storage metadata
✓ schedule_presets - Time-based control
✓ system_config - Thresholds
✓ activity_logs - Audit trail

SUGGESTIONS & IMPROVEMENTS:
---------------------------
Based on your research paper:

1. ARDUINO/ESP32 INTEGRATION:
   - Create API endpoint: api/sensor_data.php
   - ESP32 sends data via HTTP POST
   - JSON format: {node_id, lux, current, voltage, temp}

2. FIREBASE BACKUP:
   - Install Firebase PHP SDK
   - Auto-backup sensor_data daily
   - Upload CCTV footage metadata

3. EMAIL NOTIFICATIONS:
   - Configure PHPMailer
   - Send alerts to admins/operators
   - Alert triggers: fault, predictive, high temp

4. MOBILE APP:
   - Create REST API endpoints
   - JWT authentication
   - Push notifications

5. ADVANCED ANALYTICS:
   - Energy consumption graphs (Chart.js)
   - Failure prediction trends
   - Cost savings calculator

6. GEOLOCATION MAP:
   - Google Maps integration
   - Show all streetlights on map
   - Click for details

7. REPORTING:
   - PDF export (TCPDF/FPDF)
   - Weekly/Monthly automated reports
   - Email delivery

NEXT STEPS:
-----------
The files being generated will include complete implementations for all pages.
Check the individual PHP files for detailed functionality.

Support: Developed based on Shine Guard Hulo Capstone Project

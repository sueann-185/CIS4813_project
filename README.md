# DonationHub - Real-time Donation Matching Platform

# Overview

DonationHub is a comprehensive web application that facilitates the matching of physical item donations with those in need. The platform features:

- Secure user authentication with role-based access control
- Real-time donation and request matching system
- Admin panel with content moderation and analytics
- Session management with automatic timeout
- Responsive UI with Bootstrap 5
- Data visualization with Chart.js
- Comprehensive error handling and input validation

# Core Features

1. User Management
- Registration with profile creation
- Login/logout functionality
- Role-based access (user/admin)
- Profile management with image upload
- Account activation/deactivation

2. Donation System
- Create donation listings with images
- Browse available donations
- Categorization system for donations
- Status tracking (available/pending/matched)

3. Request System
- Create item requests
- Browse open requests
- Status tracking (pending/matched)

4. Matching Engine
- Requesters can match donations to their needs
- Donors can confirm/reject matches
- Admin approval for final matching
- Complete match history tracking

5. Admin Panel
- User management
- Content moderation (donations/requests)
- Category management
- Comprehensive analytics dashboard
- Login activity tracking

6. Security Features
- Password hashing
- Session management with timeout
- CSRF protection
- Input validation and sanitization
- Role-based access control

# How the System Works

1. User Flow

## Guest View:
- Browse donations and requests
- Register as new user

## Registered User:
- Create donation listings
- Submit item requests
- Match donations to requests
- Manage profile

## Admin:
- Moderate content
- Manage users
- View analytics
- Finalize matches

# Deployment

1. Hosting Platform: InfinityFree

2. Deployment Steps:
- Created account on InfinityFree.net
- Set up MySQL database through control panel
- Uploaded all PHP files to server via File Manager
- Executed init.sql through phpMyAdmin
- Configured database credentials in config.php

3. Test Credentials:
 Username          Password
 admin@test.com    123

4. Deployment URL:
https://sueann1085.42web.io/project/


Remarks commands:
sudo chown -R www-data:www-data /opt/lampp/htdocs/project
sudo chmod -R 777 /opt/lampp/htdocs/project
sudo /opt/lampp/lampp start

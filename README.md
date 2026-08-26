<p align="center">
  <img src="https://img.shields.io/badge/PHP_8+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8+" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/Bootstrap_5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5" />
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript" />
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5" />
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3" />
  <img src="https://img.shields.io/badge/Leaflet.js-199900?style=for-the-badge&logo=leaflet&logoColor=white" alt="Leaflet.js" />
  <img src="https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chart.js&logoColor=white" alt="Chart.js" />
</p>

# 🍽️ FoodShare — Food Waste Sharing Platform

A web platform designed to **reduce food waste** by connecting individuals, restaurants, and organizations with surplus food to people in need. FoodShare facilitates food donations, surplus food sharing, and community-driven food rescue — directly addressing **food insecurity** and **environmental sustainability**.

> 🌍 Aligned with **UN Sustainable Development Goals**: SDG 2 (Zero Hunger) & SDG 12 (Responsible Consumption and Production)

> Developed for **Ankara Bilim University** — Web Design course.

---

## ✨ Features

### 🔐 Authentication & Multi-Role System
- **Session-based PHP authentication** with secure login/register
- **Three user roles:** Donor (individual/restaurant/organization), Recipient, Admin
- Profile management with avatar and preferences

### 🥗 Food Listing & Donation
- Donors list surplus food with details: name, quantity, category, expiration date, pickup location
- **Photo upload** for food items
- Real-time availability status tracking
- Category-based organization (Fruits, Vegetables, Bakery, Dairy, Meals, etc.)

### 🗺️ Interactive Map (Leaflet.js + OpenStreetMap)
- **Map view** showing nearby food donation points and available pickups
- Location-based filtering and search
- Marker clustering for dense areas
- Route calculation to pickup points

### 🤖 Smart Matching Algorithm
- Matches available donations with nearby recipients based on:
  - Location proximity
  - Dietary preferences
  - Food categories
- Automated notification on new matches

### 📦 Reservation & Pickup System
- Browse, reserve, and schedule pickup for available food items
- **Status tracking pipeline:** Available → Reserved → Picked Up → Completed
- Pickup confirmation with timestamps

### 📊 Analytics Dashboard (Chart.js)
- **Total donations** count and trends
- **Food saved** (kg) metrics
- **CO₂ emissions prevented** calculations
- Active user statistics
- Pie, bar, and line charts for donation trends and category breakdowns

### 🌱 Environmental Impact Tracker
- Real-time calculation of **CO₂ savings**, **water savings**, and **landfill waste reduction** per donation
- Cumulative platform-wide impact statistics
- Individual donor impact cards

### ⭐ Rating & Review System
- Post-transaction ratings for both donors and recipients
- Trust score building through community feedback

### 🔔 Notification System
- In-app notifications for new listings, reservation confirmations, and pickup reminders
- Read/unread status tracking

### 🛠️ Admin Panel
- User management and moderation
- Donation review and approval
- Platform-wide analytics and reporting
- Reported listings management

### 📱 Responsive Design
- Mobile-friendly layout with **Bootstrap 5** grid system
- Touch-friendly interface for on-the-go food sharing

---

## 🛠️ Tech Stack

| Category | Technologies |
|----------|-------------|
| **Backend** | PHP 8+ (vanilla) |
| **Frontend** | HTML5 · CSS3 · JavaScript · Bootstrap 5 |
| **Database** | MySQL |
| **Server** | Apache (XAMPP) |
| **Maps** | Leaflet.js + OpenStreetMap |
| **Charts** | Chart.js |
| **Icons** | Font Awesome 6 |
| **Typography** | Google Fonts (Poppins) |

---

## 🚀 Getting Started

### Prerequisites

| Tool | Details |
|------|---------|
| XAMPP | Apache + MySQL + PHP 8+ (or any equivalent LAMP/WAMP stack) |

### 1. Clone the repository

```bash
git clone <repo-url>
```

### 2. Set up the web server

Copy the project folder to your XAMPP `htdocs` directory (or your web server root):

```bash
cp -r FoodShareV6 /Applications/XAMPP/htdocs/foodshare
```

### 3. Import the database

1. Start **MySQL** from XAMPP Control Panel
2. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
3. Create a new database named `foodshare`
4. Import `database/foodshare.sql`

### 4. Configure database connection

Edit `includes/config.php` with your database credentials:

```php
<?php
$host = 'localhost';
$dbname = 'foodshare';
$username = 'root';
$password = '';
?>
```

### 5. Start the application

1. Start **Apache** and **MySQL** from XAMPP Control Panel
2. Navigate to `http://localhost/foodshare`

---

## 📁 Project Structure

```
FoodShareV6/
├── index.php                  # Landing page / home
├── login.php                  # Login page
├── register.php               # Registration with role selection
├── dashboard.php              # User dashboard
├── donate.php                 # Create food donation listing
├── browse.php                 # Browse available food items
├── map.php                    # Interactive map view (Leaflet.js)
├── profile.php                # User profile management
├── admin/
│   ├── index.php              # Admin dashboard
│   ├── users.php              # User management
│   ├── donations.php          # Donation moderation
│   └── analytics.php          # Platform analytics (Chart.js)
├── api/
│   ├── listings.php           # Food listing CRUD API
│   ├── reservations.php       # Reservation endpoints
│   ├── notifications.php      # Notification endpoints
│   └── ratings.php            # Rating/review API
├── includes/
│   ├── config.php             # Database connection config
│   ├── auth.php               # Authentication helpers
│   ├── functions.php          # Utility functions
│   ├── header.php             # Common header
│   └── footer.php             # Common footer
├── assets/
│   ├── css/                   # Custom stylesheets
│   ├── js/                    # Custom JavaScript
│   └── images/                # Static images
├── database/
│   └── foodshare.sql          # MySQL schema + seed data
└── uploads/                   # User-uploaded food images
```

---


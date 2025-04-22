
---

# Evenera – Event Planning Checklist Web App

**Evenera** is a full-featured event planning checklist platform designed to streamline the planning and management of any kind of event—be it personal celebrations, corporate gatherings, launch parties, or social meetups. It offers powerful tools like task lists, budget tracking, and guest management in an intuitive, mobile-friendly interface.

---

## 🚀 Features

- **Customizable Event Templates**
  - Personal Celebrations (Birthdays, Anniversaries)
  - Corporate Events
  - Launch Parties
  - Social Gatherings
  - Travel & Trip Planning

- **Core Modules**
  - ✅ Task Management  
  - 💰 Budget Tracking  
  - 👥 Guest Management  
  - 🛍️ Shopping Lists  
  - 🏷️ Vendor Coordination  
  - 📅 Timeline Planning

---

## 🛠️ Technology Stack

### Frontend
- HTML5  
- Tailwind CSS (Utility-first CSS)  
- JavaScript (ES6+)  
- jQuery  
- GSAP + ScrollTrigger (for smooth animations)  
- Font Awesome (icon library)

### Backend
- PHP  
- MySQL  
- PDO (for secure database interaction)  
- RESTful APIs

---

## 📁 Project Structure

```
Evenera/
├── api/                    # API endpoints for backend interaction
│   ├── auth.php
│   ├── events.php
│   ├── checklist.php
│   └── save-cookie-consent.php
├── assets/                 # Static assets like images
│   └── images/
├── database/               # Database schema & seed data
│   ├── schema.sql
│   ├── migrations.sql
│   └── cookie_consents.sql
├── includes/               # Reusable PHP components
│   ├── cookie_handler.php
│   ├── db_connection.php
│   └── session_handler.php
├── js/                     # Custom JavaScript files
│   ├── cookie-consent.js
│   ├── dashboard.js
│   └── events.js
├── styles.css              # Main stylesheet (Tailwind-based)
├── index.html              # Landing/Home page
└── README.md               # Project documentation
```

---

## ⚙️ Setup Instructions

### 1. Prerequisites
- XAMPP / MAMP / WAMP or equivalent local server
- PHP 7.4 or newer
- MySQL 5.7 or newer
- A modern web browser

### 2. Database Initialization

```sql
-- Create database
CREATE DATABASE evenera;

-- Import schema
source database/schema.sql;

-- Import cookie consent structure
source database/cookie_consents.sql;
```

### 3. Configuration
- Update database credentials in `includes/db_connection.php`
- Configure sessions in `includes/session_handler.php`
- Modify cookie behavior via `js/cookie-consent.js`

### 4. Run the App
- Launch your local server
- Go to `http://localhost/Evenera/` in your browser
- Start planning and managing events with ease!

---

## 🍪 Cookie Consent System

Evenera includes a flexible cookie consent module:

- Consent banner shown to new users  
- Options to select cookie types (essential, analytics, marketing)  
- Stores preferences in the browser *and* database  
- Applies to both guest and authenticated users  
- Enforces secure cookie handling (SameSite + HttpOnly)

### Cookie Categories
- **Essential Cookies** – Required for core functionality  
- **Analytics Cookies** – Help track usage metrics  
- **Marketing Cookies** – For personalized recommendations

---

## 🔐 Security Highlights

- Uses `HttpOnly` and `SameSite` flags on cookies  
- All database queries use **PDO + Prepared Statements**  
- Input validation & sanitization across all forms  
- Session-based login handling  
- Built-in error logging and fallback handling

---

## 🤝 Contributing

We welcome contributions!

1. Fork the repo  
2. Create a new branch: `git checkout -b feature/AmazingFeature`  
3. Make changes & commit: `git commit -m 'Add some AmazingFeature'`  
4. Push to GitHub: `git push origin feature/AmazingFeature`  
5. Open a Pull Request!

---

## 📧 Support

For questions or help:
- 📩 Email: atishaysodhiya5845@gmail.com  
- 🌐 Visit: [evenera5845.fwh.is] 

---

## 🙏 Acknowledgments

- [Tailwind CSS](https://tailwindcss.com/) for flexible utility classes  
- [GSAP](https://greensock.com/gsap/) for next-level animations  
- [Font Awesome](https://fontawesome.com/) for icons  
- [@YourTeacherTag] for mentorship and guidance  
- All contributors from **ATY Designs** for design & development expertise  

---

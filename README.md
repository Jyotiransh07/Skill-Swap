# SkillSwap Campus

**TAGLINE:** *"Learn. Teach. Connect."*

SkillSwap Campus is a full-featured, peer-to-peer student skill exchange web application built using native **PHP 8+**, **MySQL**, **Bootstrap 5**, **Bootstrap Icons**, and **Chart.js**, specifically engineered to run locally on Windows using **WAMP Server** (`C:\wamp64\www\skillswap\`).

---

## 🌟 Project Overview & Concept

In a college environment, students possess complementary skills:
- **Student A (Rahul):** Teaches *Python* & *C++*, wants to learn *UI/UX Design* & *Figma*.
- **Student B (Priya):** Teaches *UI/UX Design* & *Figma*, wants to learn *Python*.

**SkillSwap Campus** detects this 100% two-way exchange match and allows students to exchange knowledge without any monetary transactions.

---

## 🚀 WAMP Installation & Setup Guide

### Environment Requirements
- **OS:** Windows 10 / 11
- **Stack:** WAMP (Apache 2.4+, PHP 8.0+, MySQL 5.7+ / 8.0+)
- **Access URL:** `http://localhost/skillswap/`

### Step-by-Step Installation

1. **Start WAMP Server:**
   Ensure WAMP Server is running on Windows (Green WampServer icon in system tray). Apache and MySQL services must be online.

2. **Deploy Project Directory:**
   Copy or link the `SkillSwap` project folder to your WAMP `www` root:
   ```text
   C:\wamp64\www\skillswap\
   ```

3. **Import MySQL Database in phpMyAdmin:**
   - Open your browser and navigate to `http://localhost/phpmyadmin/`.
   - Log in to phpMyAdmin (Default username: `root`, Password: `""` empty).
   - Create a new database named `skillswap` (utf8mb4_unicode_ci).
   - Click on the **Import** tab, select the SQL file located at:
     ```text
     C:\wamp64\www\skillswap\database\skillswap.sql
     ```
   - Click **Execute** / **Go** to import all tables, views, stored procedures, triggers, and seed data.

4. **Verify Database Configuration:**
   Inspect `config/database.php` if your local MySQL configuration uses a custom port or password:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'skillswap');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

5. **Launch Application:**
   Open your browser and visit:
   ```text
   http://localhost/skillswap/
   ```

---

## 🔑 Test Credentials for Demo & Viva

| Account Role | Email Address | Password | Description |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@skillswap.edu` | `Student@123` | Full Admin Panel access & analytics |
| **Student 1 (Rahul)** | `rahul@skillswap.edu` | `Student@123` | Teaches Python/C++, Wants UI/UX |
| **Student 2 (Priya)** | `priya@skillswap.edu` | `Student@123` | Teaches UI/UX/Figma, Wants Python |
| **Student 3 (Aman)** | `aman@skillswap.edu` | `Student@123` | Teaches Java/Web Dev, Wants Figma |
| **Student 4 (Sneha)** | `sneha@skillswap.edu` | `Student@123` | Teaches Robotics/Arduino, Wants C++ |

---

## 🗄️ Database Architecture & DBMS Viva Features

The database demonstrates advanced RDBMS design concepts required for college viva defense:

### 1. Core Relational Tables
1. `users`: Student & Admin accounts with hashed passwords (`password_hash`), reputation score, status.
2. `skills`: Master skill catalog with categories (Programming, Design, Hardware, Creative).
3. `user_skills`: Many-to-Many normalized pairing between users and skills (`TEACH` vs `LEARN`, proficiency levels).
4. `learning_requests`: Exchange proposal states (`PENDING`, `ACCEPTED`, `REJECTED`, `CANCELLED`, `COMPLETED`).
5. `sessions`: Scheduled learning sessions with date, time, mode (`ONLINE`/`OFFLINE`), meet links, locations.
6. `reviews`: 5-star ratings and peer feedback comments.
7. `notifications`: In-app notification center for request updates and session reminders.
8. `reports`: Safety moderation reports submitted to administrators.

### 2. Constraints & Indexes
- **PRIMARY KEY & FOREIGN KEYs** with `ON DELETE CASCADE`.
- **UNIQUE Constraints:** `uk_user_skill_type` (prevents duplicate skill mappings) and `uk_session_reviewer` (prevents double reviews).
- **CHECK Constraints:** `chk_reputation_range` (0 to 100), `chk_rating_range` (1 to 5 stars), `chk_sender_receiver_diff`.
- **INDEXES:** Performance indexes on foreign keys and search fields.

### 3. SQL Views
- `vw_student_reputation`: Aggregates average rating, total reviews, completed sessions, and total exchanges per student.
- `vw_skill_demand_supply`: Aggregates count of students teaching vs wanting to learn each skill.

### 4. Stored Procedures
- `sp_calculate_reputation(p_user_id)`: Calculates reputation score using weighted formula:
  $$\text{Reputation} = \left(\frac{\text{Avg Rating}}{5} \times 40\right) + \left(\frac{\text{Completed Sessions}}{10} \times 40\right) + \left(\frac{\text{Exchanges}}{5} \times 20\right)$$
- `sp_get_smart_matches(p_user_id)`: Pre-computes complementary candidates.

### 5. Triggers
- `trg_after_review_insert`: Automatically recalculates student reputation score when a new review is inserted.
- `trg_after_session_completed`: Automatically updates reputation scores when session status changes to `COMPLETED`.

---

## ⚡ Smart Matching Engine Algorithm

The smart compatibility algorithm evaluates pairs on a 0–100 scale:
- **Direct Skill Match (+50 pts):** Peer teaches what you want to learn.
- **Reverse Skill Match (+30 pts):** You teach what peer wants to learn (80 pts = 2-way match!).
- **Department Match (+10 pts):** Both students belong to the same department.
- **Year Match (+5 pts):** Both students belong to the same academic year.
- **Reputation Bonus (+5 pts):** Teacher reputation score $\ge 80/100$.

---

## 🔒 Security Practices

- **PDO Prepared Statements:** 100% parameter bound queries preventing SQL Injection.
- **Password Hashing:** BCRYPT hashing via `password_hash()` and `password_verify()`.
- **CSRF Protection:** Token verification on all state-modifying POST forms.
- **Input Sanitization & Escaping:** `htmlspecialchars` output escaping preventing XSS.
- **File Upload Protection:** Strict extension whitelisting (`jpg`, `png`, `webp`) and size limits (2MB).

---

## 📁 Architecture & File Structure

```text
skillswap/
├── index.php                     (Landing Page - Hero, How It Works, Stats, CTA)
├── config/
│   └── database.php              (PDO Database Connection Singleton)
├── auth/
│   ├── login.php                 (Login authentication)
│   ├── register.php              (Student registration)
│   ├── logout.php                (Session termination)
│   └── forgot-password.php       (Password reset simulation)
├── student/
│   ├── dashboard.php             (Student Hub & Stats)
│   ├── profile.php               (Student profile view)
│   ├── edit-profile.php          (Edit bio & avatar upload)
│   ├── skills.php                (My Skills management)
│   ├── find-skills.php           (Explore & search skills)
│   ├── matches.php               (Smart Skill Matches)
│   ├── requests.php              (Exchange proposals)
│   ├── sessions.php              (Schedule & track sessions)
│   ├── reviews.php               (Peer ratings & reviews)
│   ├── notifications.php         (Notifications center)
│   └── reports.php               (User safety reporting)
├── admin/
│   ├── dashboard.php             (Admin KPI overview)
│   ├── users.php                 (User management & moderation)
│   ├── skills.php                (Master skills catalog CRUD)
│   ├── requests.php              (Global requests monitor)
│   ├── sessions.php              (Global sessions monitor)
│   ├── reviews.php               (Global reviews monitor)
│   ├── reports.php               (Review safety reports)
│   └── analytics.php             (Chart.js analytics dashboard)
├── includes/
│   ├── header.php                (HTML head & CSS assets)
│   ├── footer.php                (Footer & JS assets)
│   ├── navbar.php                (Top header navigation)
│   ├── sidebar.php               (Responsive panel sidebar)
│   ├── auth-check.php            (Student authorization middleware)
│   ├── admin-check.php           (Admin authorization middleware)
│   └── functions.php             (Matching logic & helpers)
├── api/
│   ├── skills.php                (AJAX skill search)
│   ├── matches.php               (AJAX matching calculation)
│   ├── requests.php              (AJAX request actions)
│   ├── notifications.php         (AJAX notification marker)
│   └── sessions.php              (AJAX session completion)
├── assets/
│   ├── css/ (style.css, dashboard.css)
│   └── js/  (script.js, dashboard.js, validation.js)
├── uploads/
│   └── profiles/                 (Profile avatar uploads)
├── database/
│   └── skillswap.sql             (MySQL dump for phpMyAdmin)
└── README.md
```

---

## 🎓 License & Credits
Built for College Project Submission. Designed & Developed for local execution on WAMP stack.

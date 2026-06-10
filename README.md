# 🏅 Sport Event

A comprehensive web-based school sports day (กีฬาสี) management system built with PHP — covering student registration, team (color) management, match scheduling, score recording by referees, athletics (track & field) events, and full PDF report generation.

> Currently in production at Sukhon School. Forked by 2 other schools.

---

## ✨ Features

### 👨‍💼 Admin
- Manage **academic years** and sport event settings
- Manage **sports**, **categories**, and **matches** (team vs. team scheduling)
- Manage **athletics events** (กรีฑา) with separate scheduling and export
- Manage **student registration** (`regis`) — assign students to color teams
- Manage **players** — register individual students per sport/category
- Manage **users** (admin/staff/referee accounts)
- Assign **referees** to matches
- Upload team logos and event cover image
- View **summary** dashboard (scores, medals, standings per color team)
- View **activity logs**
- **Backup and restore** database
- Generate multiple **PDF reports**:
  - Match results booklet
  - Athletics schedule
  - Athletics results export
  - Registration export
  - Match results report

### 👨‍🏫 Staff
- View student registrations and color team rosters
- Register students and update player data
- Access own team overview

### 🟥🟨🟦 Referee
- Login with referee account
- View assigned matches and player lists
- **Record scores** in real-time via `referee_api.php` (JSON API)

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP |
| Styling | Tailwind CSS |
| PDF Export | dompdf 3.1 |
| Config | phpdotenv 5.6 |
| Database | MySQL |
| Server | Apache (XAMPP) |

---

## 📁 Project Structure

```
sport-event/
├── public/
│   ├── index.php                        # Admin dashboard
│   ├── login.php / logout.php           # Admin auth
│   ├── students.php                     # Student management
│   ├── sports.php                       # Sports management
│   ├── categories.php                   # Category management
│   ├── matches.php                      # Match scheduling
│   ├── athletics.php                    # Athletics events
│   ├── player.php                       # Player registration
│   ├── regis.php                        # Color team registration
│   ├── referee.php / referee_api.php    # Referee management & score API
│   ├── summary.php                      # Score summary & standings
│   ├── users.php                        # User management
│   ├── years.php                        # Academic year settings
│   ├── logs.php                         # Activity logs
│   ├── backup.php                       # DB backup/restore
│   ├── upload_logo.php                  # Team logo upload
│   ├── upload_cover.php                 # Event cover upload
│   ├── reports.php                      # Match results report
│   ├── reports_booklet.php              # Results booklet (PDF)
│   ├── reports_athletics.php            # Athletics results (PDF)
│   ├── reports_athletics_schedule.php   # Athletics schedule (PDF)
│   ├── reports_athletics_export.php     # Athletics export
│   ├── reports_matches.php              # Match report (PDF)
│   ├── reports_export_registration.php  # Registration export
│   └── staff/
│       ├── index.php                    # Staff dashboard
│       ├── login.php / logout.php       # Staff auth
│       └── register.php
│   └── referee/
│       ├── index.php                    # Referee dashboard
│       ├── login.php / logout.php       # Referee auth
│       └── players.php                 # Match player list
├── includes/                           # Shared helpers & DB functions
├── config/                             # Database & app configuration
├── lib/                                # Custom libraries
├── vendor/                             # Composer dependencies
├── .htaccess                           # Clean URL routing (3 role prefixes)
└── composer.json
```

---

## 🔐 Role-Based Access

| Role | Access |
|------|--------|
| **Admin** | Full access — setup, registration, matches, reports, backup |
| **Staff** | Student and team roster management |
| **Referee** | Score recording for assigned matches only |

---

## 🚀 Getting Started

### Requirements

- PHP 8.0+
- MySQL 5.7+
- Apache with `mod_rewrite` enabled (or XAMPP)
- Composer

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/thana-boon/sport-event.git
   cd sport-event
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy and configure environment:
   ```bash
   cp .env.example .env
   ```

   Edit `.env`:
   ```
   DB_HOST=localhost
   DB_NAME=sport_event
   DB_USER=root
   DB_PASS=
   BASE_URL=http://localhost/sport-event
   ```

4. Import the database schema via phpMyAdmin or MySQL CLI.

5. Update `RewriteBase` in `.htaccess` to match your install path (default: `/sport-event/`).

6. Open `http://localhost/sport-event` in your browser.

---

## 📄 License

This project is for educational and internal school use.

---

## 👤 Author

**thana-boon** — Teacher & Developer at Sukhon School  
GitHub: [@thana-boon](https://github.com/thana-boon)

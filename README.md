# EduTrack Pro — Faculty College Log & Attendance Tracker (XAMPP Web Application)

A modern, full-stack college logbook and student attendance tracker built for college professors and faculty members. Designed to run seamlessly on **XAMPP (Apache, PHP 8.x, MySQL / MariaDB)**.

---

## 🌟 Key Features

1. **User Registration & Secure Authentication**
   - Faculty members can **register by choosing their own username and password**, full name, department, and institutional email.
   - Passwords are encrypted using industry-standard `password_hash()` (Bcrypt).
   - Multi-faculty workspace isolation: Each registered user has their own private campus timings, semester student rosters, and activity logs.
   - 1-Click Faculty Switcher: Easily switch between registered faculty profiles on shared department terminals.

2. **100% SQL Database Storage**
   - All records are saved in MySQL database: `college_log_db`.
   - **Zero-Config Auto-Migration**: When accessed for the first time, the application automatically creates the database and all necessary tables (`users`, `daily_timings`, `activity_logs`, `semester_rosters`) and seeds default demo data.

3. **Daily Campus Arrival & Departure Log**
   - Punch In / Punch Out buttons with real-time timestamp capture.
   - Automatic calculation of total campus duration (e.g. `7h 30m on campus`).
   - Syncs arrival and departure times across all sessions conducted on that date.

4. **Class & Exam Invigilation Duty Entry (Tab 1)**
   - Duty Categories: **Lecture Class**, **Lab Session**, **Exam Duty**, **Tutorial / Extra**.
   - Semester selector (Semester 1 to Semester 8).
   - Time-slot start and end time with live duration indicator.
   - **Strict Schedule Overlap Engine**: Automatically detects date & time conflicts against existing logs and offers an **Auto-Fix** button suggesting the next free 1-hour time window.
   - Course/Subject name, Hall/Room allocation, syllabus topics, and invigilation remarks.
   - Interactive student attendance roster cards with 1-click **Present (P) / Absent (A)** toggles, **Mark All** shortcuts, and live percentage counters.
   - Manual examinee headcount mode for large exam halls.

5. **Date-Range Reports & Export Center (Tab 2)**
   - Filter by: Custom Date Range (From - To), Single Date, or All Dates.
   - Quick date presets: **Today**, **Yesterday**, **This Week**, **This Month**.
   - Filter by semester and activity type.
   - Executive metric cards: Total Campus Hours, Sessions Conducted, Total Student Attendances, Classes Taught.
   - **Official PDF Export** with college header, summary blocks, and table styling via `jsPDF`.
   - **Multi-Sheet Excel (.xlsx) Export** with full student roll-call lists via `SheetJS`.
   - Session Attendance Sheet PDF for individual classes with printable signature line.

6. **Semester Student Roster Manager (Tab 3)**
   - Manage enrolled student rosters for Semester 1 through Semester 8.
   - Add new students with Roll Number and Full Name.
   - Delete or update enrolled students.
   - Auto-seeded with default college student lists for instant usability.

---

## 🚀 Quick Start with XAMPP

### Step 1: Start XAMPP
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 2: Deploy to `htdocs`
Place the `college_log` folder inside your XAMPP web root:
```
C:\xampp\htdocs\college_log
```
*(If your XAMPP is installed in `D:\xampp` or `C:\xampp1`, place it in the corresponding `htdocs` folder.)*

### Step 3: Open in Browser
Visit the application in your browser:
```
http://localhost/college_log/
```
The database `college_log_db` and tables will be created automatically on first load!

---

## 🔑 Default Credentials

A pre-configured demo faculty account is ready out of the box:
- **Username:** `alorika`
- **Password:** `password123`
- **Name:** Dr. Alorika Chatterjee
- **Department:** Dept. of Physics, The Heritage College
- **Email:** `alorika.chatterjee@theheritage.ac.in`

### Registering Your Own Account
1. Click **Account / Sign In** (or the top banner link).
2. Select the **Register New Faculty** tab.
3. Enter your preferred **Username**, **Password**, **Full Name**, **Department**, and **Email**.
4. Check or uncheck *Pre-seed sample classes & attendance logs*.
5. Click **Register & Create Faculty Workspace**.
6. You are now logged in with your private workspace and can immediately start logging your college duties!

---

## 📁 Project File Structure

```
college_log/
├── index.php             # Main single-page web app (identical design, Tailwind CSS, Lucide icons)
├── db.php                # PDO database connection & auto-table migration
├── auth.php              # User registration, login, logout, profile update & account list
├── api.php               # RESTful JSON API for timings, activity logs & semester rosters
├── schema.sql            # Database schema definition with table constraints & foreign keys
├── README.md             # Documentation and usage guide
```

---

## 🗄️ SQL Database Schema

- **`users`**: Faculty accounts (ID, username, password_hash, full_name, email, department, timestamps).
- **`daily_timings`**: Campus arrival & departure log per faculty and date (`user_id`, `log_date`, `arrival_time`, `departure_time`).
- **`activity_logs`**: Lecture, lab, and exam duty records (`log_code`, `user_id`, `log_date`, `activity_type`, `semester`, `slot_start`, `slot_end`, `subject_or_exam`, `room_or_hall`, `topic_or_duty`, `student_count`, `present_count`, `absent_count`, `attendance_json`).
- **`semester_rosters`**: Enrolled students per semester per faculty (`user_id`, `semester`, `roll_no`, `student_name`).

---

## 🛠️ API Reference

- `GET  /api.php?action=get_state` — Retrieves active user profile, daily timings, activity logs, and rosters.
- `POST /api.php?action=save_daily_timing` — Saves arrival/departure time for a date.
- `POST /api.php?action=save_log` — Saves a new class or exam duty log.
- `POST /api.php?action=delete_log` — Deletes a log entry.
- `POST /api.php?action=add_roster_student` — Adds a student to a semester roster.
- `POST /api.php?action=remove_roster_student` — Removes a student from a semester roster.
- `POST /api.php?action=reset_demo_data` — Resets sample demo records.
- `POST /auth.php?action=register` — Registers a new faculty account.
- `POST /auth.php?action=login` — Authenticates user credentials.
- `POST /auth.php?action=logout` — Clears active session.
- `POST /auth.php?action=update_profile` — Updates faculty profile details or password.
- `GET  /auth.php?action=list_accounts` — Lists registered faculty for 1-click switching.


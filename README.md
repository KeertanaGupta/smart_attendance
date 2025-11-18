# 🎓 Smart Attendance System  
### *A Real-Time, Two-Factor, Proxy-Proof Attendance Solution for Classrooms*

The **Smart Attendance System** is a full-stack, security-focused web application designed to eliminate proxy attendance in academic settings.  
Traditional methods like sign-in sheets or roll calls are slow, unreliable, and easy to manipulate.  
This system solves that by enforcing **physical presence** using:

✔ A **Dynamic QR Code**, and  
✔ A **Time-Sensitive PIN**  

Both factors change continuously in real-time, making screenshot-based proxy attendance impossible.

This project uses the **LAMP Stack (Linux/XAMPP – Apache – MySQL – PHP)** and demonstrates real-world backend security, database normalization, AJAX-based real-time updates, and session-level protection.

---

# 🌟 Key Features (Resume-Grade Highlights)

Below is the **real impact + tech behind each feature** — perfect for resumes and project evaluations.

## 1️⃣ Two-Factor Attendance (QR + Time-Sensitive PIN)
- Generates a **unique QR code** for each session.
- A **new 4-digit PIN** is generated every 30 seconds via AJAX.
- PIN + QR must be used together → screenshot sharing becomes useless.
- Secure PHP validation using `password_verify()` style logic ensures no tampering.

**Impact:** Forces real physical presence. Eliminates proxy/remote sign-ins.

---

## 2️⃣ Real-Time Live Dashboard (AJAX)
- The professor’s screen automatically updates every **5 seconds**.
- Shows a **live list** of checked-in students.
- Also refreshes the current PIN in real time.

**Impact:** Creates transparency + prevents mass fraud because everyone can see who checked in.

---

## 3️⃣ Browser Lockout (Anti-Proxy Security)
- Once a student submits attendance, the browser session is locked.
- Even using “back” button or reloading cannot resubmit.
- Prevents one student from marking attendance for multiple people.

**Impact:** Completely removes the biggest loophole in digital attendance.

---

## 4️⃣ Dynamic, Expiring PIN (30-second cycle)
- Server auto-generates new PIN every 30 seconds.
- Served asynchronously using AJAX endpoint.

**Impact:** Prevents any kind of screenshot-based proxy cheating.

---

## 5️⃣ CSV/Excel Export for Professors
- One-click download of a clean `.csv` sheet.
- Created using `fputcsv()` with correct HTTP headers.

**Impact:** Professors get an audit-ready record for ERP/college submission.

---

## 6️⃣ Fully Structured Class Management (Year → Branch → Subject → Section)
- Professors can create and manage classes.
- Deletions protected by **foreign keys** and **transactions**.

**Impact:** No broken data, no orphaned logs, perfect DBMS discipline.

---

# 🛠️ Tech Stack

### **Backend**
- PHP 8.x (Native)
- Secure authentication (`password_hash`, `password_verify`)
- AJAX endpoints for live updates  
- Sessions for browser identity security

### **Database**
- MySQL with:
  - Foreign Keys  
  - Normalized tables  
  - ACID-safe operations  
  - Proper indexing  

### **Frontend**
- HTML5  
- CSS3 (Rose-Gold Maroon Theme #EE6983)  
- JavaScript + AJAX  
- QRious.js for QR generation

### **Local Server**
- XAMPP / MAMP (Apache + MySQL)

---

# 📂 Project Structure

```plaintext
smart_attendance/
├── db_connect.php            # Database connection using PDO/MySQLi
├── login.php                 # Secure professor login
├── dashboard.php             # Professor home: class list
├── manage_classes.php        # CRUD operations for class structure
├── create_session.php        # Starts session, generates PIN, activates QR
├── live_session.php          # Projector screen: QR, PIN, live updates
├── checkin.php               # Student check-in (QR + PIN)
├── end_session.php           # Ends attendance window
├── download_list.php         # Exports .csv attendance file
├── get_new_pin.php           # AJAX endpoint to refresh PIN
├── get_student_list.php      # AJAX endpoint to fetch live student list
└── README.md                 # This documentation

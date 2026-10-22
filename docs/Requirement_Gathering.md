# StudentHub Portal - Functional Requirements & System Architecture

**Subject:** Web Development Frameworks (ITUE203)  
**Institution:** Devang Patel Institute of Advance Technology and Research (DEPSTAR)  
**University:** Charotar University of Science and Technology (CHARUSAT)  
**Semester:** 3rd Semester, B.Tech Computer Engineering  

---

## 1. Project Overview & Scope

The **StudentHub Portal** is a centralized, accessible, responsive student information management system designed for engineering students and academic faculty. It unifies essential student lifecycle tasks into an intuitive web interface, including:
- Academic tracking (courses, credits, faculty details)
- Real-time attendance monitoring with eligibility status
- Course assignment deadlines and lab submission tracking
- Semester SGPA/CGPA result computation and transcript access
- Campus event exploration with category filtering, search, and registration deadlines
- User-friendly registration with regular expression validation and Canvas CAPTCHA
- Accessible design with WCAG 2.1 AA standards, keyboard navigation, and dark/light mode

---

## 2. User Roles & Permissions

| Role | Permissions & Capabilities |
| :--- | :--- |
| **Guest / Public** | • Browse Home, About, Events directory, FAQ, and Contact pages<br>• Access student registration and login forms<br>• Submit general inquiries and feedback |
| **Student** | • Secure login & session management<br>• View personalized Dashboard with CGPA, attendance %, and due assignments<br>• Access enrolled course syllabi via interactive modals<br>• Track subject-wise lecture & lab attendance<br>• View semester results and SGPA breakdown<br>• Register for campus hackathons and workshops<br>• Update profile details with dependent location selection |
| **Faculty / Admin** (Future Modules) | • View student directory and performance statistics<br>• Manage event listings, update venues and deadlines<br>• Update course assignments and upload lecture materials<br>• Review student feedback and system audit logs |

---

## 3. Site Navigation & Page Hierarchy

```mermaid
graph TD
    Home[index.html - Home Page] --> About[about.html - About Us]
    Home --> Events[events.html - Campus Events]
    Home --> FAQ[faq.html - FAQ Directory]
    Home --> Feedback[feedback.html - Student Feedback]
    Home --> Contact[contact.html - Contact Us]
    Home --> Login[login.html - Student Login]
    Home --> Register[register.html - Student Registration]
    
    Login --> Dashboard[dashboard.html - Student Dashboard]
    Register --> Login
    
    Dashboard --> Courses[courses.html - Enrolled Courses]
    Dashboard --> Attendance[attendance.html - Attendance Tracker]
    Dashboard --> Assignment[assignment.html - Assignments]
    Dashboard --> Result[result.html - Academic Results]
    Dashboard --> Profile[profile.html - Student Profile]
    Dashboard --> Events
```

---

## 4. Entity-Relationship (ER) Model (Practical 1 & 8 Design)

```mermaid
erDiagram
    USERS ||--o{ STUDENTS : "identifies"
    STUDENTS ||--o{ ATTENDANCE : "tracks"
    STUDENTS ||--o{ ENROLLMENTS : "registers"
    COURSES ||--o{ ENROLLMENTS : "contains"
    COURSES ||--o{ ASSIGNMENTS : "assigns"
    STUDENTS ||--o{ SUBMISSIONS : "submits"
    ASSIGNMENTS ||--o{ SUBMISSIONS : "evaluates"
    STUDENTS ||--o{ EVENT_REGISTRATIONS : "joins"
    EVENTS ||--o{ EVENT_REGISTRATIONS : "hosts"

    USERS {
        int id PK
        string email UK
        string password_hash
        string role
        timestamp created_at
    }

    STUDENTS {
        string student_id PK
        int user_id FK
        string full_name
        string phone
        string course
        int semester
        string city
        string state
        string country
        float cgpa
        float attendance_pct
    }

    EVENTS {
        int id PK
        string title
        string category
        date event_date
        string time
        string venue
        string organizer
        text description
        date deadline
        string status
    }
```

---

## 5. REST Route Plan for Future Backend Integration (Practical 1 Extension)

| Method | Endpoint | Description | Role Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/auth/register` | Register new student with hashed password | Public |
| `POST` | `/api/auth/login` | Authenticate student, create PHP session | Public |
| `POST` | `/api/auth/logout` | Destroy session & invalidate tokens | Authenticated |
| `GET` | `/api/events` | List all campus events (with search & filter) | Public |
| `GET` | `/api/events/{id}` | Get event details | Public |
| `POST` | `/api/events` | Create new event with poster upload | Admin |
| `GET` | `/api/students` | Get student directory with pagination | Admin / Student |
| `GET` | `/api/students/{id}` | Get specific student profile & stats | Student / Admin |
| `PUT` | `/api/students/{id}` | Update student contact & location details | Student / Admin |
| `GET` | `/api/courses` | List enrolled semester courses & syllabi | Student |
| `GET` | `/api/attendance/{id}`| Fetch subject-wise attendance breakdown | Student |
| `POST` | `/api/feedback` | Submit student rating & feedback comments | Public / Student |

# Student Registration Form - Validation Test Suite

**Project:** StudentHub Portal  
**Subject:** Web Development Frameworks (ITUE203) - Practical 5  
**Form Target:** `pages/register.html`  
**Engine:** `js/script.js` (`validateRegister()`, Regex patterns, and Canvas CAPTCHA)  

---

## 1. Test Cases & Execution Matrix

| Test ID | Field Tested | Input Value | Expected Behavior / Error Message | Result |
| :--- | :--- | :--- | :--- | :---: |
| **TC-01** | Full Name | *(Empty)* | Reject: "Full name is required." | PASS |
| **TC-02** | Full Name | `Jay123` *(Numbers)* | Reject: "Name must contain alphabets and spaces only (2-50 characters)." | PASS |
| **TC-03** | Full Name | `Jay Bhensdadiya` | Accept: Green valid border class applied. | PASS |
| **TC-04** | Email | `jay@gmail` *(Incomplete)* | Reject: "Please enter a valid email address (e.g., student@charusat.edu.in)." | PASS |
| **TC-05** | Email | `jay@charusat.edu.in` | Accept: Valid email pattern matches `/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/`. | PASS |
| **TC-06** | Mobile Phone | `98765` *(<10 digits)* | Reject: "Enter a valid 10-digit mobile number starting with 6-9." | PASS |
| **TC-07** | Mobile Phone | `1234567890` *(Starts with 1)* | Reject: "Enter a valid 10-digit mobile number starting with 6-9." | PASS |
| **TC-08** | Mobile Phone | `+91 9427721097` | Accept: Prefix sanitized, 10-digit regex verified. | PASS |
| **TC-09** | Course Dropdown | *(Unselected / Blank)* | Reject: "Please select your enrolled course/branch." | PASS |
| **TC-10** | Course Dropdown | `B.Tech CE` | Accept: Option registered. | PASS |
| **TC-11** | Location Cascading | Country `India`, State `Gujarat`, City `Changa` | Accept: Cascading dropdowns dynamically populate. | PASS |
| **TC-12** | Location Cascading | Country `India`, State *(Empty)* | Reject: "Please select your state/province." | PASS |
| **TC-13** | Gender | *(None selected)* | Reject: "Please select your gender." | PASS |
| **TC-14** | Password Strength | `abc` *(Short)* | Reject: "Password must be at least 8 characters..." Bar: Red "Weak". | PASS |
| **TC-15** | Password Strength | `Student123` *(No symbol)* | Reject: Missing special symbol. Bar: Orange "Medium". | PASS |
| **TC-16** | Password Strength | `Student@2026` | Accept: Meets all criteria. Bar: Green "Strong" (100% width). | PASS |
| **TC-17** | Confirm Password | `Student@9999` *(Mismatch)* | Reject: "Passwords do not match." | PASS |
| **TC-18** | Confirm Password | `Student@2026` *(Match)* | Accept: Passwords match. | PASS |
| **TC-19** | Canvas CAPTCHA | *(Empty or Mismatch)* | Reject: "CAPTCHA does not match. Please try again." | PASS |
| **TC-20** | Canvas CAPTCHA | `ABCDEF` *(Exact match)* | Accept: Validated against dynamic Canvas token. | PASS |
| **TC-21** | Terms Acceptance | *(Unchecked)* | Reject: "You must agree to the Terms of Service to proceed." | PASS |
| **TC-22** | Complete Valid Form | All fields valid + CAPTCHA matched | Submit: Alert "Registration Successful! Redirecting to login page..." and redirect. | PASS |

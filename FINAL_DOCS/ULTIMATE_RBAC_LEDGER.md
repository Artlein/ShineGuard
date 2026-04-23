# 🏆 Ultimate RBAC Verification Manual: Exhaustive Operational Core

This manual provides the definitive demonstration flow for all system roles (Admin, Maintainer, Observer), following the Master Table format.

---

## 🏛️ SECTION 1: ADMIN (ADM) - Full Governance
*High-level control, forensic auditing, and global configuration.*

| Test ID | Test Title | Test Description | Preconditions | Test Steps | Expected Result |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **ADM_01** | **Secure Entry** | Multi-factor login. | Sys Online | 1. Open `login.php`. 2. Enter Admin credentials. 3. Enter MFA code. | Redirected to Dash; Admin badge active. |
| **ADM_02** | **Analytic Audit** | Dashboard monitoring.| Logged in | 1. Audit KPIs on dash. 2. Verify unmasked PII data. | Full transparency; PII is readable. |
| **ADM_03** | **Hardware Control**| Manual Relay Toggle. | SBA Active | 1. Open Node 2 panel. 2. Click "FORCE ON". | Node state: Green; Relay engages. |
| **ADM_04** | **Dimming PWM** | PWM Simulation. | SBA Active | 1. Drag Intensity Slider to 25%. | Hardware syncs; 25% Duty cycle active. |
| **ADM_05** | **Self-Run Test** | Cloud Diagnostics. | Logged in | 1. Node Detail -> [Run Smart Test]. 2. Wait 3s. | Telemetry sweep returns OK/Healthy. |
| **ADM_06** | **CCTV Video** | High-Def Immersion. | Feeds active | 1. Open CCTV. 2. Click [Expand Fullscreen]. | Stream fills display; Real-time verified. |
| **ADM_07** | **Snapshot Gallery**| Forensic Capture. | Feeds active | 1. CCTV Feed -> [Take Secure Snapshot]. | Frame saved with SHA-256 integrity. |
| **ADM_08** | **Forensic Meta** | Evidentiary Audit. | Frame saved | 1. Gallery -> Toggle [Forensic Metadata]. | Node ID, Hash, and Timestamp visible. |
| **ADM_09** | **Alert Lifecycle** | Fault Resolution. | Alert Open | 1. Alerts -> Acknowledge -> Resolve. | Status: Resolved; Action logged in audit. |
| **ADM_10** | **Work Order Init** | Repair Management. | Alert Resolved| 1. Create WO from Alert. 2. Assign staff. | Work Order registered in registry. |
| **ADM_11** | **Smart Schedule** | Automated Orchestr.| Admin logged | 1. Schedules -> [Add New]. 2. Set Time/Dim. | Auto-trigger added to system policy. |
| **ADM_12** | **Export Report** | Forensic PDF. | Data exists | 1. Logs -> [Export Report] -> [Generate PDF].| Signed forensic PDF downloaded. |
| **ADM_13** | **Firebase Raw** | Cloud Stream Audit. | Data exists | 1. [Firebase Raw Data] -> Monitor stream. | Raw JSON updates live in real-time. |
| **ADM_14** | **Global Settings**| Global Governance. | Admin Context| 1. Settings -> Edit 'System Name' -> Save. | Branding updates site-wide instantly. |
| **ADM_15** | **Integrity Audit** | Cryptographic check.| Actions done | 1. [Security Logs] -> Verify Signature. | Entry turns Green: 'Signed by Kernel'. |
| **ADM_16** | **MFA Override** | Emergency Identity.| Admin Context| 1. User Profile -> [Reset MFA]. | User MFA detached; fresh enrollment req. |
| **ADM_17** | **Snapshot Init** | Forensic Backup. | Admin logged | 1. Data & Backup -> [Initiate Snapshot]. 2. Enter Admin Password. | SQL Archive generated with SHA-256 seal. |
| **ADM_18** | **System Restore** | Forensic Rollback. | Snapshot exists| 1. [Verify & Restore] -> Enter Admin Pass. | Database state restored; Page reloads. |

---

## 🔧 SECTION 2: MAINTAINER (MNT) - Field Operations
*Hardware maintenance, alert handling, and task execution.*

| Test ID | Test Title | Test Description | Preconditions | Test Steps | Expected Result |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **MNT_01** | **Login** | Operational Login. | Mnt Account | 1. Open `login.php`. 2. Enter Maintainer credentials. | Redirected to Dash; Admin menus hidden. |
| **MNT_02** | **Ops Dashboard** | Field Monitoring. | Logged in | 1. Audit telemetry. 2. Verify PII masking. | Data: `A***n R***s` (Data Privacy Active). |
| **MNT_03** | **Relay Toggle** | Authorized Override. | SBA Active | 1. Open Node 2 Detail. 2. Click "Toggle ON". | State updates to active in real-time. |
| **MNT_04** | **PWM Dimming** | Lighting Optimization.| SBA Active | 1. Move slider to 40% intensity. | Dashboard syncs with hardware PWM state. |
| **MNT_05** | **Self-Run Test** | Integrity Check. | Logged in | 1. Node Detail -> [Run Health Sweep]. | Hardware check complete; status: OK. |
| **MNT_06** | **CCTV Video Feed** | Safety Observation. | Feeds active | 1. Open CCTV Feed. 2. Monitor site status. | Live video stream active for field safety. |
| **MNT_07** | **Alert Handling**| Fault Response. | Alert Open | 1. Alerts Center -> Click [Acknowledge]. | Status: In-Progress; Staff action logged. |
| **MNT_08** | **Work Order Sync** | Task Updates. | WO Assigned | 1. Open [Maintenance Registry]. 2. Edit WO #102. 3. Update Status to 'In-Progress'. | Work order updated; changes synced to database. |
| **MNT_09** | **Raw Diagnostics**| Cloud Transparency. | Dashboard | 1. [Firebase Raw Data] -> View Stream. | Verified unprocessed sensor data sync. |
| **MNT_10** | **Identity Shield**| Access Block A. | Logged in | 1. Attempt access to [User Management]. | System redirect: "Access Denied" active. |
| **MNT_11** | **Backup Shield** | Access Block B. | Logged in | 1. Attempt access to [Data & Backup]. | Menu not rendered; Action is forbidden. |

---

## 👁️ SECTION 3: OBSERVER (OBS) - Analytical Audit
*Read-only monitoring, trend auditing, and zero-control safety.*

| Test ID | Test Title | Test Description | Preconditions | Test Steps | Expected Result |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **OBS_01** | **Login** | Auditing Login. | Obs Account | 1. Open `login.php`. 2. Enter Observer creds. | Redirected to View-Only Dashboard. |
| **OBS_02** | **Trend Monitoring**| Analytical Insight. | Logged in | 1. Observe real-time Lux/Temp charts. | Visualization rendering; All controls hidden. |
| **OBS_03** | **Map Status** | Pip observation. | Map active | 1. Monitor node pips for real-time status. | Icons show Green/Red state via Cloud. |
| **OBS_04** | **CCTV View** | Site Awareness. | Feeds active | 1. Open CCTV Stream. | Live view active; Take Snapshot is hidden. |
| **OBS_05** | **Alert History** | Observation Log. | Alerts active | 1. View historical alert registry. | History visible; Action buttons are removed. |
| **OBS_06** | **Analytics Summary**| Performance Monitor. | Data exists | 1. View weekly performance graphs. | Data is readable; "Export PDF" is disabled. |
| **OBS_07** | **Action Block** | Command Lockout. | Dashboard | 1. Attempt to toggle any node relay state. | Interaction is fully disabled/non-rendered. |
| **OBS_08** | **Sec Block A** | Setting Lockout. | Dashboard | 1. Attempt manual browser access to /settings.| Redirect to Global Access Denied page. |
| **OBS_09** | **Sec Block B** | Governance Lockout. | Dashboard | 1. Attempt manual access to /user_mgmt. | Handled via role-guard; Action logged. |
| **OBS_10** | **Audit Lockout** | Forensic Log Lockout. | Dashboard | 1. Manually navigate to `activity_logs.php`. | Redirected to **Access Denied UI** instantly. |

---
*Manual Version: ULTIMATE_V7.0 | Narrative: ShineGuard Operational Core*

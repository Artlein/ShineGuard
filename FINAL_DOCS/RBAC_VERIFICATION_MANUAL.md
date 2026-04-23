# 🏆 Master Granular Verification Manual: Full Operational Suite

This is the definitive guide for your live demonstration. Each row follows the **Master Table Format** with ultra-specific granular steps.

---

## 🏛️ SECTION 1: ADMIN SIDE (Full Authority)

| Test ID | Test Title | Test Description | Preconditions | Test Steps | Expected Result | Test Data | Actual Result | Remarks |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ADM_01** | **Secure Entry** | Multi-factor login check. | Sys Online | 1. Open `login.php`.<br>2. Enter Admin credentials.<br>3. Enter 6-digit MFA TOTP code.<br>4. Click **[Authorize Session]**. | Redirection to index.php; Admin badge active. | Admin Creds | Access Granted | PASS |
| **ADM_02** | **Analytic Dashboard**| Live monitoring audit. | Logged in | 1. Click **[Real-time Stats]**.<br>2. Hover over 'Reporter' column.<br>3. Check for unmasked names. | KPIs update live; PII is fully transparent. | System Stats | Data Visible | PASS |
| **ADM_03** | **Hardware Control** | Dimming PWM simulation. | SBA Active | 1. Click **[Intelligence Map]** on sidebar.<br>2. Open Node 2 panel.<br>3. Drag Dimming Slider to 25%. | Hardware synchronizes; Status: 25% Duty Cycle. | Node SL-002 | PWM Synced | PASS |
| **ADM_04** | **Master Toggle** | Global State Override. | SBA Active | 1. In Node Detail, locate 'Override'.<br>2. Click **[FORCE ON]** button. | Node state turns Green; Relay engages instantly. | Relay Cmd | Power Synced | PASS |
| **ADM_05** | **Policy Sync** | Threshold Broadcast. | Admin Context| 1. Settings -> Thresholds.<br>2. Set Lux Trigger to 35.<br>3. Click **[Broadcast Policy]**. | All nodes receive 35lx limit; Handshake ACK shown. | Global Policy | Broadcast OK | PASS |
| **ADM_06** | **Forensic Feed** | Secure CCTV capture. | Feeds active| 1. Open CCTV Feed.<br>2. Click **[Take Secure Snapshot]**. | Image saved to gallery with SHA-256 success toast. | Main Cam | Frame Captured| PASS |
| **ADM_07** | **Evidentiary Meta**| Snapshot verification. | Frame saved | 1. Open Gallery.<br>2. Toggle "Forensic Metadata" switch. | Node ID, Timestamp, and Forensic Hash visible. | Meta Overlay | Hash Verified | PASS |
| **ADM_08** | **Integrity Audit** | Cryptographic log check. | Actions done | 1. Navigate to **[Security Logs]**.<br>2. Click "Verify Signature" icon. | Entry turns green: "Signed by Kernel". | Log Hash | Verified ✅ | PASS |
| **ADM_09** | **Export Report** | Forensic PDF Generation. | Data exists | 1. Click **[Export Report]** on logs page.<br>2. Click **[Generated Signed PDF]**. | Multi-page PDF downloaded with security watermark. | Last 24 Hours | PDF Generated | PASS |
| **ADM_10** | **FAR Snapshot** | Forensic Backup. | SBA Active | 1. Settings -> Backup.<br>2. Click **[Generate Snapshot]**. | Progress bar finishes; Snap hash: `0xBE...` shown. | DB Registry | Snapshot OK | PASS |
| **ADM_11** | **User Lifecycle** | Role & Identity Gov. | Admin Context| 1. Settings -> Users.<br>2. Update Operator to Admin. | Permission trees rebuild; user has full access. | User: Arvin | Role Update | PASS |
| **ADM_12** | **MFA Override** | Emergency Identity Reset.| Admin Context| 1. In User Profile, push **[Reset MFA]**.<br>2. Confirm with Admin Password. | User MFA detached; fresh enrollment required. | Stale Token | MFA Reset OK | PASS |
| **ADM_13** | **CCTV Immersion** | Full-screen live video audit. | Feeds active | 1. Navigate to **[CCTV Feeds]**.<br>2. Select 'Node SL-002' stream.<br>3. Click **[Expand Fullscreen]** icon. | High-def stream fills display; real-time video verified. | SL-002 Stream | Working OK | PASS |
| **ADM_14** | **Alert Lifecycle** | Fault resolution and audit trail. | Alerts active | 1. Access **[Alerts Center]**.<br>2. Click **[Acknowledge]** on fault.<br>3. Input note and click **[Resolve]**. | Status: Resolved; timestamp and user ID persisted. | High-Severity | Resolved | PASS |
| **ADM_15** | **Smart Scheduling** | View and add operational schedules. | Admin Context | 1. Click **[Schedules]** on sidebar.<br>2. Audit list and click **[Add New]**.<br>3. Set time/dim level and click **[Save]**. | Schedule added to registry; automated triggers updated. | 18:00 @ 50% | Schedule OK | PASS |
| **ADM_16** | **Raw Firebase Data**| Audit unprocessed IoT telemetry. | Admin Context | 1. Click **[Firebase Raw Data]** on sidebar.<br>2. Inspect live JSON stream from SG-NODE2. | Raw attributes (ldrData, temp) update live in real-time. | Firebase JSON | Data Live | PASS |
| **ADM_17** | **Global Governance**| Manage core settings & system identity. | Admin Context | 1. Go to **[Settings]**.<br>2. Audit Prefs, Thresholds, Backup, Security, Users.<br>3. Edit 'System Name' -> 'ShineGuard City Hub' & Save. | Prefs synchronized; System name updates site-wide. | ShineGuard City Hub | Identity Sync | PASS |

---

## 🔧 SECTION 2: MAINTAINER SIDE (Operations)

| Test ID | Test Title | Test Description | Preconditions | Test Steps | Expected Result | Test Data | Actual Result | Remarks |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **MNT_01** | **Ops Entry** | Operational Entry check. | Mnt Account | 1. Login with Operator credentials. | Restricted Dashboard loads; Admin menus hidden. | Mnt Account | Access OK | PASS |
| **MNT_02** | **Privacy Mask** | Kernel-level masking. | Mnt Context | 1. View User List or Logs. | Names: `A***n R***s` (Data Privacy Active). | Resident PII | Masked Data | PASS |
| **MNT_03** | **Health Sweep** | Real-time diagnostics. | Logged in | 1. Detail -> **[Run Smart Test]**.<br>2. Wait 3s for Cloud response. | Analysis complete; Health pips show GREEN/OK. | Loopback Test | Node Healthy | PASS |
| **MNT_04** | **Map Isolation** | Geo-Filter Tasking. | Map active | 1. Filter Dropdown -> **[Faulty Only]**. | Map pins isolate specific nodes needing repair. | Filter: Faults | Isolation OK | PASS |
| **MNT_05** | **Cloud Bridge** | Manual DB Sync. | Dashboard | 1. Click **[Sync MySQL]** on hero bar. | "Sync Success" toast; local tables match Cloud. | RTDB Bridge | Sync ACK | PASS |
| **MNT_06** | **Incident Fix** | Alert Lifecycle Mgmt. | Alert Open | 1. Alerts Pane -> Click **[Resolve]**. | Status: Resolved; Incident archived in history. | SL-Fault-001 | Resolved | PASS |

---

## 👁️ SECTION 3: OBSERVER SIDE (Monitoring)

| Test ID | Test Title | Test Description | Preconditions | Test Steps | Expected Result | Test Data | Actual Result | Remarks |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **OBS_01** | **Monitor Entry**| Visual audit entry. | Obs Account | 1. Enter Observer Credentials. | Only Monitoring cards render; Controls hidden. | Obs Account | Access OK | PASS |
| **OBS_02** | **Telemetry Trend**| Live Analytics audit. | Obs Account | 1. Monitor Lux Trend charts. | Real-time graph updates via Firebase stream. | Live Feed | Trends Live | PASS |
| **OBS_03** | **Control Shield**| Interaction block check. | Dashboard | 1. Attempt to toggle Node 2 relay. | Buttons are non-rendered; Action is forbidden. | Forbidden CMD | Shielded OK | PASS |
| **OBS_04** | **Config Shield** | Security Shield check. | Dashboard | 1. Attempt manual URL entry to `settings`.| System redirect to Dash via Role Guard. | URL Injection | Denied OK | PASS |

---
*Generated: 2026-04-23 | Final Demonstration Standards | Master v6.0-GRANULAR*

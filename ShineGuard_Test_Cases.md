# ShineGuard System: Role-Based User Stories & Test Cases (Expanded)

Below is an expanded, highly detailed list of User Stories and Test Cases. These scenarios cover edge cases, negative testing, and granular interactions tailored specifically to the exact permissions and limitations of the three Role-Based Access Control (RBAC) tiers.

---

## 👑 Role #1: System Admin Testing Table
*As a System Admin, I have full system oversight, the ability to control hardware, manage system configurations, and authorize or terminate user access. I am responsible for system integrity.*

| Admin Test ID | Module | User Story / Specific Admin Test Case |
| :--- | :--- | :--- |
| **ADMIN-001** | Authentication | As an Admin, I want to log in securely so that my highest-level permissions are minted into my PHP session variables. |
| **ADMIN-002** | Security | As an Admin, I expect the system to automatically generate a completely new Session ID (`session_regenerate_id`) upon my successful login to prevent Session Fixation attacks. |
| **ADMIN-003** | Auto-Logout | As an Admin, if I leave my terminal idle for 30 minutes, I want the system to automatically log me out and destroy my session to prevent unauthorized physical access. |
| **ADMIN-004** | Dashboard View | As an Admin, I want to see the total number of Active Streetlights, Open Work Orders, and Total Power Usage dynamically calculated on my Dashboard immediately after login. |
| **ADMIN-005** | Hardware Mgmt | As an Admin, I want to add a brand-new streetlight to the database via the "Add Streetlight" modal, inputting its precise GPS coordinates, specifications, and static IP address. |
| **ADMIN-006** | Hardware Edge | As an Admin, if I attempt to add a new streetlight with an IP address or Pole ID that already exists in the database, the system must block the creation and throw a "Duplicate Entry" error toast. |
| **ADMIN-007** | Hardware Ctrl | As an Admin, I want to manually click the physical "OFF" toggle on a streetlight record and watch the system instantly transition its visual state to offline on both the Map and the Data grid. |
| **ADMIN-008** | CCTV Capture | As an Admin, I want to click a "Take Snapshot" button that seamlessly triggers an asynchronous HTTP request to securely capture the camera's current frame without freezing my local UI session. |
| **ADMIN-009** | Workflow Creation | As an Admin, I want to manually click "Create Work Order" directly from an active hardware Alert row so that a technician is immediately tied to that specific hardware failure. |
| **ADMIN-010** | Assignment Logic | As an Admin, I want to assign a Work Order, but the dropdown menu must *only* show users who hold the `Maintenance Operator` role, preventing me from assigning physical repairs to Observers. |
| **ADMIN-011** | Workflow Analytics | As an Admin, I want all closed Work Orders to automatically calculate the "Total Time to Repair" metric for me, boosting my department's efficiency analytics. |
| **ADMIN-012** | PDF Generation | As an Admin, I want to select a custom date range and click "Generate PDF" to export a professional, graphical system report containing embedded charts showing total alerts and electrical anomalies. |
| **ADMIN-013** | CSV Export | As an Admin, I want to click "Export CSV" to download the raw numerical telemetry history for all streetlights straight into a Microsoft Excel compatible format. |
| **ADMIN-014** | Settings Config | As an Admin, I want to navigate to the "System Preferences" tab to dynamically change the global Application Name, Organization Name, and primary Hex Theme Color without touching code. |
| **ADMIN-015** | Map Config | As an Admin, I want to dynamically update the default Dashboard Map Center Coordinates (Lat/Lng) inside Settings so the map always boots centered exactly over our Barangay Hall. |
| **ADMIN-016** | User Creation | As an Admin, I want to create a new `Maintenance Operator`, but the system must force me to input a strict `@hulo.gov.ph` email and a modern complex password (Upper, Number, Symbol, 8 chars minimum). |
| **ADMIN-017** | User Creation Edge | As an Admin, if I try to create a new user with an email address that does *not* end in `@hulo.gov.ph` (e.g., a standard gmail account), the system must block it and show an invalid domain error. |
| **ADMIN-018** | User Edit | As an Admin, I want to click "Edit" on an existing user to change their Role from `System Observer` to `Maintenance Operator`. |
| **ADMIN-019** | User Suspend | As an Admin, I want to temporarily suspend a user by unchecking their "Is Active" box in the Edit modal, ensuring they cannot log in even if they know their password. |
| **ADMIN-020** | Secure Deletion | As an Admin, I want to click the red "🗑️ Delete" button on an employee who resigned. A modal must pop up requiring me to securely re-type my own active Admin Password before executing the destructive database drop. |
| **ADMIN-021** | Suicide Block | As the active Admin, if I mistakenly attempt to securely delete *my own account* while logged in, the system must instantly block me and drop an error message, preventing accidental lockouts. |
| **ADMIN-022** | Deletion Auth Fail | As an Admin, if I input the wrong secondary authorization password during a user deletion attempt, the system must abort the transaction instantly and flash an `Authorization Failed` error toast. |

---

## 🛠️ Role #2: Maintenance Operator Testing Table
*As a Maintenance Operator, I am a field technician. I can view geographical dashboards, toggle physical hardware states in emergencies, and complete any physical repair tasks assigned to me, but I cannot manipulate system configurations or users.*

| Operator Test ID | Module | User Story / Specific Operator Test Case |
| :--- | :--- | :--- |
| **OPERATOR-001** | RBAC Enforcement | As a Maintenance Operator, I want to log in using my authorized credentials and be structurally restricted from viewing the "User Management", "System Preferences", or "Thresholds" tabs in the Settings panel entirely. |
| **OPERATOR-002** | Dashboard View | As a Maintenance Operator, I want to view the Map on my mobile device and seamlessly pinch-to-zoom to see exactly which streetlights in my sector are currently offline. |
| **OPERATOR-003** | Telemetry Read | As a Maintenance Operator, I want to click on a specific streetlight icon to view its live, real-time electrical telemetry (Voltage, Amperage, Wattage readings). |
| **OPERATOR-004** | Hardware Ctrl | As a Maintenance Operator, I want to manually click the physical "OFF" toggle on a streetlight record if I see sparks flying, immediately cutting power logic to the node. |
| **OPERATOR-005** | Hardware Block | As a Maintenance Operator, I should *not* see the "Delete Streetlight" button, preventing me from accidentally destroying historical asset data. |
| **OPERATOR-006** | Global Search | As a Maintenance Operator, I want to use the global navigation search bar to type `SL-045` and have the system instantly locate that specific pole on the map for rapid field deployment. |
| **OPERATOR-007** | Alert Generation | As a Maintenance Operator, I want the system to flash an alert at me instantly if it detects 0 Voltage on a pole that is supposed to be mathematically drawing power, indicating a blown bulb. |
| **OPERATOR-008** | Alert Ack | As a Maintenance Operator, I want to click "Acknowledge" on a new Alert so that the central dispatch (Admin) knows I am personally investigating the hardware failure on-site. |
| **OPERATOR-009** | Workflow Receive | As a Maintenance Operator, I want to log in and immediately see a prioritized list of Work Orders that have my specific `user_id` assigned to them by the Admin. |
| **OPERATOR-010** | Workflow Update | As a Maintenance Operator, I want to click into an assigned Work Order and change its status from "Pending" to "In Progress" when I arrive at the physical pole. |
| **OPERATOR-011** | Workflow Close | As a Maintenance Operator, I want to type my physical repair notes ("Replaced 60W LED bulb and checked terminal wiring") and close the ticket out, removing it from my active queue. |
| **OPERATOR-012** | Workflow Lock | As a Maintenance Operator, once I mark a Work Order as "Resolved", the system must lock the record so I cannot retroactively change my repair notes to alter accountability. |

---

## 👁️ Role #3: System Observer Testing Table
*As a System Observer, I am an auditor, mayor, or read-only stakeholder. I can monitor metrics, read analytics, and view camera feeds, but I have absolutely zero authority to control hardware, create work orders, or change users.*

| Observer Test ID | Module | User Story / Specific Observer Test Case |
| :--- | :--- | :--- |
| **OBSERVER-001** | RBAC Block | As a System Observer, I want to securely log in and have the system fundamentally hide the Settings navigation link and any configuration panels completely from my UI. |
| **OBSERVER-002** | Dashboard Read | As a System Observer, I want to clearly read the high-level statistics (Total Streetlights, Active Alerts, Open Work Orders) immediately upon login so I can unilaterally monitor system health. |
| **OBSERVER-003** | Metric Monitor | As a System Observer, I want to watch the system dynamically calculate "Power Usage" based on the ongoing wattage draw of all active streetlights down to the exact decimal. |
| **OBSERVER-004** | Dashboard Nav | As a System Observer, I want to freely toggle between the interactive Map View, Grid View, and Table View within the dashboard container without breaking the UI grid layout. |
| **OBSERVER-005** | Global Ping | As a System Observer, I want an active, red pulsing `🚨 Alerts [Count]` pill button to appear in my top-right header if any sensors trip, alerting me instantly regardless of what page I am on. |
| **OBSERVER-006** | Hardware Block | As a System Observer, I can view the Streetlights data table, but if I attempt to click the "ON/OFF" toggle switch, the system must physically disable the UI element and block the POST request, ensuring I cannot control hardware. |
| **OBSERVER-007** | CCTV Live | As a System Observer, I want to view a live grid of simulated CCTV camera feeds covering key vehicular intersections to monitor traffic passively. |
| **OBSERVER-008** | CCTV Expand | As a System Observer, I want to click on a specific CCTV feed to expand it into a full-screen modal view for a detailed, undistracted view of an incident. |
| **OBSERVER-009** | Snapshot Block | As a System Observer, the "Take Snapshot" button must be hidden from my CCTV view, preventing me from capturing arbitrary frames and eating up server storage. |
| **OBSERVER-010** | Snapshot Read | As a System Observer, I want to open the Snapshot Gallery to view previously captured frames saved by authorized Admins, sorted chronologically by timestamp, for auditing purposes. |
| **OBSERVER-011** | Report Read | As a System Observer, I want to view previously generated PDF incident reports, but the complex PDF "Generate" engine UI should ideally be restricted to Admins. |
| **OBSERVER-012** | URL Forcing Block | As a malicious Observer, if I manually type `http://localhost/ShineGuard/settings.php` directly into the URL bar, the PHP backend must detect my RBAC Role and instantly redirect me back to the dashboard with an "Unauthorized" error toast. |

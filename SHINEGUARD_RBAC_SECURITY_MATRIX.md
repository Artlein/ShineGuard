# ShineGuard: Security RBAC & Identity Matrix

This document defines the **Role-Based Access Control (RBAC)** architecture of the ShineGuard IoT Platform. It is designed to enforce the "Principle of Least Privilege," ensuring that users only have access to the controls and data necessary for their specific job function.

---

### 🛡️ RBAC Authorization Matrix

| Feature / Action | System Admin | Maintenance Operator | System Observer |
| :--- | :---: | :---: | :---: |
| **💡 Streetlight Control** (On/Off/Dim) | ✅ | ✅ | ❌ |
| **🗺️ View Map & Live Status** | ✅ | ✅ | ✅ |
| **📹 View Live CCTV Streams** | ✅ | ✅ | ✅ |
| **📸 Take CCTV Snapshots** | ✅ | ❌ | ❌ |
| **⚙️ Manage CCTV Configs** (IP/Pass) | ✅ | ❌ | ❌ |
| **🔧 Create/Update Work Orders** | ✅ | ❌ | ❌ |
| **📜 View Activity Logs** | ✅ | ❌ | ❌ |
| **📤 Export Reports (PDF/CSV)** | ✅ | ❌ | ❌ |
| **👥 User & Role Management** | ✅ | ❌ | ❌ |
| **☁️ Manage Firebase/S3 Cloud** | ✅ | ❌ | ❌ |
| **🛡️ Global System Settings** | ✅ | ✅ (View Only) | ✅ (View Only) |
| **🕵️ View Raw PII** (Data Privacy) | ✅ | ❌ (Masked) | ❌ (Masked) |

---

### ⚔️ Detailed Capability Table: CAN vs CANNOT

This table provides a direct comparison of authority levels across the three core system tiers.

| Role | 👍 CAN | 👎 CANNOT |
| :--- | :--- | :--- |
| **System Admin** | • Override any system setting<br>• Create/Delete user accounts<br>• Access unmasked PII data<br>• Generate forensic SQL backups<br>• Verify Audit Log Integrity | • Cannot bypass cryptographic logging (even admins are tracked) |
| **Maintenance Operator** | • Control physical streetlight power<br>• Adjust dimming for repair tests<br>• View live camera feeds for site safety<br>• Acknowledge system alerts | • Access security settings or passwords<br>• Delete any data or log entries<br>• See unmasked resident information<br>• Export sensitive audit reports |
| **System Observer** | • Monitor real-time city status<br>• View streetlights on the map<br>• View analytical performance reports<br>• Observe live camera thumbnails | • Toggle any hardware (No control)<br>• Access any configuration menus<br>• Manage users or work orders<br>• Download secure snapshots |

---

### 🕵️ Data Privacy Compliance (Data Privacy Act)
ShineGuard implements an "Opaque Data" policy for PII (Personally Identifiable Information). Unless you are a System Admin, the system dynamically masks data at the kernel level:

| Data Type | Raw (Admin) | Masked (Operator/Observer) |
| :--- | :--- | :--- |
| **Full Name** | Arvin Reyes | A***n |
| **Email Address** | arvinreyes@example.com | ar***@example.com |
| **Phone Number** | +63 917 123 4567 | +63 917 *** 4567 |

---

### 🔒 Security Integration Layer
1. **Multi-Factor Authentication**: Mandatory for administrative roles.
2. **SBA (Secure Session Authorization)**: Requires password re-verification for high-risk regions.
3. **Chain-of-Trust Logging**: Every row in the DB is SHA-256 signed.

*This document is part of the ShineGuard System Security Portfolio.*

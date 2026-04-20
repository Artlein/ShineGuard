# ShineGuard: Security RBAC & Identity Matrix

This document defines the **Role-Based Access Control (RBAC)** architecture of the ShineGuard IoT Platform.

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
| **📤 Export Reports** (PDF/CSV) | ✅ | ❌ | ❌ |
| **👥 User & Role Management** | ✅ | ❌ | ❌ |
| **☁️ Manage Firebase/S3 Cloud** | ✅ | ❌ | ❌ |
| **🛡️ Global System Settings** | ✅ | ✅ (View Only) | ✅ (View Only) |
| **🕵️ View Raw PII** (Privacy Act) | ✅ | ❌ (Masked) | ❌ (Masked) |

---

### 🕵️ Data Privacy (PII Masking) Comparison

| Data Type | System Admin View | Operator / Observer View |
| :--- | :--- | :--- |
| **Full Name** | Arvin Reyes | A***n |
| **Email Address** | arvinreyes@example.com | ar***@example.com |
| **Phone Number** | +63 917 123 4567 | +63 917 *** 4567 |

---

*This document is formatted for panelist presentation.*

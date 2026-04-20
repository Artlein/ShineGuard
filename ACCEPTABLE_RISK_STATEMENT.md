# ShineGuard Infrastructure Data — Acceptable Risk Statement
## Zero-Trust Security Architecture | April 2026

## Scope
GPS coordinate data (`latitude`, `longitude`) is retained in plaintext as operational infrastructure data — not PII under RA 10173.

## Rationale
Encryption of GPS coordinates is not technically feasible:
- Map rendering (Leaflet.js) requires raw DECIMAL values
- MySQL DECIMAL columns cannot store Base64 ciphertext
- Real-time IoT calculations require raw numeric values

## Mitigating Controls
| Control | Status |
|---|---|
| Authentication required for all data access | ✅ requireLogin() enforced |
| Location text fields encrypted (AES-256) | ✅ Implemented April 2026 |
| PHPMyAdmin blocked from public internet | ✅ Apache rule applied |
| Forensic audit log chain | ✅ HMAC-SHA256 active |
| MFA on all privileged roles | ✅ TOTP enforced |

## Risk Classification: LOW
Streetlight locations are publicly visible physical infrastructure. No PII is derivable from GPS coordinates alone.

**Date:** April 20, 2026 | **Classification:** Internal Security Documentation

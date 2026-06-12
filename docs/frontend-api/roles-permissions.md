# RolesPermissions Module — Frontend API Documentation

## 1. Module Overview

The RolesPermissions module exposes the RBAC (role-based access control) system: listing roles and permissions, assigning/revoking roles, and granting/revoking **per-staff permission overrides**.

- **Actors:** primarily `medical_center_admin`. Read endpoints can be used by staff with `view staff`.
- **Key concepts:**
  - **Roles:** `medical_center_admin`, `doctor`, `secretary`, `imaging_technician`. Each role carries a default permission set (seeded).
  - **Permissions:** flat string names (e.g. `"view patients"`, `"create imaging request"`). The effective permission check is: *override (grant/deny) if one exists, otherwise role permission*.
  - **Permission overrides:** per-staff grant/deny entries that win over the role defaults. They can be temporary with `expires_at`.
  - Only `medical_center_admin` can assign roles; overriding permissions additionally requires the `override permissions` permission.
- **Connections:** every protected endpoint in Patients, Appointments, MedicalRecords, and Imaging checks one of these permissions. The login response (Authentication module) returns the staff's effective roles + permissions for UI decisions.

## 2. Base Route Prefix

```
/api/role-permission
```

All endpoints require `auth:sanctum` → `Authorization: Bearer <token>`.

## 3. Authentication and Permissions

| Endpoint | Authorization |
|---|---|
| `GET /index` | policy `viewAny` on Staff → permission **`view staff`** |
| `GET /permissions` | authenticated only (no extra permission) |
| `POST /roles/assign`, `POST /roles/revoke` | gate `can-assign-roles` → actor must have the **`medical_center_admin` role** |
| `POST /permissions/grant`, `/revoke`, `/clear`, `/clearAll` | gate `can-override-permissions` → admin role **and** permission `override permissions` |

- 401 if unauthenticated; 403 (`code: "FORBIDDEN"`) if the gate/policy denies.

## 4. API Endpoint List

| Method | URL | Purpose | Auth | Permission | Request | Main response |
|---|---|---|---|---|---|---|
| GET | `/api/role-permission/index` | List roles with their permissions | Yes | `view staff` | — | array of roles |
| GET | `/api/role-permission/permissions` | List all permissions | Yes | — | — | array of permissions |
| POST | `/api/role-permission/roles/assign` | Assign role to staff | Yes | admin | JSON | assignment summary |
| POST | `/api/role-permission/roles/revoke` | Revoke role from staff | Yes | admin | JSON | revocation summary |
| POST | `/api/role-permission/permissions/grant` | Grant permission override | Yes | admin + `override permissions` | JSON | override summary |
| POST | `/api/role-permission/permissions/revoke` | Deny permission override | Yes | admin + `override permissions` | JSON | override summary |
| POST | `/api/role-permission/permissions/clear` | Remove one override | Yes | admin + `override permissions` | JSON | summary |
| POST | `/api/role-permission/permissions/clearAll` | Remove all overrides of a staff | Yes | admin + `override permissions` | JSON | summary + count |

## 5. Detailed API Documentation

### 5.1 GET `/api/role-permission/index`

- **Purpose:** roles with labels and their default permissions; use to build role pickers and permission matrices.
- **Success 200:**

```json
{
  "success": true, "status_code": 200, "message": "Roles fetched",
  "data": [
    {
      "id": 2,
      "name": "doctor",
      "label": "Doctor",
      "permissions": ["view patients", "create visit record", "..."]
    }
  ]
}
```

`label` is translated according to `Accept-Language`.

### 5.2 GET `/api/role-permission/permissions`

- **Success 200:** `data: [ { "id": 1, "name": "view staff" }, ... ]`
- Use this list to populate override pickers — **never hardcode permission strings**.

### 5.3 POST `/api/role-permission/roles/assign`

- **Body:**

```json
{
  "staff_id": 9,
  "role": "secretary",
  "clinic_id": 1,
  "is_temporary": false,
  "expires_at": null,
  "notes": "covering front desk"
}
```

- **Validation:** `staff_id` required, must exist; `role` required, one of the 4 role names; `clinic_id` nullable int (defaults to configured clinic); `is_temporary` boolean; `expires_at` nullable date in the future; `notes` max 1000.
- **Success 200:**

```json
{ "data": { "staff_id": 9, "staff_name": "New Secretary", "role": "secretary", "clinic_id": 1, "record_id": 12 } }
```

- **Errors:** 403 not allowed; 422 invalid role; 404 staff not found (model lookup).

### 5.4 POST `/api/role-permission/roles/revoke`

- **Body:** `{ "staff_id": 9, "role": "secretary", "clinic_id": 1 }`
- **Success 200:** `{ "staff_id": 9, "staff_name": "...", "role": "secretary", "clinic_id": 1 }`

### 5.5 POST `/api/role-permission/permissions/grant` and `/permissions/revoke`

Grant = override effect `grant`; Revoke = override effect `deny` (an explicit deny that wins over role defaults).

- **Body:**

```json
{
  "staff_id": 9,
  "permission": "view statistics",
  "is_temporary": true,
  "expires_at": "2026-07-01T00:00:00Z",
  "notes": "temporary access for the monthly report"
}
```

- **Validation:** `permission` required and must be one of the known permission names.
- **Success 200:** `{ "staff_id": 9, "staff_name": "...", "permission": "view statistics", "effect": "grant", "override_id": 4 }` (`effect: "deny"` for revoke).

### 5.6 POST `/api/role-permission/permissions/clear`

- **Body:** `{ "staff_id": 9, "permission": "view statistics" }`
- **Success 200:** `{ "staff_id": 9, "staff_name": "...", "permission": "view statistics", "effect": "cleared" }` — staff falls back to role defaults for that permission.

### 5.7 POST `/api/role-permission/permissions/clearAll`

- **Body:** `{ "staff_id": 9 }`
- **Success 200:** `{ "staff_id": 9, "staff_name": "...", "effect": "cleared", "deleted_count": 3 }`
- **Error 422:** staff has no permission overrides.

## 6. Response Shape

Standard `ApiResponse` wrapper (see Authentication doc §6). No pagination is used in this module — role and permission lists are returned as plain arrays in `data`.

## 7. Roles and Default Permissions (seeded)

| Role | Highlights of default permissions |
|---|---|
| `medical_center_admin` | staff management (`view/create/edit staff`, `toggle staff status`), `assign roles`, `revoke roles`, `override permissions`, rooms & devices management, schedules, center settings, `view patients`, `search patient`, archive/restore patients, `view appointments`, `view statistics`, `view financial summary`, `view activity log`, `view imaging requests`, `view all imaging requests`, `manage imaging queue`, `delete any imaging file` |
| `doctor` | patients (view/search/create/edit/archive/restore), `view appointments`, full medical records (`view/edit medical records`, `create visit record`, timelines, reports, prescriptions, measurements, diagnoses, disease classification, private notes), imaging (`create imaging request`, `view own imaging requests`, `cancel imaging request`, `upload doctor imaging files`, `upload external imaging files`, `compare images`, `annotate images`), devices CRUD, `view statistics` |
| `secretary` | patients (view/create/edit/search/archive/restore), appointments (`create/cancel/confirm appointment`, `manage patient status`, `assign patient to doctor`), imaging (`view imaging requests`, `create imaging request for patient`, `confirm imaging payment`, `send imaging request to technician`, `cancel imaging request`), invoices, devices CRUD, rooms, `view staff`, schedules |
| `imaging_technician` | `view imaging queue`, `upload imaging files`, `delete own imaging file`, `start imaging request`, `complete imaging request`, `update imaging request status`, `view patients`, `search patient`, `view devices`, `view clinic rooms`, `view schedules` |

The full permission catalog comes from `GET /permissions` — render it dynamically.

## 8. Frontend Action Flags

This module returns no `can_*` flags. UI gating rule:

> **Use the `permissions` array from the login response** to decide whether to show role/permission management screens (need `view staff`; mutations need the admin role). Where other modules return explicit `can_*` flags (MedicalRecords visit sessions, timelines), prefer those flags over local permission checks.

## 9. Cross-Module Integration Notes

- All `403` responses across the system originate from these permissions.
- Authentication's `POST /api/auth/staff` internally calls assign-role and the override endpoints' logic — the same validation rules apply.
- Permission overrides take effect immediately (cache is cleared server-side), but a logged-in user's cached `permissions` array in the frontend becomes stale — they see updated permissions after re-login (or you can re-fetch on demand).

## 10. Frontend Flows

**Roles & permissions screen (admin)**
1. On page load: `GET /index` (roles + their permissions) and `GET /permissions` (catalog).
2. Display matrix of role → permissions.

**Change a staff member's role**
1. `POST /roles/assign` with `staff_id` + `role` (confirm modal recommended).
2. To remove: `POST /roles/revoke`.

**Override a single permission**
1. `POST /permissions/grant` or `/permissions/revoke` (deny).
2. Show active overrides with badges: `grant` (green) / `deny` (red); temporary ones show `expires_at`.
3. `POST /permissions/clear` to drop a single override; `clearAll` with a confirm modal.

**UI gating everywhere**
- Hide buttons the staff cannot use based on the `permissions` array; the backend still enforces, so handle 403 gracefully (toast + refresh state).

## 11. Form-Data Examples

All endpoints are JSON-only.

## 12. Error Handling

| Code | When |
|---|---|
| 401 | No/expired token |
| 403 | Not admin (`roles/*`), not allowed to override permissions (`permissions/*`), missing `view staff` (`/index`) |
| 404 | `staff_id` not found |
| 422 | Validation (unknown role/permission, `expires_at` in the past), staff has no overrides (`clearAll`) |
| 500 | Unexpected error |

## 13. Frontend Notes

- **On page load:** `GET /index` + `GET /permissions`; cache for the session (they change rarely).
- **After mutations:** refresh the staff member's effective permission view; remember the target user must re-login to see changes in their own UI.
- **Do not hardcode** permission name strings or role labels — fetch them.
- **Nullable:** `clinic_id`, `expires_at`, `notes`.
- **Confirmation modals:** role revoke, permission deny, `clearAll`.
- **multipart:** none.

# Authentication Module — Frontend API Documentation

## 1. Module Overview

The Authentication module handles staff login/logout, password change, the forgot-password (OTP) flow, and creation of new staff accounts by an administrator.

- **Actors:** all staff roles (`medical_center_admin`, `doctor`, `secretary`, `imaging_technician`). Staff account creation is admin-only.
- **Key concepts:**
  - Authentication is **token-based (Laravel Sanctum)**. Login returns a Bearer token with an expiry time.
  - The login response also returns the staff member's **roles and permissions** — the frontend must store these and use them to show/hide UI (see RolesPermissions doc).
  - Failed login attempts are counted. After too many failures (default 5) the account is locked and requires a password reset (HTTP **423**, code `PASSWORD_RESET_REQUIRED`).
  - Forgot-password is a 3-step OTP flow: send code → verify OTP → reset with a one-time `reset_token`.
- **Connections to other modules:** every other module requires the Sanctum token issued here. Roles/permissions returned at login drive all permission-based UI. Staff created here are assigned roles through the RolesPermissions module.

## 2. Base Route Prefix

```
/api/auth
```

- Public endpoints: `/login`, `/forgot-password/*`.
- Protected endpoints (`/logout`, `/change-password`, `/staff`) require the header:

```
Authorization: Bearer <token>
Accept: application/json
Accept-Language: en   (or "ar" — controls the language of all API messages)
```

## 3. Authentication and Permissions

| Endpoint | Guard | Permission / Role | Notes |
|---|---|---|---|
| `POST /login` | none (public) | — | Returns 401/423/403 on failure |
| `POST /forgot-password/send-code` | none | — | CAPTCHA may be required |
| `POST /forgot-password/verify-otp` | none | — | |
| `POST /forgot-password/reset` | none | — | |
| `POST /logout` | `auth:sanctum` | any authenticated staff | 401 if token missing/expired |
| `POST /change-password` | `auth:sanctum` | any authenticated staff | |
| `POST /staff` | `auth:sanctum` | `medical_center_admin` role (checked in service) | 403 otherwise |

- **401 behavior:** missing/invalid token → `{"success": false, "status_code": 401, "message": "...", "code": "UNAUTHORIZED", "errors": null}`.
- **403 behavior:** authenticated but not allowed → `code: "FORBIDDEN"` (or a translated business message).

## 4. API Endpoint List

| Method | URL | Purpose | Auth | Permission | Request | Main response object |
|---|---|---|---|---|---|---|
| POST | `/api/auth/login` | Staff login | No | — | JSON | `token`, `staff`, `clinic_id` |
| POST | `/api/auth/forgot-password/send-code` | Send OTP to email/SMS | No | — | JSON | `resend_available_at`, `otp_channel` |
| POST | `/api/auth/forgot-password/verify-otp` | Verify OTP | No | — | JSON | `reset_token` |
| POST | `/api/auth/forgot-password/reset` | Set new password | No | — | JSON | `null` |
| POST | `/api/auth/logout` | Revoke all tokens | Yes | — | (empty) | `null` |
| POST | `/api/auth/change-password` | Change own password | Yes | — | JSON | `null` |
| POST | `/api/auth/staff` | Create staff account | Yes | admin (manage roles) | JSON | `staff`, `credentials_sent` |

## 5. Detailed API Documentation

### 5.1 POST `/api/auth/login`

- **Purpose:** authenticate a staff member and obtain a Bearer token.
- **Used by:** login page.
- **Headers:** `Accept: application/json`, `Accept-Language`.
- **Body (JSON):**

```json
{
  "email": "doctor@clinic.com",
  "password": "secret123"
}
```

- **Validation:** `email` required/email format; `password` required string.
- **Success 200:**

```json
{
  "success": true,
  "status_code": 200,
  "message": "Logged in successfully",
  "data": {
    "token": "1|XxYyZz...",
    "token_expires_at": "2026-06-13T10:00:00.000000Z",
    "clinic_id": 1,
    "staff": {
      "id": 5,
      "name": "Dr. Sami",
      "email": "doctor@clinic.com",
      "phone": "0999999999",
      "is_active": true,
      "roles": ["doctor"],
      "permissions": ["view patients", "create visit record", "..."]
    }
  }
}
```

- **Errors:**
  - `401` + `code: "INVALID_CREDENTIALS"` — wrong email or password.
  - `423` + `code: "PASSWORD_RESET_REQUIRED"` — account locked after repeated failures (default 5) or reset already required. Frontend must redirect to the forgot-password flow.
  - `403` + `code: "ACCOUNT_DEACTIVATED"` — account disabled.
  - `422` validation errors.
- **Frontend notes:** store `token`, `token_expires_at`, `staff.roles`, `staff.permissions`. `clinic_id` may be `null` if the staff has no clinic role record.

### 5.2 POST `/api/auth/forgot-password/send-code`

- **Purpose:** send a 6-digit OTP (valid 10 minutes) by email and/or SMS.
- **Body (JSON):**

```json
{
  "email": "doctor@clinic.com",
  "captcha_token": "recaptcha-token-here"
}
```

- **Validation:** `email` required/email. `captcha_token` is **required only when CAPTCHA is enabled** in backend config (`opticare.captcha_enabled`); otherwise nullable.
- **Success 200:**

```json
{
  "success": true,
  "status_code": 200,
  "message": "OTP sent",
  "data": {
    "resend_available_at": "2026-06-12T10:00:20.000000Z",
    "otp_channel": "email"
  }
}
```

`otp_channel` is one of `email`, `sms`, `both`.

- **Errors:**
  - `422` — CAPTCHA failed, or staff has no phone while SMS channel is configured.
  - `429` — resend requested too soon (message includes the remaining seconds).
- **Important:** if the email does not exist, the API still returns a **generic success response** (anti-enumeration). Always show "If this email exists, a code was sent."
- **Frontend notes:** disable the "Resend" button until `resend_available_at`. The resend delay grows after each resend.

### 5.3 POST `/api/auth/forgot-password/verify-otp`

- **Body:**

```json
{ "email": "doctor@clinic.com", "otp": "123456" }
```

- **Validation:** `otp` required, exactly 6 digits.
- **Success 200:** `data: { "reset_token": "<64-char token>" }`. Token is valid for **15 minutes**.
- **Errors (422):** no valid OTP / OTP expired / OTP incorrect.

### 5.4 POST `/api/auth/forgot-password/reset`

- **Body:**

```json
{
  "reset_token": "<token from verify-otp>",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

- **Validation:** `reset_token` required; `password` required, min 8, must match `password_confirmation`.
- **Success 200:** `data: null`, message "Password reset".
- **Errors:** `422` invalid/expired reset token, `422` new password same as current, `404` staff not found.
- **Side effects:** all existing tokens are revoked; the failed-attempts lock is cleared. The user must log in again.

### 5.5 POST `/api/auth/logout` (auth)

- No body. Revokes **all** tokens of the staff member (logs out all devices).
- **Success 200:** `data: null`.

### 5.6 POST `/api/auth/change-password` (auth)

- **Body:**

```json
{
  "current_password": "oldPass123",
  "password": "newPass123",
  "password_confirmation": "newPass123"
}
```

- **Validation:** `current_password` required; `password` required, min 8, confirmed.
- **Errors (422):** current password incorrect; new password same as current.
- **Side effects:** all **other** tokens are revoked; the current session token stays valid.

### 5.7 POST `/api/auth/staff` (auth, admin only)

- **Purpose:** create a doctor / secretary / imaging-technician account. A welcome email with credentials is sent automatically.
- **Used by:** admin "Staff management" screen.
- **Body (JSON):**

```json
{
  "name": "New Secretary",
  "email": "sec@clinic.com",
  "phone": "0991234567",
  "password": null,
  "role": "secretary",
  "clinic_id": 1,
  "permission_overrides": [
    { "permission": "view statistics", "effect": "grant" },
    { "permission": "cancel appointment", "effect": "deny" }
  ]
}
```

- **Validation:**
  - `name` required, max 255. `email` required, valid, unique in `staff`.
  - `phone` nullable, max 20. `password` nullable, min 8 — **if omitted a temporary password is generated and emailed**.
  - `role` required, one of: `doctor`, `secretary`, `imaging_technician` (an admin account cannot be created here).
  - `clinic_id` nullable integer (defaults to the configured clinic).
  - `permission_overrides` optional array; each item needs `permission` (must be a valid permission name) and `effect` (`grant` | `deny`). Overrides are only applied if the actor can override permissions.
- **Success 201:**

```json
{
  "success": true,
  "status_code": 201,
  "message": "Staff account created",
  "data": {
    "staff": { "id": 9, "name": "New Secretary", "email": "sec@clinic.com", "phone": "0991234567", "is_active": true, "roles": ["secretary"], "permissions": ["..."] },
    "clinic_id": 1,
    "credentials_sent": true,
    "note": "Credentials were sent to the staff email"
  }
}
```

- **Errors:** `403` not allowed to create staff; `422` email already exists / invalid role.

## 6. Response Shape

All endpoints use the shared `ApiResponse` wrapper:

```json
// success
{ "success": true,  "status_code": 200, "message": "...", "data": { } }

// error
{ "success": false, "status_code": 422, "message": "...", "code": "VALIDATION_ERROR", "errors": { "email": ["..."] } }
```

`meta` is appended only when provided (not used by Authentication). Validation errors put a `field => [messages]` map in `errors`. Business errors raised as HTTP exceptions have `code: null` and `errors: null`.

## 7. Status Values

The Authentication module has no entity statuses, but the **HTTP status 423** is effectively a state: "password reset required / account locked". Treat it as a distinct UI state, not a generic error.

| Signal | Meaning | Frontend behavior |
|---|---|---|
| 401 `INVALID_CREDENTIALS` | Wrong credentials | Show inline error |
| 423 `PASSWORD_RESET_REQUIRED` | Account locked | Redirect to forgot-password flow with explanation |
| 403 `ACCOUNT_DEACTIVATED` | Account disabled | Show "contact administrator" |

## 8. Frontend Action Flags

Not applicable — this module returns no `can_*` flags.

## 9. Cross-Module Integration Notes

- The token returned by `/login` must be sent as `Authorization: Bearer <token>` to **all** modules.
- `staff.roles` and `staff.permissions` from the login response are the source of truth for showing/hiding navigation and buttons (RolesPermissions module documents the full permission list).
- `clinic_id` identifies the staff member's clinic context.
- Staff created via `POST /staff` immediately get a role through the RolesPermissions system.

## 10. Frontend Flows

**Login flow**
1. `POST /api/auth/login`.
2. On 200: store token + expiry + staff (roles/permissions); route to role-based dashboard.
3. On 423: route to forgot-password flow. On 403: show deactivated screen.

**Logout flow**
1. `POST /api/auth/logout`; clear local storage regardless of response; redirect to login.

**Token expiry handling**
- Any API call may return 401 (`UNAUTHORIZED`) when the token expires. Implement a global interceptor: on 401, clear the session and redirect to login. Optionally pre-empt using `token_expires_at`.

**Forgot password flow**
1. `send-code` → show OTP input + resend countdown (`resend_available_at`).
2. `verify-otp` → keep `reset_token` in memory only (valid 15 min).
3. `reset` → on success route to login.

**Create staff (admin)**
1. Load roles/permissions lists from RolesPermissions module to populate the form.
2. `POST /api/auth/staff`; show "credentials sent by email" confirmation.

## 11. Form-Data Examples

All Authentication endpoints are pure JSON — no multipart requests.

## 12. Error Handling

| Code | When |
|---|---|
| 401 | Bad credentials; expired/missing token |
| 403 | Deactivated account; non-admin calling `/staff` |
| 404 | Staff not found during password reset (edge case) |
| 422 | Validation errors; wrong OTP; expired OTP; invalid reset token; same-as-current password; CAPTCHA failure; duplicate email |
| 423 | Password reset required (account locked) |
| 429 | OTP resend requested too soon |
| 500 | Unexpected server error (`code: "SERVER_ERROR"`) |

## 13. Frontend Notes

- **On app start:** if a token exists, you may validate it lazily (first failing call returns 401). There is no dedicated `GET /me` endpoint in this module — cache the `staff` object from login.
- **Cache:** token, staff object, roles, permissions, clinic_id.
- **Do not hardcode:** role names beyond the four known roles; permission strings should come from the login payload / permissions endpoint.
- **Nullable fields:** `staff.phone`, `clinic_id` can be `null`.
- **Confirmation modals:** logout (optional), staff creation with permission overrides (recommended).
- **multipart:** none.
- **Language:** send `Accept-Language: ar` to receive Arabic messages; all `message` strings are translated server-side.

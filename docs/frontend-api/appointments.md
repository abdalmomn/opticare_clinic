# Appointments Module — Frontend API Documentation

## 1. Module Overview

The Appointments module manages booking and the in-clinic patient flow: book → confirm → check-in (queue) → start examination → complete, plus cancellation and doctor assignment.

- **Actors:**
  - **Secretary:** creates/updates/confirms/cancels appointments, checks patients in, assigns doctors, starts/completes flow states.
  - **Doctor:** views appointments (especially "my today list"); the examination itself is handled in MedicalRecords (visit session).
  - **Admin:** view access.
- **Key concepts:**
  - `appointment_at` (datetime) is split server-side into `appointment_date` + `appointment_time`.
  - **Queue:** on check-in the appointment gets a per-day `queue_number` and enters the waiting queue.
  - **Types:** `consultation`, `follow_up`, `imaging`, `consultation_and_imaging`, `surgery_preparation`.
- **Connections:**
  - Requires an existing, active patient (Patients module).
  - An appointment with status `in_progress` is the entry point for opening a **visit session** in MedicalRecords; finalizing the visit auto-completes the appointment.
  - Imaging requests can reference `appointment_id`.

## 2. Base Route Prefix

```
/api/appointments
```

All endpoints require `auth:sanctum` (`Authorization: Bearer <token>`).

## 3. Authentication and Permissions

Checks are service-level; failures return 403 with a translated message.

| Endpoint | Permission |
|---|---|
| `GET /`, `/today`, `/queue`, `/doctor/today`, `GET /{id}` | `view appointments` |
| `POST /` (create), `POST /{id}` (update) | `create appointment` |
| `POST /{id}/confirm` | `confirm appointment` |
| `POST /{id}/cancel` | `cancel appointment` |
| `POST /{id}/check-in`, `/{id}/start`, `/{id}/complete` | `manage patient status` |
| `POST /{id}/assign-doctor` | `assign patient to doctor` |

By default seeding: all roles can view; create/confirm/cancel/check-in/start/complete/assign are **secretary** permissions.

## 4. API Endpoint List

| Method | URL | Purpose | Auth | Permission | Request | Main response |
|---|---|---|---|---|---|---|
| GET | `/api/appointments` | List with filters | Yes | view appointments | query | `items` + `pagination` |
| GET | `/api/appointments/today` | Today's appointments | Yes | view appointments | query | `items` + `pagination` |
| GET | `/api/appointments/queue` | Waiting-room queue | Yes | view appointments | query | `items` + `pagination` |
| GET | `/api/appointments/doctor/today` | Logged-in doctor's today list | Yes | view appointments | query | `items` + `pagination` |
| POST | `/api/appointments` | Create appointment | Yes | create appointment | JSON | `appointment` |
| GET | `/api/appointments/{id}` | Appointment details | Yes | view appointments | path | `appointment` |
| POST | `/api/appointments/{id}` | Update appointment | Yes | create appointment | JSON | `appointment` |
| POST | `/api/appointments/{id}/confirm` | booked → confirmed | Yes | confirm appointment | — | `appointment` |
| POST | `/api/appointments/{id}/cancel` | → cancelled | Yes | cancel appointment | JSON | `appointment` |
| POST | `/api/appointments/{id}/check-in` | → waiting (+ queue number) | Yes | manage patient status | JSON | `appointment` |
| POST | `/api/appointments/{id}/assign-doctor` | Set doctor | Yes | assign patient to doctor | JSON | `appointment` |
| POST | `/api/appointments/{id}/start` | waiting → in_progress | Yes | manage patient status | — | `appointment` |
| POST | `/api/appointments/{id}/complete` | in_progress → completed | Yes | manage patient status | JSON | `appointment` |

> Update uses `POST /{id}` (not PUT/PATCH). Static routes (`/today`, `/queue`, `/doctor/today`) must be called exactly as written.

## 5. Detailed API Documentation

### 5.1 GET `/api/appointments` (and `/today`, `/queue`, `/doctor/today`)

- **Query parameters (all optional, shared by the four list endpoints):**

| Param | Rules |
|---|---|
| `date` | date (single day) |
| `date_from` / `date_to` | date range; `date_to` ≥ `date_from` |
| `status` | `booked,confirmed,waiting,in_progress,completed,cancelled,no_show` |
| `type` | `consultation,follow_up,imaging,consultation_and_imaging,surgery_preparation` |
| `patient_id` | int, must exist |
| `doctor_id` | int, must exist |
| `keyword` | string ≤255 |
| `per_page` | 1–100 (default 15) |

- `/today` restricts to today's date; `/queue` returns the checked-in waiting flow; `/doctor/today` returns today's appointments for the **authenticated** doctor only.
- **Success 200:** `data: { "items": [ <appointment>, ... ], "pagination": { ... } }`.

**Appointment object (model JSON with `patient` and `doctor` relations loaded):**

```json
{
  "id": 31,
  "patient_id": 12,
  "patient": { "id": 12, "full_name": "Sara Ali Khalil", "medical_file_number": "MF-000012", "phone": "0991112233", "national_id": "01234567890", "passport_id": null },
  "doctor_id": 5,
  "doctor": { "id": 5, "name": "Dr. Sami" },
  "appointment_at": "2026-06-12T10:30:00.000000Z",
  "appointment_date": "2026-06-12",
  "appointment_time": "10:30:00",
  "type": "consultation",
  "status": "waiting",
  "queue_number": 4,
  "reason": "blurred vision",
  "notes": null,
  "cancel_reason": null,
  "completion_notes": null,
  "confirmed_at": "2026-06-12T08:00:00.000000Z",
  "cancelled_at": null,
  "checked_in_at": "2026-06-12T10:10:00.000000Z",
  "started_at": null,
  "completed_at": null,
  "created_at": "2026-06-10T09:00:00.000000Z",
  "updated_at": "2026-06-12T10:10:00.000000Z"
}
```

### 5.2 POST `/api/appointments` (create)

- **Body:**

```json
{
  "patient_id": 12,
  "doctor_id": 5,
  "appointment_at": "2026-06-15 10:30:00",
  "type": "consultation",
  "reason": "blurred vision",
  "notes": "prefers morning"
}
```

- **Validation:** `patient_id` required/exists; `doctor_id` nullable/exists in staff; `appointment_at` required date, **must be now or future**; `type` required (enum above); `reason` ≤1000; `notes` ≤2000.
- **Business errors (422):** patient deceased / archived / inactive; selected staff is not a doctor. **404:** patient not found.
- **Success 201:** `data: { "appointment": { ... , "status": "booked" } }`.

### 5.3 POST `/api/appointments/{id}` (update)

- Fields all optional: `doctor_id` (nullable), `appointment_at` (future), `type`, `reason`, `notes`.
- **422:** cannot update a `cancelled` or `completed` appointment; staff is not a doctor.

### 5.4 POST `/api/appointments/{id}/confirm`

- No body. Only allowed from `booked`; otherwise **422 invalid status transition**.
- Sets `status: "confirmed"`, `confirmed_at`, `confirmed_by`.

### 5.5 POST `/api/appointments/{id}/cancel`

- **Body:** `{ "cancel_reason": "patient request" }` — required, ≤1000.
- Allowed from `booked`, `confirmed`, `waiting`; otherwise **422 cannot cancel**.
- Sets `status: "cancelled"`, `cancelled_at`, `cancelled_by`, `cancel_reason`.

### 5.6 POST `/api/appointments/{id}/check-in`

- **Body:** `{ "notes": "arrived early" }` — optional ≤2000.
- Allowed from `booked` or `confirmed`; otherwise **422**.
- Sets `status: "waiting"`, assigns the next `queue_number` for the day, `checked_in_at`.

### 5.7 POST `/api/appointments/{id}/assign-doctor`

- **Body:** `{ "doctor_id": 5 }` — required, must exist and have the doctor role.
- **422:** cannot assign on `cancelled`/`completed`; staff is not a doctor.

### 5.8 POST `/api/appointments/{id}/start`

- No body. Only from `waiting`, and **a doctor must already be assigned** (422 `cannot start without doctor` otherwise).
- Sets `status: "in_progress"`, `started_at`.
- After this, the doctor can open the visit session in MedicalRecords.

### 5.9 POST `/api/appointments/{id}/complete`

- **Body:** `{ "completion_notes": "..." }` — optional ≤2000.
- Only from `in_progress`; otherwise **422**.
- Sets `status: "completed"`, `completed_at`.
- Note: finalizing a visit session in MedicalRecords **auto-completes** the appointment — the frontend usually doesn't need to call this manually for examined patients.

## 6. Response Shape

Standard `ApiResponse` wrapper. Lists return `{ items, pagination }` with the shared pagination block:

```json
{ "current_page": 1, "per_page": 15, "total": 100, "last_page": 7, "from": 1, "to": 15, "has_more": true }
```

Single operations return `data: { "appointment": { ... } }`.

## 7. Status Values and Frontend Behavior

| Status | Meaning | Badge | Allowed actions |
|---|---|---|---|
| `booked` | Created, not confirmed | blue "Booked" | confirm, cancel, check-in, update, assign doctor |
| `confirmed` | Confirmed by secretary | teal "Confirmed" | cancel, check-in, update, assign doctor |
| `waiting` | Checked in, in queue (has `queue_number`) | yellow "Waiting" | start (needs doctor), cancel, update, assign doctor |
| `in_progress` | Examination running | purple "In progress" | complete (or finalize visit in MedicalRecords), update, assign doctor |
| `completed` | Done | green "Completed" | view only |
| `cancelled` | Cancelled (has `cancel_reason`) | red "Cancelled" | view only |
| `no_show` | Patient did not arrive | gray "No-show" | **filter value only — no endpoint currently sets this status** |

**Transition map (implemented):**

```
booked ──confirm──▶ confirmed
booked/confirmed ──check-in──▶ waiting ──start──▶ in_progress ──complete──▶ completed
booked/confirmed/waiting ──cancel──▶ cancelled
```

Any other transition returns **422** with a translated "invalid status transition"/"cannot cancel/update" message.

## 8. Frontend Action Flags

Appointment responses do **not** include `can_*` flags. Derive availability from the status table (§7) plus the user's permissions. Treat 422 responses on transition endpoints as "state changed elsewhere" → re-fetch the appointment.

## 9. Cross-Module Integration Notes

- **Patients:** booking validates patient status; show patient status in the booking dialog.
- **MedicalRecords:** when status is `in_progress`, the doctor's UI should offer "Open visit session" → `POST /api/medical-records/appointments/{appointment}/visit-session`. Finalizing the visit completes the appointment automatically.
- **Imaging:** imaging requests may carry `appointment_id`; mismatched patient/appointment combinations are rejected by Imaging with 422.

## 10. Frontend Flows

**Secretary creates an appointment**
1. Search patient (`GET /api/patient/search`).
2. `POST /api/appointments` with patient, optional doctor, datetime, type.
3. List refresh; new row shows `booked`.

**Reception day flow**
1. Page load: `GET /api/appointments/today`.
2. Patient arrives → `POST /{id}/check-in` (gets `queue_number`); queue screen polls `GET /api/appointments/queue`.
3. Doctor free → ensure doctor assigned (`/assign-doctor`) → `POST /{id}/start`.
4. After the doctor finalizes the visit, status becomes `completed` automatically; otherwise secretary calls `/complete`.

**Doctor's view**
1. Page load: `GET /api/appointments/doctor/today`.
2. For `in_progress` rows, deep-link into the MedicalRecords visit session screen.

**Cancellation**
- Modal asking for `cancel_reason` (required) → `POST /{id}/cancel`.

## 11. Form-Data Examples

All endpoints are JSON-only.

## 12. Error Handling

| Code | When |
|---|---|
| 401 | Missing/expired token |
| 403 | Missing permission for the action |
| 404 | Appointment / patient / doctor not found |
| 422 | Validation (past `appointment_at`, bad enum); patient deceased/archived/inactive; staff not a doctor; invalid status transition; cannot cancel/update completed-cancelled; cannot start without doctor |
| 500 | Unexpected error |

## 13. Frontend Notes

- **Page load:** `/today` for reception dashboard, `/queue` for the waiting screen (poll every ~30s), `/doctor/today` for doctor dashboards.
- **After actions:** every transition endpoint returns the updated `appointment` — update local state from it; re-fetch the queue after check-in/start.
- **Cache:** none of these lists should be cached for long; they change in real time.
- **Do not hardcode:** doctors list (fetch staff with doctor role), appointment types and statuses should live in one constants file mirroring §7.
- **Nullable:** `doctor_id`/`doctor`, `queue_number` (until check-in), `reason`, `notes`, all `*_at` transition timestamps, `cancel_reason`, `completion_notes`.
- **Confirmation modals:** cancel (with reason input), complete, and start (recommended).
- **multipart:** none.
- **Timezone:** `appointment_at` is returned in ISO/UTC-like server format; display in the clinic's local timezone consistently.

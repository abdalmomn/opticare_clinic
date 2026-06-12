# Imaging Module — Frontend API Documentation

## 1. Module Overview

The Imaging module manages the medical-imaging workflow: a doctor (or secretary) orders imaging for a patient, the secretary confirms payment and dispatches the request to a technician, the technician works a queue (start → upload files → complete). Doctors can also upload images **directly** (taken during examination) or register **external** images brought by the patient. The module also manages **imaging devices**, **statistics**, and an **activity log**.

- **Actors:**
  - **Doctor:** creates imaging requests, direct/external uploads, views own requests.
  - **Secretary:** creates requests on behalf of doctors, confirms/waives payment, sends requests to technicians, cancels.
  - **Imaging technician:** sees the queue, starts requests, uploads files, completes.
  - **Admin:** sees all requests, statistics, activity log, manages devices.
- **Key concepts:**
  - An **imaging request** contains one or more **requested types** (items): `image_type` + optional `eye`/`region`/`notes`. Each item is `requested` → `captured` (or `skipped`).
  - **Payment gate:** a request starts at `pending_payment`; it cannot be dispatched until payment is `confirmed` or `waived`.
  - **Queue:** dispatching creates/updates a queue entry with a queue number; technicians may be limited to **one active request at a time** (config `opticare.imaging_one_at_a_time`, default on).
  - **Sources:** `doctor_request`, `secretary_request`, `doctor_upload`, `external`. Direct/external uploads create a request that is **immediately `completed`**.
  - Every transition is recorded in the **activity log**.
- **Connections:** requests reference `patient_id`, optional `visit_record_id` and `appointment_id` (consistency enforced). Completed requests appear as **image folders** in MedicalRecords, where files are viewed/compared/annotated/attached to reports.

## 2. Base Route Prefix

```
/api/imaging
```

All endpoints require `auth:sanctum` (`Authorization: Bearer <token>`).

## 3. Authentication and Permissions

Authorization combines **policies** (ImagingPolicy) and direct permission checks:

| Endpoint | Authorization |
|---|---|
| `GET /requests` | policy `viewAny`: any of `view all imaging requests`, `view imaging requests`, `view own imaging requests`, `view imaging queue`. Results are **scoped**: doctors with only "own" see their requests; technicians see queue-relevant ones |
| `POST /requests` | policy `create`: `create imaging request` (doctor) **or** `create imaging request for patient` (secretary) |
| `GET /requests/{id}` | policy `view` (all / own / assigned-technician scoping) |
| `POST /requests/{id}/cancel` | ⚠ **No permission check enforced in the service** — only status rules apply (see §15 note). The policy method `cancel` exists (`cancel imaging request`) but is not invoked |
| `POST /requests/{id}/confirm-payment` | ⚠ **No permission check enforced** — only status rules. Intended: `confirm imaging payment` / `confirm imaging request` |
| `POST /requests/{id}/send-to-technician` | ⚠ **No permission check enforced** — only status/payment rules. Intended: `send imaging request to technician` |
| `GET /technician/requests` | policy `viewQueue`: `view imaging queue` |
| `POST /requests/{id}/start` | policy `start`: `start imaging request` + request `ready_for_imaging` + unassigned or assigned to this technician |
| `POST /requests/{id}/complete` | policy `complete`: `complete imaging request` + `in_progress` + technician scoping |
| `POST /requests/{id}/files` | policy `uploadFiles`: `upload imaging files` + `in_progress` + technician scoping |
| `DELETE /files/{id}` | policy `deleteFile`: `delete any imaging file` / `delete imaging file`, or `delete own imaging file` for own uploads |
| `POST /direct-upload` | `upload doctor imaging files` |
| `POST /external-upload` | `upload external imaging files` |
| `GET /devices`, `GET /devices/{id}` | `view devices` |
| `POST /devices` | `create device` |
| `PATCH /devices/{id}` | `edit device` |
| `PATCH /devices/{id}/toggle-status` | `toggle device status` |
| `DELETE /devices/{id}` | `delete device` |
| `GET /statistics/*` | `view statistics` |
| `GET /activity-logs` | `view activity log` |

401/403 behavior follows the global `ApiResponse` envelope (`code: "UNAUTHORIZED"` / `"FORBIDDEN"`).

## 4. API Endpoint List

| Method | URL | Purpose | Permission | Request | Main response |
|---|---|---|---|---|---|
| GET | `/api/imaging/requests` | List requests (scoped) | view (any variant) | query | `items`+`pagination` |
| POST | `/api/imaging/requests` | Create request | create imaging request (or "for patient") | JSON | `request` |
| GET | `/api/imaging/requests/{id}` | Request details | view | path | `request` |
| POST | `/api/imaging/requests/{id}/cancel` | Cancel | (status rules only) | JSON | `request` |
| POST | `/api/imaging/requests/{id}/confirm-payment` | Confirm/waive payment | (status rules only) | JSON | `request` |
| POST | `/api/imaging/requests/{id}/send-to-technician` | Dispatch to technician | (status+payment rules) | JSON | `request` |
| GET | `/api/imaging/technician/requests` | Technician queue | view imaging queue | query | `current_request` + `items` |
| POST | `/api/imaging/requests/{id}/start` | Start imaging | start imaging request | — | `request` |
| POST | `/api/imaging/requests/{id}/complete` | Complete imaging | complete imaging request | — | `request` |
| POST | `/api/imaging/requests/{id}/files` | Upload files to request | upload imaging files | **form-data** | `request` + `files` |
| DELETE | `/api/imaging/files/{id}` | Delete a file | delete (own/any) imaging file | path | `file {id, deleted}` |
| POST | `/api/imaging/direct-upload` | Doctor direct upload | upload doctor imaging files | **form-data** | `request` + `files` |
| POST | `/api/imaging/external-upload` | External images upload | upload external imaging files | **form-data** | `request` + `files` |
| GET | `/api/imaging/devices` | List devices | view devices | query | `items`+`pagination` |
| POST | `/api/imaging/devices` | Create device | create device | JSON | `device` |
| GET | `/api/imaging/devices/{id}` | Device details | view devices | path | `device` |
| PATCH | `/api/imaging/devices/{id}` | Update device | edit device | JSON | `device` |
| PATCH | `/api/imaging/devices/{id}/toggle-status` | Toggle active/offline | toggle device status | — | `device` |
| DELETE | `/api/imaging/devices/{id}` | Delete (or retire) device | delete device | path | `deleted`, `retired_instead`, `device` |
| GET | `/api/imaging/statistics/overview` | Totals & breakdowns | view statistics | query | stats object |
| GET | `/api/imaging/statistics/by-device` | Per-device stats | view statistics | query | `items` |
| GET | `/api/imaging/statistics/by-type` | Per-image-type stats | view statistics | query | `items` |
| GET | `/api/imaging/activity-logs` | Audit log | view activity log | query | `items`+`pagination` |

## 5. Detailed API Documentation

### 5.1 The imaging request object (returned by all request endpoints)

```json
{
  "id": 70,
  "status": "pending_payment",
  "status_label": "Pending Payment",
  "payment_status": "pending",
  "payment_status_label": "Pending",
  "priority": "normal",
  "request_type": "OCT macula OD + Fundus",
  "source": "doctor_request",
  "patient": { "id": 12, "full_name": "Sara Ali Khalil", "birth_date": "1990-04-02", "medical_file_number": "MF-000012" },
  "requested_by": { "id": 5, "name": "Dr. Sami" },
  "room": { "id": 2, "name": "Imaging Room 1" },
  "visit_record_id": 41,
  "appointment_id": 31,
  "requested_types": [
    { "id": 91, "image_type": "OCT", "eye": "OD", "region": "macula", "notes": null, "status": "requested", "status_label": "Requested" }
  ],
  "payment": { "status": "pending", "status_label": "Pending", "confirmed_at": null, "confirmed_by": null },
  "technician": null,
  "timestamps": {
    "created_at": "2026-06-12T10:50:00.000000Z",
    "payment_confirmed_at": null,
    "sent_to_technician_at": null,
    "started_at": null,
    "completed_at": null,
    "cancelled_at": null
  },
  "notes": null,
  "cancel_reason": null,
  "files": [ { "...": "present on detail/upload responses when files are loaded" } ],
  "files_count": 0
}
```

**File object inside `files`:**

```json
{
  "id": 101,
  "label": "macula OD",
  "image_type": "OCT",
  "modality": "OCT",
  "eye": "OD",
  "region": "macula",
  "file_name": "scan1.png",
  "file_url": "https://host/storage/imaging/12/70/uuid.png",
  "thumbnail_url": null,
  "file_size": 524288,
  "mime_type": "image/png",
  "captured_at": "2026-06-12T11:00:00.000000Z",
  "uploaded_at": "2026-06-12T11:01:00.000000Z",
  "source": "technician_upload",
  "is_primary": false,
  "imaging_request_item_id": 91,
  "device": { "id": 3, "name": "Topcon OCT", "device_identifier": "OCT-01", "type": "OCT" },
  "uploaded_by": { "id": 8, "name": "Tech Omar" }
}
```

> ⚠ Imaging request responses currently **do not include `can_*` action flags** (a `formatActions` helper exists in the backend but is not wired into the payload). Derive action availability from `status` + `payment_status` + the user's permissions (see §7/§8).

### 5.2 GET `/api/imaging/requests`

- **Query filters (all optional):** `status` (`requested,pending_payment,payment_confirmed,ready_for_imaging,in_progress,completed,cancelled,pending,canceled` — the last two are legacy aliases), `payment_status` (`pending,confirmed,waived,refunded`), `patient_id`, `requested_by`, `technician_id`, `priority` (`normal,urgent`), `date_from`, `date_to`, `search` (≤255), `per_page` (1–100), `page`.
- **Success 200:** `data: { items: [<request>], pagination: {...} }`.
- Scoping: doctors with only `view own imaging requests` get only their own; technicians with only `view imaging queue` get queue-visible ones.

### 5.3 POST `/api/imaging/requests` (create)

- **Body (JSON):**

```json
{
  "patient_id": 12,
  "visit_record_id": 41,
  "appointment_id": 31,
  "requested_by": null,
  "room_id": 2,
  "notes": "check macula",
  "priority": "urgent",
  "requested_types": [
    { "image_type": "OCT", "eye": "OD", "region": "macula", "notes": null },
    { "image_type": "Fundus", "eye": "OU", "region": null, "notes": null }
  ]
}
```

- **Validation:** `patient_id` required/exists; `visit_record_id`, `appointment_id`, `requested_by`, `room_id` nullable/exist; `priority` `normal|urgent`; `requested_types` required array min 1; each item: `image_type` required ≤100, `eye` ≤20, `region` ≤100, `notes` ≤2000.
- **Behavior:** `source` and `requested_by` are derived from the caller's permissions — a secretary (has "for patient" permission only) creates `secretary_request` and may set `requested_by` to a doctor id; a doctor always becomes the `requested_by` themself. Initial status: **`pending_payment`**, payment `pending`.
- **Errors:** 404 patient/visit/appointment/`requested_by` not found; **422** visit↔patient mismatch, appointment↔patient mismatch, visit↔appointment conflict; 403 no create permission.
- **Success 201:** `data: { request: {...} }`.

### 5.4 GET `/api/imaging/requests/{id}`

- Detail incl. `files`. 404 if missing; 403 if outside the caller's view scope.

### 5.5 POST `/api/imaging/requests/{id}/cancel`

- **Body:** `{ "reason": "patient declined" }` (nullable ≤1000).
- **Allowed only before work starts** — 422 `cannot cancel` when status is `in_progress`, `completed`, or already `cancelled`.
- **Success 200:** updated `request` (status `cancelled`, `cancel_reason` set).

### 5.6 POST `/api/imaging/requests/{id}/confirm-payment`

- **Body:**

```json
{ "invoice_item_id": null, "waive": false, "notes": null }
```

- `waive: true` marks payment as `waived` instead of `confirmed`. `invoice_item_id` optionally links a Payments-module invoice item.
- **Precondition:** status must be `pending_payment` (legacy `pending` is normalized) — otherwise **422 cannot confirm payment**.
- **Success 200:** request with `status: "payment_confirmed"`, `payment.confirmed_at/confirmed_by` filled.

### 5.7 POST `/api/imaging/requests/{id}/send-to-technician`

- **Body:**

```json
{ "technician_id": 8, "room_id": 2, "priority": "urgent" }
```

- `technician_id` required/exists; validated to actually be a technician (must hold `view imaging queue`) — otherwise **422 not a technician**.
- **Preconditions:** status `payment_confirmed` (or already `ready_for_imaging` for re-dispatch) — else **422 cannot dispatch**; payment must be `confirmed`/`waived` — else **422 payment not confirmed**.
- **Effect:** status → `ready_for_imaging`; queue entry created/updated (`dispatched`, queue number assigned).
- **Success 200:** updated `request` with `technician` filled.

### 5.8 GET `/api/imaging/technician/requests` (queue)

- **Query:** `priority`, `patient_id`, `date_from`, `date_to`, `search`.
- **Success 200:**

```json
{
  "data": {
    "current_request": { "...": "the technician's active in_progress request, or null" },
    "items": [ { "...": "ready_for_imaging requests visible to this technician" } ]
  }
}
```

> Note: the queue response has **no `pagination` block** even though the backend paginates internally — the frontend receives the first page of items.

### 5.9 POST `/api/imaging/requests/{id}/start`

- No body. Preconditions: status `ready_for_imaging` (422 `cannot start`), and if one-at-a-time mode is on, the technician must have no other active request (**422 technician busy**). Policy also requires the request be unassigned or assigned to the caller.
- **Effect:** status → `in_progress`; technician set to the caller if unassigned; queue → `in_progress`.

### 5.10 POST `/api/imaging/requests/{id}/files` (technician upload) — multipart

- **Preconditions:** request `in_progress` (**422 cannot upload**); `device_id` must reference an **active** device (404 device not found / **422 device not active**); `metadata` count must equal `files` count (**422 metadata mismatch**); `imaging_request_item_id` (if sent) must belong to this request (**422 item mismatch**).
- **Validation:** `device_id` required; `files[]` required, each file mime `jpg,jpeg,png,gif,bmp,webp,tif,tiff,pdf`, max size from config (default 20480 KB = 20 MB); `metadata[]` required, `metadata[i].image_type` required ≤100; optional `modality`, `eye`, `region`, `image_label`, `captured_at` (date), `imaging_request_item_id`, `is_primary` (bool).
- **Effect:** files stored under `storage/imaging/{patient_id}/{request_id}/`; matched request items flip to `captured`.
- **Success 201:** `data: { request: {...with files}, files: [<file>] }`.
- See §11 for the form-data layout.

### 5.11 POST `/api/imaging/requests/{id}/complete`

- No body. Preconditions: status `in_progress` (**422 cannot complete**) and **at least one uploaded file** (**422 no files uploaded**).
- **Effect:** status → `completed`; queue → `completed`. The request now appears as an image folder in MedicalRecords.

### 5.12 DELETE `/api/imaging/files/{id}`

- Deletes one imaging file (soft business delete of the DB row). Technicians can delete **their own** uploads; `delete any imaging file` / `delete imaging file` allows deleting any.
- **Success 200:** `data: { file: { id: 101, deleted: true } }`. 404 if missing; 403 if not allowed.

### 5.13 POST `/api/imaging/direct-upload` (doctor) — multipart

- **Purpose:** doctor uploads images captured during the visit without the payment/technician flow. Creates a request with `source: "doctor_upload"` that is **immediately `completed`** (payment `waived`).
- **Validation:** `patient_id` required; `visit_record_id`/`appointment_id` nullable (consistency enforced, same 422s as create); `notes` ≤2000; `files[]` + `metadata[]` as in 5.10, metadata may also include `device_id` (must be an **active** device when provided) and `is_primary`.
- **Success 201:** `data: { request, files }`.

### 5.14 POST `/api/imaging/external-upload` — multipart

- **Purpose:** register images taken **outside the clinic** (patient brings them). Same shape as direct-upload, but `source: "external"`, devices are not required to be active/registered, and metadata accepts a free-text `device_name` (≤255) instead of/in addition to `device_id`.
- **Success 201:** `data: { request, files }`.

### 5.15 Devices

**GET `/devices`** — query: `status` (`active,maintenance,offline,retired`), `room_id`, `device_type` (≤100), `search`, `per_page`, `page`. Returns `items` + `pagination`.

**Device object:**

```json
{
  "id": 3,
  "name": "Topcon OCT",
  "device_identifier": "OCT-01",
  "serial_number": "SN12345",
  "device_type": "OCT",
  "manufacturer": "Topcon",
  "model": "Maestro2",
  "status": "active",
  "room": { "id": 2, "name": "Imaging Room 1" },
  "last_maintenance_at": "2026-05-01",
  "notes": null,
  "created_by": { "id": 1, "name": "Admin" },
  "updated_by": { "id": 1, "name": "Admin" },
  "created_at": "2026-01-01T08:00:00.000000Z",
  "updated_at": "2026-05-01T08:00:00.000000Z"
}
```

**POST `/devices`** — body: `name` (required ≤255), `device_type` (required ≤100), optional `device_identifier` (unique), `serial_number`, `manufacturer`, `model`, `room_id`, `status` (defaults `active`), `last_maintenance_at` (date), `notes` (≤2000). 201 → `{ device }`.

**PATCH `/devices/{id}`** — same fields, all optional (`sometimes`). 200 → `{ device }`.

**PATCH `/devices/{id}/toggle-status`** — flips `active` ⇄ `offline`. **422 cannot toggle a `retired` device.** Note: a device in `maintenance` toggles to `active`.

**DELETE `/devices/{id}`** — if the device is referenced by any imaging file it is **retired instead of deleted**:

```json
{ "deleted": false, "retired_instead": true, "device": { "...status": "retired" } }
```

otherwise `{ "deleted": true, "retired_instead": false, "device": null }`. The success `message` differs accordingly — show it to the user.

### 5.16 Statistics

Shared query filters: `date_from`, `date_to`, `doctor_id`, `technician_id`, `room_id`, `device_id`, `source` (`doctor_request,secretary_request,doctor_upload,external`).

**GET `/statistics/overview`:**

```json
{
  "totals": { "requests": 120, "completed": 90, "cancelled": 5, "pending_payment": 10, "payment_confirmed": 5, "ready_for_imaging": 4, "in_progress": 6, "files": 480 },
  "by_status":   [ { "status": "completed", "count": 90 } ],
  "by_source":   [ { "source": "doctor_request", "count": 70 } ],
  "by_priority": [ { "priority": "normal", "count": 100 } ],
  "by_day":      [ { "day": "2026-06-01", "count": 8 } ]
}
```

**GET `/statistics/by-device`:** `items: [ { device {id,name,device_identifier,type}, files_count, requests_count, last_upload_at } ]`

**GET `/statistics/by-type`:** `items: [ { image_type, files_count, requests_count } ]`

### 5.17 GET `/activity-logs`

- **Query:** `imaging_request_id`, `imaging_file_id`, `actor_id`, `action`, `date_from`, `date_to`, `per_page`, `page`.
- **Actions enum:** `request_created`, `request_cancelled`, `payment_confirmed`, `payment_waived`, `sent_to_technician`, `started`, `file_uploaded`, `file_deleted`, `completed`, `direct_upload_created`, `external_upload_created`, `device_created`, `device_updated`, `device_activated`, `device_deactivated`, `device_deleted_or_retired`.
- **Item:** `{ id, action, imaging_request_id, imaging_file_id, actor {id,name}, from_status, to_status, metadata, created_at }` + standard pagination.

## 6. Response Shape

Standard `ApiResponse` wrapper; paginated lists use the shared pagination block (`current_page`, `per_page`, `total`, `last_page`, `from`, `to`, `has_more`). Single operations return `data: { request | device | file ... }`.

## 7. Status Values and Frontend Behavior

**Imaging request status** (`status` + human `status_label`):

| Status | Meaning | Badge | Allowed actions (by role) |
|---|---|---|---|
| `pending_payment` (legacy alias `pending`) | Created, awaiting payment | orange "Pending Payment" | secretary: confirm-payment, cancel; doctor: cancel |
| `payment_confirmed` | Paid or waived | blue "Payment Confirmed" | secretary: send-to-technician, cancel |
| `ready_for_imaging` | Dispatched, in queue | teal "Ready For Imaging" | technician: start; secretary: re-dispatch (send-to-technician), cancel |
| `in_progress` | Technician working | purple "In Progress" | technician: upload files, complete. **No cancel** |
| `completed` | Files delivered | green "Completed" | view; appears in MedicalRecords as folder |
| `cancelled` (legacy alias `canceled`) | Cancelled | red "Cancelled" | view only |

Transition map:

```
pending_payment ──confirm-payment──▶ payment_confirmed ──send-to-technician──▶ ready_for_imaging ──start──▶ in_progress ──complete──▶ completed
pending_payment / payment_confirmed / ready_for_imaging ──cancel──▶ cancelled
(direct-upload / external-upload create requests directly in `completed`)
```

**Payment status:** `pending` → `confirmed` or `waived` (`refunded` exists as a value/filter but no endpoint sets it).

**Requested-type item status:** `requested` (gray) → `captured` (green, set automatically on upload) / `skipped` (defined but no endpoint sets it).

**Queue status (internal, may appear in queue data):** `waiting`, `dispatched`, `in_progress`, `completed`, `cancelled`.

**Device status:** `active` (usable for uploads), `maintenance`, `offline`, `retired` (terminal; cannot toggle). Only **active** devices can be selected for technician/direct uploads.

**Priority:** `normal` | `urgent` — show urgent rows highlighted and sort them first in queue UIs.

## 8. Frontend Action Flags

⚠ **Imaging responses currently include no `can_*` flags.** Until the backend adds them, the frontend must derive availability from:

1. `status` / `payment_status` (table in §7), and
2. the user's permission list (e.g. show "Confirm payment" only with `confirm imaging payment`).

When the backend later wires its existing `formatActions` (`can_cancel`, `can_confirm_payment`, `can_send_to_technician`, `can_start`, `can_upload`, `can_complete`) into the payload, switch to those flags and delete the local logic. Always handle 403/422 as "state changed elsewhere" → re-fetch.

## 9. Cross-Module Integration Notes

- **Patients:** `patient_id` everywhere; patient summary embedded in the request object.
- **Appointments / MedicalRecords:** pass the current `visit_record_id` and/or `appointment_id` when creating requests or uploading from a visit — mismatches return 422.
- **MedicalRecords:** completed requests = image folders (`/api/medical-records/patients/{p}/image-folders`); files become viewable/comparable/attachable there. Doctor notes on images live in MedicalRecords (`POST /api/medical-records/imaging-files/{file}/notes`).
- **Payments:** `invoice_item_id` in confirm-payment links to the Payments module (optional).
- **RolesPermissions:** all gating permissions listed in §3.

## 10. Frontend Flows

**Doctor orders imaging (during a visit)**
1. In the visit screen: `POST /api/imaging/requests` with `patient_id`, `visit_record_id`, `requested_types`.
2. Show the created request with status "Pending Payment"; doctor's "My requests" list = `GET /requests` (auto-scoped).

**Secretary processes a request**
1. Dashboard: `GET /requests?status=pending_payment`.
2. Confirm payment modal (`waive` checkbox, optional invoice item) → `POST /requests/{id}/confirm-payment`.
3. Dispatch modal (pick technician from staff with technician role, room, priority) → `POST /requests/{id}/send-to-technician`.
4. Cancel with optional reason at any pre-`in_progress` stage.

**Technician works the queue**
1. Page load: `GET /technician/requests` → render `current_request` banner + queue list; poll periodically.
2. `POST /requests/{id}/start` (handle 422 "technician busy" by pointing at `current_request`).
3. Upload screen: pick an **active** device (`GET /devices?status=active`), attach files with per-file metadata → `POST /requests/{id}/files` (multipart). Repeat as needed; wrong file → `DELETE /files/{id}`.
4. `POST /requests/{id}/complete` (requires ≥1 file).

**Doctor direct upload** — one multipart call to `/direct-upload`; resulting folder is immediately visible in MedicalRecords.

**External upload** — same via `/external-upload`, with free-text `device_name` per file.

**Device management (admin)** — list/create/edit/toggle/delete as in §5.15; explain "retired instead of deleted" in the UI when `retired_instead: true`.

**Statistics & logs (admin)** — dashboard calls the three `/statistics/*` endpoints with a shared date filter; audit screen pages through `/activity-logs`.

## 11. Form-Data Examples (Postman-ready)

### Create request — JSON (not form-data), shown here as bracket notation for reference

```
patient_id = 12
requested_types[0][image_type] = OCT
requested_types[0][eye] = OD
requested_types[0][region] = macula
requested_types[1][image_type] = Fundus
requested_types[1][eye] = OU
priority = urgent
```

### Technician upload — `POST /api/imaging/requests/{id}/files` (multipart/form-data)

```
device_id                       = 3
files[0]                        = (file) scan1.png
files[1]                        = (file) scan2.png
metadata[0][image_type]         = OCT
metadata[0][modality]           = OCT
metadata[0][eye]                = OD
metadata[0][region]             = macula
metadata[0][image_label]        = Macula OD
metadata[0][captured_at]        = 2026-06-12 11:00:00
metadata[0][imaging_request_item_id] = 91
metadata[0][is_primary]         = 1
metadata[1][image_type]         = OCT
metadata[1][eye]                = OS
metadata[1][region]             = macula
```

> `files` and `metadata` must have the **same count and order**: `metadata[i]` describes `files[i]`.

### Doctor direct upload — `POST /api/imaging/direct-upload` (multipart/form-data)

```
patient_id                = 12
visit_record_id           = 41
appointment_id            = 31
notes                     = taken during exam
files[0]                  = (file) anterior.jpg
metadata[0][image_type]   = Slit Lamp
metadata[0][eye]          = OD
metadata[0][device_id]    = 4
metadata[0][is_primary]   = 1
```

### External upload — `POST /api/imaging/external-upload` (multipart/form-data)

```
patient_id                 = 12
files[0]                   = (file) external_oct.pdf
metadata[0][image_type]    = OCT
metadata[0][eye]           = OU
metadata[0][device_name]   = External Lab Device
metadata[0][captured_at]   = 2026-06-01
```

Allowed file types: `jpg, jpeg, png, gif, bmp, webp, tif, tiff, pdf`. Max size per file: 20 MB by default (config `opticare.imaging_max_upload_kb`).

## 12. Error Handling

| Code | When |
|---|---|
| 401 | Missing/expired token |
| 403 | Policy denial: view scope, create, start/complete/upload by wrong technician, delete file, devices/statistics/logs permissions, direct/external upload permissions |
| 404 | Request / file / device / patient / visit / appointment / technician not found |
| 422 | Validation; **cannot cancel** (in progress/completed/cancelled); **cannot confirm payment** (not pending_payment); **cannot dispatch** (wrong status); **payment not confirmed**; **not a technician**; **cannot start** (wrong status); **technician busy**; **cannot upload** (request not in progress); **device not active**; **metadata mismatch** (count ≠ files); **item mismatch**; **cannot complete** (wrong status); **no files uploaded**; **cannot toggle retired device**; patient/visit/appointment mismatch |
| 500 | Unexpected error (file storage failure also cleans up already-stored files) |

## 13. Frontend Notes

- **Page load:** secretary board → `GET /requests?status=...`; technician → `GET /technician/requests` (+ poll); admin → statistics endpoints.
- **After actions:** every transition returns the updated `request` — update state from it; refresh the queue after start/complete.
- **Cache:** device list (changes rarely; refresh after device mutations). Never cache the queue.
- **Do not hardcode:** statuses/labels come with the payload (`status_label`, `payment_status_label`); image types are free strings — build pickers from `GET /api/medical-records/patients/{p}/image-types` or your own catalog; technician/doctor lists come from staff data.
- **Nullable fields:** `technician`, `room`, `visit_record_id`, `appointment_id`, `requested_by`, `notes`, `cancel_reason`, `thumbnail_url`, `device` (on files), all `timestamps.*` except `created_at`, `files` (only present when loaded).
- **Confirmation modals:** cancel request, waive payment, complete request, delete file, delete/retire device, toggle device status.
- **multipart/form-data:** `/requests/{id}/files`, `/direct-upload`, `/external-upload` only. Everything else is JSON.
- **Legacy values:** treat `pending` as `pending_payment` and `canceled` as `cancelled` when filtering or mapping badges (the API normalizes them in responses).

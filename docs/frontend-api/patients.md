# Patients Module — Frontend API Documentation

## 1. Module Overview

The Patients module manages the clinic's patient registry (`clinic_patients`): registration, profile editing, search, status management (active/inactive), archiving, restoring, and marking deceased.

- **Actors:** secretary (main user), doctor, medical_center_admin, imaging technician (view/search only).
- **Key concepts:**
  - Each patient has a unique **identity** (`identity_type`: `national_id` | `passport` + `identity_number`) and an auto-generated **`medical_file_number`** (can also be supplied manually on create).
  - **Patient status lifecycle:** `active`, `inactive`, `archived`, `deceased` (see §7).
  - The patient profile carries medical background data (chronic diseases, allergies, ocular history, etc.) stored as JSON arrays.
- **Connections:** Appointments, MedicalRecords, and Imaging all reference `patient_id` from this module. Appointments cannot be created for archived/deceased/inactive patients.

## 2. Base Route Prefix

```
/api/patient
```

> Note the **singular** prefix: `/api/patient`, not `/api/patients`.

All endpoints require `auth:sanctum` (`Authorization: Bearer <token>`).

## 3. Authentication and Permissions

Permission checks are performed in the service; failures return **403** with a translated message.

| Endpoint | Permission |
|---|---|
| `GET /` (list) | `view patients` |
| `GET /search` | `search patient` |
| `GET /{patient}` | `view patients` |
| `POST /` (create) | `create patient` |
| `POST /{patient}` (update) | `edit patient` |
| `PATCH /{patient}/toggle-status` | `edit patient` |
| `PATCH /{patient}/archive` | `archive patient` |
| `PATCH /{patient}/restore` | `restore patient` |
| `PATCH /{patient}/mark-deceased` | `archive patient` |

All four roles can view/search (seeded); create/edit belongs to secretary and doctor; archive/restore to secretary, doctor, and admin.

## 4. API Endpoint List

| Method | URL | Purpose | Auth | Permission | Request | Main response |
|---|---|---|---|---|---|---|
| GET | `/api/patient` | Paginated patient list with filters | Yes | `view patients` | query | `items` + `pagination` |
| GET | `/api/patient/search` | Same filters, for search box | Yes | `search patient` | query | `items` + `pagination` |
| POST | `/api/patient` | Register patient | Yes | `create patient` | JSON | `patient` |
| GET | `/api/patient/{id}` | Patient profile | Yes | `view patients` | path | `patient` |
| POST | `/api/patient/{id}` | Update patient | Yes | `edit patient` | JSON | `patient` |
| PATCH | `/api/patient/{id}/toggle-status` | Activate/deactivate | Yes | `edit patient` | — | `patient` |
| PATCH | `/api/patient/{id}/archive` | Archive patient | Yes | `archive patient` | JSON | `patient` |
| PATCH | `/api/patient/{id}/restore` | Restore archived/inactive patient | Yes | `restore patient` | — | `patient` |
| PATCH | `/api/patient/{id}/mark-deceased` | Mark patient deceased | Yes | `archive patient` | JSON | `patient` |

> **Update uses `POST`, not `PUT`/`PATCH`.**

## 5. Detailed API Documentation

### 5.1 GET `/api/patient` and GET `/api/patient/search`

- **Purpose:** list/search patients. Both accept the same query parameters; `/search` only differs in the permission checked.
- **Query parameters:**

| Param | Rules |
|---|---|
| `keyword` | nullable string ≤255 (matches name/file number etc.) |
| `identity_number` | nullable string ≤50 |
| `phone` | nullable string ≤30 |
| `is_active` | nullable boolean (`1`/`0`) |
| `status` | nullable, one of `active`, `inactive`, `archived`, `deceased` |
| `include_archived` | nullable boolean |
| `archive_reason` | nullable, one of `no_longer_patient`, `transferred`, `duplicate`, `deceased`, `other` |
| `per_page` | nullable int 1–100 (default 15) |
| `page` | standard Laravel page param |

- **Success 200:**

```json
{
  "success": true, "status_code": 200, "message": "Patients fetched",
  "data": {
    "items": [
      {
        "id": 12,
        "medical_file_number": "MF-000012",
        "first_name": "Sara", "father_name": "Ali", "last_name": "Khalil",
        "full_name": "Sara Ali Khalil",
        "identity_type": "national_id", "identity_number": "01234567890",
        "national_id": "01234567890", "passport_id": null,
        "gender": "female", "birth_date": "1990-04-02",
        "marital_status": "married",
        "phone": "0991112233", "address": "Damascus",
        "height_cm": 165, "weight_kg": 60, "blood_type": "O+",
        "is_smoker": false, "drinks_alcohol": false,
        "chronic_diseases": ["diabetes"], "allergies": [],
        "current_medications": [], "previous_eye_surgeries": [],
        "wears_glasses_or_lenses": true, "family_ocular_history": null,
        "status": "active", "is_active": true,
        "archived_at": null, "archive_reason": null, "archive_notes": null,
        "deceased_at": null,
        "created_at": "2026-01-10T08:00:00.000000Z",
        "updated_at": "2026-06-01T08:00:00.000000Z"
      }
    ],
    "pagination": { "total": 100, "per_page": 15, "current_page": 1, "last_page": 7, "from": 1, "to": 15, "has_more": true }
  }
}
```

(Items are the raw patient model JSON; exact columns may include additional bookkeeping fields such as `created_by`, `updated_by`, `central_user_id`.)

### 5.2 POST `/api/patient` (create)

- **Body (JSON):**

```json
{
  "first_name": "Sara",
  "father_name": "Ali",
  "last_name": "Khalil",
  "identity_type": "national_id",
  "identity_number": "01234567890",
  "gender": "female",
  "date_of_birth": "1990-04-02",
  "marital_status": "married",
  "phone": "0991112233",
  "address": "Damascus",
  "height_cm": 165,
  "weight_kg": 60,
  "blood_type": "O+",
  "is_smoker": false,
  "drinks_alcohol": false,
  "chronic_diseases": ["diabetes"],
  "diabetes_details": { "type": "2" },
  "allergies": [],
  "current_medications": [],
  "previous_eye_surgeries": [],
  "wears_glasses_or_lenses": true,
  "family_ocular_history": "glaucoma (father)",
  "central_user_id": null,
  "medical_file_number": null
}
```

- **Validation highlights:**
  - Required: `first_name`, `last_name`, `identity_type` (`national_id`|`passport`), `identity_number` (≤50), `gender` (`male`|`female`).
  - `date_of_birth` nullable date, not in the future. `marital_status` in `single,married,divorced,widowed`.
  - `height_cm` 0–300, `weight_kg` 0–500; medical history fields are arrays.
  - `medical_file_number` nullable — generated automatically when omitted.
- **Success 201:** `data: { "patient": { ... } }` with computed `full_name`, `status: "active"`, `is_active: true`.
- **Business error 422:** identity already exists (same `identity_number` + `identity_type`).

### 5.3 GET `/api/patient/{patient}`

- **Success 200:** `data: { "patient": { ... } }`.
- **404:** patient not found.

### 5.4 POST `/api/patient/{patient}` (update)

- Same fields as create, but **all optional** (`sometimes`). `medical_file_number` cannot be changed here.
- Name parts and identity fields are recomputed server-side (`full_name`, `national_id`/`passport_id` columns).
- **Errors:** 404 not found; 422 identity exists for another patient.

### 5.5 PATCH `/api/patient/{patient}/toggle-status`

- No body. Flips `is_active` and sets `status` to `active`/`inactive`.
- **422:** cannot toggle an `archived` or `deceased` patient.

### 5.6 PATCH `/api/patient/{patient}/archive`

- **Body:**

```json
{ "archive_reason": "transferred", "archive_notes": "moved to another city" }
```

- `archive_reason` required, one of `no_longer_patient`, `transferred`, `duplicate`, `other` (note: `deceased` is **not** allowed here — use mark-deceased). `archive_notes` nullable ≤2000.
- Sets `status: "archived"`, `is_active: false`, `archived_at`, `archived_by`.
- **422:** patient already archived; deceased patient cannot be archived.

### 5.7 PATCH `/api/patient/{patient}/restore`

- No body. Sets `status: "active"`, clears archive fields.
- **422:** deceased patient cannot be restored; patient is neither `archived` nor `inactive`.

### 5.8 PATCH `/api/patient/{patient}/mark-deceased`

- **Body:**

```json
{ "deceased_at": "2026-06-10", "archive_notes": "reported by family" }
```

- `deceased_at` required date, not in the future.
- Sets `status: "deceased"`, `is_active: false`, `archive_reason: "deceased"`, `deceased_at`.
- **422:** patient already marked deceased.

## 6. Response Shape

Standard `ApiResponse` (see Authentication doc §6). List endpoints return:

```json
{ "items": [], "pagination": { "current_page": 1, "per_page": 15, "total": 100, "last_page": 7, "from": 1, "to": 15, "has_more": true } }
```

Single-object endpoints return `data: { "patient": { ... } }`.

## 7. Status Values and Frontend Behavior

| Status | Meaning | Suggested badge | Allowed actions |
|---|---|---|---|
| `active` | Normal patient | green "Active" | edit, toggle→inactive, archive, mark-deceased, book appointments |
| `inactive` | Temporarily deactivated | gray "Inactive" | edit, toggle→active, restore, archive, mark-deceased. **No new appointments** |
| `archived` | Removed from active registry | orange "Archived" | restore, mark-deceased. No edits of status via toggle, no appointments |
| `deceased` | Patient deceased | dark "Deceased" | **terminal state** — no toggle, no archive, no restore, no appointments |

**Transitions implemented:**

```
active  ⇄ inactive            (toggle-status)
active/inactive → archived    (archive)
archived/inactive → active    (restore)
any except deceased → deceased (mark-deceased)
```

## 8. Frontend Action Flags

The Patients API does **not** return `can_*` flags. Derive button visibility from:
1. the user's `permissions` array (e.g. hide Archive without `archive patient`), and
2. the patient `status` table above (e.g. hide Toggle for archived/deceased).

The backend enforces both; handle 403/422 gracefully.

## 9. Cross-Module Integration Notes

- `patient_id` is the foreign key used by Appointments (`POST /api/appointments`), MedicalRecords (unified record, timelines, image folders: `/api/medical-records/patients/{patient}/...`), and Imaging (`patient_id` in imaging requests and uploads).
- Appointment creation rejects `deceased` (422), `archived` (422), and inactive (422) patients — keep patient status visible in booking UIs.
- The patient header used in MedicalRecords screens (name, file number, age, gender) comes from this module's `show` endpoint or is embedded in MedicalRecords responses.

## 10. Frontend Flows

**Create patient (secretary/doctor)**
1. Open registration form → `POST /api/patient`.
2. On 422 "identity exists", offer to search for the existing patient instead.
3. On success, navigate to the patient profile (use returned `patient.id`).

**Search/list patients**
1. Debounced `GET /api/patient/search?keyword=...` for the global search box.
2. Full list page uses `GET /api/patient` with filters + pagination; show `status` filter chips (use `include_archived=1` to include archived rows).

**Patient profile used by other modules**
1. `GET /api/patient/{id}` for the profile header.
2. Tabs: Appointments (`GET /api/appointments?patient_id=`), Medical record (`/api/medical-records/patients/{id}/unified-record`), Images (`/api/medical-records/patients/{id}/image-folders`), Imaging requests (`/api/imaging/requests?patient_id=`).

**Archive / restore / deceased**
- Always confirm with a modal; archive requires choosing a reason. After success, refresh the profile and disable booking actions per §7.

## 11. Form-Data Examples

All endpoints are JSON. Array fields in JSON bodies (e.g. `chronic_diseases`) must be sent as real JSON arrays, not comma-separated strings.

## 12. Error Handling

| Code | When |
|---|---|
| 401 | Missing/expired token |
| 403 | Missing permission (`view patients`, `create patient`, ...) |
| 404 | Patient not found |
| 422 | Validation errors; identity exists; invalid status transition (toggle archived/deceased, archive deceased, restore deceased, already archived/deceased) |
| 500 | Unexpected error |

## 13. Frontend Notes

- **Page load:** list page → `GET /api/patient`; profile → `GET /api/patient/{id}`.
- **After actions:** re-fetch the patient (all mutations return the updated `patient`, so you can update state from the response without an extra call).
- **Cache:** nothing long-lived; patient lists change often.
- **Do not hardcode:** archive reasons and statuses are fixed enums (listed above) — centralize them in one constants file mirroring this doc.
- **Nullable fields to guard:** `father_name`, `date_of_birth`/`birth_date`, `marital_status`, `phone`, `address`, `height_cm`, `weight_kg`, `blood_type`, all medical-history arrays, `family_ocular_history`, `archived_*`, `deceased_at`.
- **Confirmation modals:** archive, restore, mark-deceased, toggle-status.
- **multipart:** none.

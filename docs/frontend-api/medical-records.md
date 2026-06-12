# MedicalRecords Module — Frontend API Documentation

## 1. Module Overview

The MedicalRecords module is the doctor's workspace: the **visit session** (examination form tied to an appointment), the **unified patient record**, per-category **timelines** (visits, reports, prescriptions, measurements, diagnoses, private notes), the **patient image library** (folders/files coming from Imaging), **image comparison**, doctor notes on images, and attaching images to medical reports.

- **Actors:** doctor (primary), medical_center_admin (full access), other staff only if granted the relevant permissions.
- **Key concepts:**
  - **Visit session:** a `visit_record` opened against an `in_progress` appointment. It is a *single composite document* with sections: visit data, eye measurement, report, prescription, diagnosis codes, private note. It is saved as a draft (repeatable) and then **finalized** (immutable).
  - **Unified record:** patient header + last visit + summary + latest eye measurement.
  - **Image folders:** each completed imaging request acts as a "folder" of imaging files for the patient; labeled by month (e.g. "Jun 2026").
  - **Private notes:** visible **only to the doctor who wrote them** — other doctors/admins never receive them.
  - **Doctor scoping:** in medical-center mode (`opticare.is_medical_center=true`), a doctor can only access patients with whom they have at least one appointment. Otherwise any doctor in the clinic can access any patient. Admin always has access.
- **Connections:** opens from Appointments (`in_progress`); finalize auto-completes the appointment; image folders/files come from the Imaging module; report image attachments reference `imaging_files` / `imaging_requests`.

## 2. Base Route Prefix

```
/api/medical-records
```

All endpoints require `auth:sanctum` (`Authorization: Bearer <token>`).

## 3. Authentication and Permissions

| Endpoint | Permission |
|---|---|
| `GET /diagnosis-codes` | `view disease classification` |
| `GET /patients/{p}/unified-record` | `view medical records` |
| `GET /patients/{p}/timeline/visits` | `view visit timeline` |
| `GET /patients/{p}/timeline/reports` | `view reports` |
| `GET /patients/{p}/timeline/prescriptions` | `view prescriptions` |
| `GET /patients/{p}/timeline/measurements` | ⚠ none enforced (see §15 in summary; intended: `view measurements`) |
| `GET /patients/{p}/timeline/diagnoses` | `view diagnoses` |
| `GET /patients/{p}/timeline/private-notes`, `GET /private-notes/{note}` | `view own notes` (+ owner-only scoping) |
| `GET /appointments/{a}/visit-session` | `view medical records` |
| `POST /appointments/{a}/visit-session` (open) | `create visit record` |
| `POST /visits/{v}/save-session` | `create visit record` + per-section permissions (below) |
| `POST /visits/{v}/finalize` | `create visit record` |
| Image types/folders/files endpoints | `view imaging timeline` |
| Image comparison endpoints | `compare images` |
| `POST /imaging-files/{f}/notes` | `edit medical records` |
| `POST /reports/{r}/images` | `create report` |

**Per-section permissions inside save-session:** sending `eye_measurement` requires `create measurement`; `report` → `create report`; `prescription` → `create prescription`; `diagnosis_codes` → `add disease classification`; `private_note` → `create note`. A 403 is returned if a section is sent without its permission.

Additionally, doctor/patient scoping (403 "not allowed to view record") applies in medical-center mode, and doctors can only open/save visits for **their own** appointments/visits (403 mismatch).

## 4. API Endpoint List

| Method | URL | Purpose | Permission | Request | Main response |
|---|---|---|---|---|---|
| GET | `/api/medical-records/diagnosis-codes` | Search ICD-style diagnosis codes | view disease classification | query | `items`+`pagination` |
| GET | `/api/medical-records/patients/{p}/unified-record` | Patient record header | view medical records | path | `patient`, `record`, `latest_eye_measurement` |
| GET | `/api/medical-records/patients/{p}/timeline/visits` | Visits timeline | view visit timeline | query | `items`+`pagination` |
| GET | `/api/medical-records/patients/{p}/timeline/reports` | Reports timeline | view reports | query | `items`+`pagination` |
| GET | `/api/medical-records/patients/{p}/timeline/prescriptions` | Prescriptions timeline | view prescriptions | query | `items`+`pagination` |
| GET | `/api/medical-records/patients/{p}/timeline/measurements` | Eye measurements timeline | (none enforced) | query | `items`+`pagination` |
| GET | `/api/medical-records/patients/{p}/timeline/diagnoses` | Diagnoses timeline | view diagnoses | query | `items`+`pagination` |
| GET | `/api/medical-records/patients/{p}/timeline/private-notes` | Own private notes timeline | view own notes | query | `items`+`pagination` |
| GET | `/api/medical-records/private-notes/{note}` | Private note details | view own notes | path | note object |
| GET | `/api/medical-records/appointments/{a}/visit-session` | Get session for appointment | view medical records | path | `visit_session` (or `null`) |
| POST | `/api/medical-records/appointments/{a}/visit-session` | Open (or return existing) session | create visit record | JSON | `visit_session` |
| POST | `/api/medical-records/visits/{v}/save-session` | Save draft session sections | create visit record (+section perms) | JSON | `visit_session` |
| POST | `/api/medical-records/visits/{v}/finalize` | Finalize visit | create visit record | — | `visit_session` |
| GET | `/api/medical-records/patients/{p}/image-types` | Available image types | view imaging timeline | query | `items` |
| GET | `/api/medical-records/patients/{p}/image-folders` | Image folders (paginated) | view imaging timeline | query | `items`+`pagination` |
| GET | `/api/medical-records/image-folders/{folder}/files` | Files of a folder | view imaging timeline | query | `folder`, `files`, `files_by_type` |
| GET | `/api/medical-records/imaging-files/{file}` | Single file details | view imaging timeline | path | file object |
| GET | `/api/medical-records/patients/{p}/image-comparison` | Compare two folders by type | compare images | query | `left`, `right` |
| GET | `/api/medical-records/patients/{p}/image-comparison/files` | Compare two files | compare images | query | `left`, `right` |
| POST | `/api/medical-records/imaging-files/{file}/notes` | Save doctor note on an image | edit medical records | JSON | note object |
| POST | `/api/medical-records/reports/{report}/images` | Attach images to a report | create report | JSON | attachment list |

## 5. Detailed API Documentation

### 5.1 GET `/diagnosis-codes`

- **Query:** `search` (≤255), `is_active` (bool), `per_page` (1–100).
- **Success 200:** `data.items` = raw diagnosis code rows (`id`, `code`, `name_en`, `name_ar`, `is_active`, ...) + `pagination`.
- Use as an autocomplete source for the visit session diagnosis-codes picker.

### 5.2 GET `/patients/{patient}/unified-record`

- **Success 200:**

```json
{
  "data": {
    "patient": { "id": 12, "medical_file_number": "MF-000012", "full_name": "Sara Ali Khalil", "gender": "female", "birth_date": "1990-04-02", "age": 36, "status": "active" },
    "record": {
      "last_visit": { "id": 40, "date": "2026-06-01 10:30:00", "status": "finalized" },
      "summary": "Dry eye syndrome"
    },
    "latest_eye_measurement": {
      "id": 7, "measured_at": "2026-06-01 10:40:00",
      "visual_acuity": { "od": "20/25", "os": "20/20" },
      "iop": { "od": { "value": 16, "unit": "mmHg" }, "os": { "value": 15, "unit": "mmHg" } }
    }
  }
}
```

- `record.last_visit` and `latest_eye_measurement` can be `null` for new patients.
- **403:** doctor without an appointment relationship to the patient (medical-center mode). **404:** patient not found.

### 5.3 Timelines — `GET /patients/{patient}/timeline/{visits|reports|prescriptions|measurements|diagnoses|private-notes}`

- **Shared query:** `date_from`, `date_to` (≥ from), `per_page` (1–100).
- All return `{ items, pagination }`. Item shapes per type:

**visit** — `{ id, timeline_type: "visit", visit_type, title ("Consultation"/"Follow-up"/"Emergency"/"Post-op"/"Visit"), date, status, doctor {id,name}, appointment {id,type,status}, is_clickable: true }`

**report** — `{ id, timeline_type: "report", visit_id, title, preview (≤120 chars), status ("draft"|"finalized"), status_label, date, display_date ("Jun 12, 2026"), doctor, images_count, actions { can_view, can_download, can_print, can_export_pdf }, is_clickable }` — download/print/export are `true` only when finalized (PDF generation itself is **not implemented** server-side yet).

**prescription** — `{ id, timeline_type: "prescription", visit_id, title (first medicine + "+N more"), preview, status, status_label, date, display_date, doctor, items_count, medicines: [{id,name,dosage,frequency,duration}], notes, actions { can_view, can_download, can_print, can_export_pdf }, is_clickable }`

**measurement** — `{ id, timeline_type: "measurement", visit_id, appointment_id, date, display_date ("Jun 2026"), doctor, visual_acuity { label, od, os }, iop { label, unit: "mmHg", od, os }, notes, is_clickable: false }`

**diagnosis** — `{ id (visit id), timeline_type: "diagnosis", visit_id, visit_type, status, status_label, date, display_date, doctor, diagnosis_summary (≤160), codes_count, codes: [{id, code, label, full_label, name_en, name_ar}], is_clickable }`

**private_note** — `{ id, timeline_type: "private_note", visit_id, date, display_date, preview (≤120), visibility: "private", access_scope: "own_doctor_only", is_clickable, actions { can_view: true, can_update: true, can_delete: false } }` — returns **only the authenticated doctor's notes**.

### 5.4 GET `/private-notes/{note}`

- Full note body: `{ id, timeline_type, visit_id, date, display_date, note, visibility, access_scope, is_owner: true }`.
- **404** if the note does not belong to the authenticated doctor (privacy by design).

### 5.5 GET `/appointments/{appointment}/visit-session`

- Returns `data: { "visit_session": null }` if no visit exists yet for that appointment (HTTP 200 — handle the null!).
- Otherwise returns the full **visit_session object** (below).

### 5.6 POST `/appointments/{appointment}/visit-session` (open)

- **Body (optional):** `{ "visit_type": "consultation", "notes": "..." }` — `visit_type` one of `consultation`, `follow_up`, `emergency`, `post_op`; `notes` ≤5000.
- **Idempotent:** if a visit already exists for the appointment, it is returned instead of creating a new one.
- **Preconditions (422):** appointment must be `in_progress`; appointment must have a doctor. **403:** doctor opening someone else's appointment. **404:** appointment not found.
- **Success 200** — `data.visit_session`:

```json
{
  "id": 41,
  "status": "draft",
  "status_label": "Draft",
  "is_finalized": false,
  "visit_type": "consultation",
  "visit_at": "2026-06-12T10:35:00.000000Z",
  "display_date": "Jun 12, 2026",
  "finalized_at": null,
  "patient": { "id": 12, "medical_file_number": "MF-000012", "full_name": "Sara Ali Khalil", "gender": "female", "birth_date": "1990-04-02", "age": 36 },
  "doctor": { "id": 5, "name": "Dr. Sami" },
  "appointment": { "id": 31, "status": "in_progress", "type": "consultation", "appointment_at": "2026-06-12T10:30:00.000000Z", "reason": "blurred vision" },
  "sections": {
    "visit": { "visit_type": "consultation", "chief_complaint": null, "symptoms": null, "examination_notes": null, "diagnosis": null, "treatment_plan": null, "notes": null },
    "eye_measurement": {
      "id": null, "mode": "create", "measured_at": null,
      "visual_acuity": { "od": null, "os": null, "od_placeholder": "e.g. 20/25", "os_placeholder": "e.g. 20/20" },
      "iop": { "unit": "mmHg", "od": null, "os": null, "od_placeholder": "e.g. 16", "os_placeholder": "e.g. 15" },
      "notes": null
    },
    "report": { "id": null, "mode": "create", "title": null, "report_text": null, "status": "draft", "images_count": 0, "images": [] },
    "prescription": { "id": null, "mode": "create", "prescription_text": null, "status": "draft", "notes": null, "items": [] },
    "diagnosis_codes": [],
    "private_note": { "id": null, "mode": "create", "note": null, "visibility": "private", "access_scope": "own_doctor_only" }
  },
  "actions": { "can_save": true, "can_finalize": true, "can_print": false, "can_export_pdf": false }
}
```

### 5.7 POST `/visits/{visit}/save-session`

- **Purpose:** save any subset of section data; sections not sent are untouched. Can be called repeatedly while the visit is `draft`.
- **Body (all sections optional):**

```json
{
  "visit": {
    "visit_type": "consultation",
    "chief_complaint": "blurred vision",
    "symptoms": "headache",
    "examination_notes": "...",
    "diagnosis": "Dry eye",
    "treatment_plan": "...",
    "notes": "..."
  },
  "eye_measurement": {
    "visual_acuity_od": "20/25", "visual_acuity_os": "20/20",
    "iop_od": 16, "iop_os": 15,
    "notes": null, "measured_at": "2026-06-12T10:40:00Z"
  },
  "report": {
    "title": "Consultation report",
    "report_text": "Findings ...",
    "selected_image_ids": [101, 102],
    "selected_folder_ids": [55],
    "images": [
      { "imaging_request_id": 55, "imaging_file_id": 101, "notes": "macula OD" }
    ]
  },
  "prescription": {
    "prescription_text": "free text rx",
    "notes": null,
    "items": [
      { "medicine_name": "Artificial tears", "dosage": "1 drop", "frequency": "3x daily", "duration": "1 month" }
    ]
  },
  "diagnosis_codes": [3, 17],
  "private_note": { "note": "suspect non-compliance" }
}
```

- **Validation highlights:** text limits — chief_complaint/symptoms ≤2000; examination_notes/diagnosis/treatment_plan/notes ≤5000; report_text/prescription_text ≤20000; medicine_name required within items; `diagnosis_codes.*` must exist; image/folder ids must exist.
- **Semantics to know:**
  - `report.images` **replaces** the report's image list when the key is present; `selected_image_ids`/`selected_folder_ids` **append** links (folder ids attach all files in the folder).
  - `prescription.items` replaces the item list when the key is present.
  - `diagnosis_codes` is synced (full replacement).
  - `private_note` upserts the authenticated doctor's note for this visit.
- **Errors:** **404** visit not found; **422** visit already finalized / visit cancelled; **403** doctor mismatch or missing section permission; **422** image not for this patient.
- **Success 200:** the refreshed `visit_session` object.

### 5.8 POST `/visits/{visit}/finalize`

- No body. Sets visit `status: "finalized"`, finalizes the report and prescription, updates the patient's `medical_records` summary/last visit, and **auto-completes the linked appointment** (if `in_progress`).
- **Errors:** 404; 422 already finalized / cancelled; 403 doctor mismatch.
- **Success 200:** `visit_session` with `is_finalized: true`, `actions: { can_save: false, can_finalize: false, can_print: true, can_export_pdf: true }`.

### 5.9 GET `/patients/{patient}/image-types`

- **Query:** `date_from`, `date_to`.
- **Success:** `data.items = [ { "image_type": "OCT", "label": "OCT", "files_count": 8, "folders_count": 3, "latest_captured_at": "2026-06-01T09:00:00.000000Z" } ]` (no pagination).

### 5.10 GET `/patients/{patient}/image-folders`

- **Query:** `image_type` (≤50), `eye` (`OD`|`OS`|`OU`), `region` (≤100), `status` (`pending,in_progress,completed,canceled`), `date_from`, `date_to`, `per_page`.
- **Item shape:**

```json
{
  "id": 55,
  "timeline_type": "imaging",
  "folder_label": "Jun 2026",
  "date": "2026-06-01T09:00:00.000000Z",
  "request_type": "OCT macula",
  "status": "completed",
  "images_count": 4,
  "available_types": ["OCT"],
  "files_count_by_type": { "OCT": 4 },
  "doctor": { "id": 5, "name": "Dr. Sami" },
  "is_selectable_for_view": true,
  "is_selectable_for_report": true,
  "is_selectable_for_compare": true
}
```

### 5.11 GET `/image-folders/{folder}/files`

- **Query:** `image_type`, `eye` (`OD`/`OS`/`OU`), `region` filters.
- **Success:** `data = { folder: {id,label,request_type,date,doctor}, files: [<file>], files_by_type: { "OCT": [<file>] } }`.

**File object:**

```json
{
  "id": 101,
  "label": "macula OD",
  "image_type": "OCT",
  "modality": "OCT",
  "eye": "OD",
  "region": "macula",
  "file_name": "scan1.png",
  "file_url": "https://host/storage/imaging/12/55/uuid.png",
  "thumbnail_url": "https://host/storage/imaging/12/55/uuid.png",
  "captured_at": "2026-06-01T09:05:00.000000Z",
  "notes": "doctor note if loaded",
  "is_selectable_for_view": true,
  "is_selectable_for_report": true,
  "is_selectable_for_compare": true
}
```

### 5.12 GET `/imaging-files/{file}`

- Single file (same shape) plus `folder: { id, label, request_type }`. **404** if not found.

### 5.13 GET `/patients/{patient}/image-comparison` (folder comparison)

- **Query (required):** `image_type` (string), `left_folder_id`, `right_folder_id` (different, must exist).
- **Success:**

```json
{
  "image_type": "OCT",
  "left":  { "folder": { "id": 50, "label": "Jan 2026", "date": "...", "title": "Historical" }, "files": [<file>] },
  "right": { "folder": { "id": 55, "label": "Jun 2026", "date": "...", "title": "Current" },   "files": [<file>] }
}
```

The older folder is titled `Historical`, the newer `Current` (regardless of which side it was sent on).

- **Errors (422):** folder not for this patient; folder has no files of that type.

### 5.14 GET `/patients/{patient}/image-comparison/files` (file comparison)

- **Query (required):** `left_file_id`, `right_file_id` (different, must exist).
- **Success:** `{ image_type, same_eye: true|false|null, same_region: true|false|null, left: <file + folder>, right: <file + folder> }` (`null` when either side lacks the metadata).
- **422:** the two files have different image types.

### 5.15 POST `/imaging-files/{file}/notes`

- **Body:** `{ "note": "thinning of RNFL", "visit_record_id": 41 }` — both nullable (`note` ≤5000). Sending `note: null`/empty clears the note.
- Upserts the **authenticated doctor's** note for the file.
- **Success 200:** `{ id, imaging_file_id, patient_id, doctor {id,name}, visit_record_id, note, updated_at }`.

### 5.16 POST `/reports/{report}/images`

- **Body:**

```json
{
  "mode": "append",
  "imaging_file_ids": [101, 102],
  "imaging_request_ids": [55]
}
```

- `mode`: `append` (default) or `replace` (clears existing attachments first). At least one of the two id arrays is required (**422** otherwise). Folder ids attach **all files** of those folders.
- **Errors:** 404 report not found; 422 image belongs to another patient.
- **Success 200:**

```json
{
  "report_id": 9,
  "attached_images_count": 5,
  "items": [
    { "id": 1, "medical_report_id": 9, "imaging_request_id": 55, "imaging_file_id": 101,
      "file": { "id": 101, "label": "macula OD", "image_type": "OCT", "file_url": "..." } }
  ]
}
```

## 6. Response Shape

Standard `ApiResponse`. Paginated endpoints use the shared block:

```json
{ "items": [], "pagination": { "current_page": 1, "per_page": 15, "total": 100, "last_page": 7, "from": 1, "to": 15, "has_more": true } }
```

## 7. Status Values and Frontend Behavior

**Visit record (`visit_session.status`):**

| Status | Meaning | Badge | Allowed actions |
|---|---|---|---|
| `draft` | Editable session | yellow "Draft" | save-session, finalize |
| `finalized` | Locked record | green "Finalized" | view, print/export (UI-level) |
| `cancelled` | Cancelled visit | red "Cancelled" | view only (422 on save/finalize) |

**Report / Prescription status:** `draft` → `finalized` (finalized together with the visit). Timelines expose `status` + `status_label`.

**Image folder status** mirrors the imaging request status (`pending`, `in_progress`, `completed`, `canceled` filter values) — normally only `completed` folders contain files.

## 8. Frontend Action Flags

This module **does** return action flags — always prefer them over local logic:

- `visit_session.actions`: `can_save`, `can_finalize` (draft only), `can_print`, `can_export_pdf` (finalized only).
- Report/prescription timeline `actions`: `can_view`, `can_download`, `can_print`, `can_export_pdf`.
- Private notes `actions`: `can_view`, `can_update`, `can_delete` (always false currently).
- Image folders/files: `is_selectable_for_view`, `is_selectable_for_report`, `is_selectable_for_compare` — disable selection checkboxes when false.
- `is_clickable` on timeline items: controls whether the row opens a detail view.

> Rule: render buttons from these flags; never duplicate the status/permission logic client-side.

## 9. Cross-Module Integration Notes

- **Appointments:** a visit session can only be opened on an `in_progress` appointment with an assigned doctor; finalizing the visit completes the appointment.
- **Imaging:** completed imaging requests appear as image folders here; files uploaded by technicians/doctors are browsable, comparable, annotatable, and attachable to reports through this module. To *order* new imaging, use the Imaging module (`POST /api/imaging/requests`, optionally passing the current `visit_record_id`).
- **Patients:** all patient-scoped URLs use the `clinic_patients` id.
- **RolesPermissions:** each section of the visit form maps to a distinct permission (see §3) — a doctor lacking `create prescription` should not even see the prescription section editor.

## 10. Frontend Flows

**Open & run a visit session (doctor)**
1. From the doctor's today list, on an `in_progress` appointment: `GET /appointments/{id}/visit-session`.
2. If `visit_session` is `null` → `POST /appointments/{id}/visit-session` to open it.
3. Autosave/save button → `POST /visits/{id}/save-session` with only the changed sections.
4. Finish → confirm modal → `POST /visits/{id}/finalize`. Appointment completes automatically; route back to the list.

**View unified patient record**
1. `GET /patients/{id}/unified-record` for the header card.
2. Tabs lazily load each timeline endpoint with pagination.

**Image library & comparison**
1. `GET /patients/{id}/image-types` for type chips → `GET /patients/{id}/image-folders?image_type=OCT`.
2. Folder click → `GET /image-folders/{id}/files` (grouped by `files_by_type`).
3. Compare: pick two folders of one type → `GET /patients/{id}/image-comparison?...`; or two individual files → `GET /patients/{id}/image-comparison/files?...`. Sides are labeled Historical/Current by the API.
4. Annotate: `POST /imaging-files/{id}/notes`.

**Attach images to a report**
1. While the visit is open: include `report.selected_image_ids` / `selected_folder_ids` in save-session, **or**
2. Standalone: `POST /reports/{id}/images` with `mode` append/replace.

## 11. Form-Data Examples

All MedicalRecords endpoints are **JSON** (uploads live in the Imaging module). Nested arrays are sent as JSON arrays as shown in §5.7 / §5.16.

## 12. Error Handling

| Code | When |
|---|---|
| 401 | Missing/expired token |
| 403 | Missing permission; doctor↔patient scope violation; doctor mismatch on appointment/visit; missing per-section permission |
| 404 | Patient / appointment / visit / folder / file / report / private note not found (private notes of other doctors return 404, not 403) |
| 422 | Validation; appointment not in progress; appointment has no doctor; visit already finalized; visit cancelled; folders must differ; files type mismatch; folder/file not for patient; folder missing requested type; attach-images requires at least one id |
| 500 | Unexpected error |

## 13. Frontend Notes

- **Page load:** unified-record + first timeline tab; visit screen loads `GET .../visit-session` first.
- **After actions:** save/finalize return the fresh `visit_session` — replace local state from the response.
- **Cache:** diagnosis-codes search results may be cached per keyword; image folders/types per patient session.
- **Do not hardcode:** visit types, diagnosis codes, image types — all come from the API.
- **Nullable fields:** virtually every section field can be `null` (new sessions); `visit_session` itself can be `null` on GET; `latest_eye_measurement`, `record.last_visit`, `doctor`, `appointment`, `thumbnail_url`, `notes`, `eye`, `region` — guard everything.
- **Confirmation modals:** finalize visit (irreversible), `mode: "replace"` on report images.
- **multipart:** none here — image upload is in Imaging.
- **Placeholders:** the API ships UI placeholders (`od_placeholder`, e.g. "e.g. 20/25") — use them in inputs.
- **Numbers:** IOP values arrive as numbers (int when whole); visual acuity is a free string ("20/25").

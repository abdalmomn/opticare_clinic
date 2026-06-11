# Imaging Module Technical Implementation Plan

## 1. Existing Imaging State

### Module Existence

`app/Modules/Imaging` exists and is registered through `CoreServiceProvider`.

Current files:

- `Models/ImagingRequest.php`
- `Models/ImagingFile.php`
- `Models/ImagingQueue.php`
- `Providers/ImagingServiceProvider.php`
- `Routes/api.php`

The `Controllers`, `Services`, `Repositories`, `Requests`, and `Helpers` directories exist but contain no implementation.

### Existing Routes

`app/Modules/Imaging/Routes/api.php` is empty.

The provider registers Imaging routes under:

```text
/api/imaging
```

There are currently no operational Imaging APIs.

### Existing Models

#### ImagingRequest

Currently supports:

- Patient
- Visit record
- Requesting doctor
- Room
- Imaging files
- Queue record
- Single `request_type`
- Notes, status, and priority

Problems:

- Imports the nonexistent `App\Modules\Clinic\Models\ClinicPatient`; the real model is `App\Modules\Patients\Models\ClinicPatient`.
- No appointment, secretary, technician, payment, audit, or lifecycle timestamp relationships.
- `room_id` is required.
- Uses `$timestamps = false`.
- Existing statuses are only documented as `pending`, `in_progress`, `completed`, and `canceled`.

#### ImagingFile

Currently supports:

- Imaging request
- Uploader
- Device foreign key in the database
- File metadata
- Image type, modality, eye, region, label, thumbnail
- MedicalRecords doctor notes relationship

Problems:

- No `device()` relationship.
- Patient, visit, and appointment are only inferred through the request.
- No upload source.
- No soft deletion.
- No updated or uploaded timestamps.

#### ImagingQueue

Currently supports:

- Imaging request
- Room
- Queue number
- Status
- Called timestamp

Problems:

- No unique constraint ensuring one queue row per request.
- No assigned technician.
- Queue status could drift from request status.
- No dispatch, start, or completion metadata.

### Existing Implementation Coverage

Already implemented:

- Basic Imaging models and database tables.
- Device storage through `clinic_devices`.
- Imaging request permissions and policy skeleton.
- MedicalRecords viewing, comparison, notes, and report attachment.
- Appointment types for `imaging` and `consultation_and_imaging`.
- Configuration flag `opticare.imaging_one_at_a_time`.

Partial:

- Request lifecycle.
- Technician queue.
- Device relationships.
- Permissions.
- Payment infrastructure through generic invoices and payments.

Missing:

- Imaging controllers, services, repositories, requests, routes, translations, and tests.
- Request items supporting multiple requested image types.
- Separate payment confirmation and technician dispatch.
- Technician start, upload, and complete workflow.
- Direct doctor and external uploads.
- Device management APIs.
- Imaging statistics and activity logs.

## 2. Existing Database Tables

All inspected migrations have been applied.

### `imaging_requests`

Current columns:

```text
id, patient_id, visit_record_id, requested_by, room_id,
request_type, notes, status, priority, created_at, completed_at
```

Relationships:

- `patient_id -> clinic_patients`, cascade delete
- `visit_record_id -> visit_records`, null on delete
- `requested_by -> staff`, cascade delete
- `room_id -> rooms`, cascade delete

Missing:

- `appointment_id`
- Payment state and confirmation metadata
- Secretary and dispatch metadata
- Technician assignment
- `started_at`, `sent_to_technician_at`, `cancelled_at`
- `created_by`, `updated_by`, `updated_at`
- Source and cancellation reason

Do not remove:

- `request_type`: MedicalRecords currently displays it as the folder type.
- `completed_at`, `status`, `created_at`: MedicalRecords uses them for folders and timeline.

Required correction:

- Make `room_id` nullable with `nullOnDelete`.
- Avoid cascading request deletion when a room, doctor, or patient account is removed.

### `imaging_files`

Current columns:

```text
id, imaging_request_id, uploaded_by, device_id,
file_path, thumbnail_path, file_name, file_type, file_size,
mime_type, device_name, modality, image_type, eye, region,
image_label, captured_at, is_primary, created_at
```

Relationships:

- `imaging_request_id -> imaging_requests`, cascade delete
- `uploaded_by -> staff`, cascade delete
- `device_id -> clinic_devices`, null on delete

Missing:

- `patient_id`
- `visit_record_id`
- `appointment_id`
- `imaging_request_item_id`
- `upload_batch_id`
- `source`
- `uploaded_at`
- `updated_at`
- `deleted_at`

Do not remove or rename:

- `image_type`, `modality`: MedicalRecords resolves type using `image_type`, then `modality`.
- `eye`, `region`, `image_label`, `thumbnail_path`
- `file_path`, `file_name`, `captured_at`
- `imaging_request_id`: MedicalRecords treats requests as image folders.

### `imaging_queue`

Current columns:

```text
id, imaging_request_id, room_id, queue_number,
status, called_at, created_at
```

Missing:

- Unique index on `imaging_request_id`
- Optional `technician_id`
- `dispatched_at`, `started_at`, `completed_at`, `updated_at`

Recommendation:

Use this only for queue ordering and dispatch metadata. `imaging_requests.status` must remain the lifecycle source of truth.

### `clinic_devices`

This is the existing equivalent of `imaging_devices`.

Current columns:

```text
id, room_id, name, serial_number, device_type,
manufacturer, model, status, last_maintenance_at,
notes, created_by, created_at, updated_at
```

Relationships:

- `room_id -> rooms`, null on delete
- `created_by -> staff`, null on delete

Missing:

- Unique operational `device_identifier`
- `updated_by`
- Optional soft deletion

Do not create a duplicate `imaging_devices` table.

### `imaging_file_notes`

Already complete and owned by MedicalRecords:

```text
id, imaging_file_id, patient_id, doctor_id, visit_record_id,
note, created_by, updated_by, created_at, updated_at
```

Do not duplicate or move this logic.

### Supporting Tables

| Table | Important Current Integration |
|---|---|
| `rooms` | Supports imaging rooms and device assignment. |
| `appointments` | Supports imaging appointment types but has no ImagingRequest relationship. |
| `visit_records` | Can link requests and files to a clinical visit. |
| `medical_reports` | Patient and visit report container. |
| `medical_report_images` | Already links reports to requests and files. |
| `invoices` / `payments` | Generic payment infrastructure exists but has no imaging request linkage. |

## 3. Required Database Design

### Reuse and Extend `clinic_devices`

Recommended additions:

```text
device_identifier nullable unique
updated_by nullable FK staff
deleted_at nullable, only if historical deletion is required
```

Keep `serial_number`, `device_type`, and `status`.

Use status values such as:

```text
active, maintenance, offline, retired
```

Activation and deactivation APIs should update `status`; adding a redundant `is_active` is unnecessary.

### Extend `imaging_requests`

Recommended final structure:

```text
id
patient_id
visit_record_id nullable
appointment_id nullable
requested_by nullable
room_id nullable
invoice_item_id nullable, if payment integration is selected
source: doctor_request / secretary_request / doctor_upload / external
request_type
notes nullable
priority
status
payment_status: pending / confirmed / waived / refunded
confirmed_by nullable
sent_to_technician_by nullable
technician_id nullable
created_by nullable
updated_by nullable
payment_confirmed_at nullable
sent_to_technician_at nullable
started_at nullable
completed_at nullable
cancelled_at nullable
cancel_reason nullable
created_at
updated_at
```

### Create `imaging_request_items`

Required because a request can contain multiple image types:

```text
id
imaging_request_id
image_type
eye nullable: OD / OS / OU
region nullable
notes nullable
status: requested / captured / skipped
created_at
updated_at
```

Use free-form validated strings initially because no image-type catalog currently exists.

### Extend `imaging_files`

Recommended final structure:

```text
id
imaging_request_id
imaging_request_item_id nullable
upload_batch_id nullable
patient_id
visit_record_id nullable
appointment_id nullable
device_id nullable
uploaded_by
source: technician_upload / doctor_upload / external
image_type
modality nullable
eye nullable
region nullable
image_label nullable
file_name
file_path
thumbnail_path nullable
file_type
mime_type
file_size
device_name nullable
captured_at nullable
uploaded_at
is_primary
created_at
updated_at
deleted_at
```

Technician uploads must require `device_id`. External uploads may omit it.

For direct and external uploads, create a completed `imaging_request` container instead of allowing a null request ID. This preserves MedicalRecords folder behavior.

### Optional `imaging_upload_batches`

Recommended for folder and multi-file uploads:

```text
id, imaging_request_id, uploaded_by, device_id,
source, files_count, status, created_at, completed_at
```

### Optional `imaging_activity_logs`

Create only if imaging-specific audit history is required:

```text
id, imaging_request_id nullable, imaging_file_id nullable,
actor_id nullable, action, from_status nullable, to_status nullable,
metadata json nullable, created_at
```

No general activity-log implementation currently exists.

## 4. Required Status Lifecycle

Recommended lifecycle:

```text
requested
  -> pending_payment
  -> payment_confirmed
  -> ready_for_imaging
  -> in_progress
  -> completed
```

Cancellation:

```text
requested / pending_payment / payment_confirmed / ready_for_imaging
  -> cancelled
```

Rules:

- `confirm-payment` changes payment state and moves only to `payment_confirmed`.
- `send-to-technician` is separate and moves only to `ready_for_imaging`.
- `start` moves `ready_for_imaging -> in_progress`.
- Uploading files does not automatically complete the request.
- `complete` moves `in_progress -> completed`.
- `cancelled` and `completed` are terminal.

Compatibility migration:

```text
pending  -> pending_payment
canceled -> cancelled
```

MedicalRecords currently validates old status values. Its status filter validation must accept the new lifecycle values while preserving aliases during rollout.

## 5. Required Permissions

The project uses human-readable permission names, not dotted names.

### Existing Reusable Permissions

```text
create imaging request
view imaging requests
upload imaging files
delete imaging file
view imaging queue
manage imaging queue
create device
edit device
delete device
toggle device status
view devices
view statistics
view activity log
```

### Required Granular Additions

Add constants to `PermissionList.php` and assign them in `RolesAndPermissionsSeeder.php`:

```text
view own imaging requests
create imaging request for patient
confirm imaging payment
send imaging request to technician
start imaging request
complete imaging request
upload doctor imaging files
upload external imaging files
delete own imaging file
delete any imaging file
cancel imaging request
view all imaging requests
```

The existing broad permissions can remain temporarily for backward compatibility.

### Recommended Role Assignments

| Role | Permissions |
|---|---|
| Doctor | Create request, view own, cancel own when allowed, doctor upload, external upload |
| Secretary | View pending requests, create for patient, confirm payment, send to technician |
| Imaging Technician | View queue, start, upload files, complete, delete own upload |
| Medical Center Admin | View all, manage devices, view statistics and logs, delete any file |

Update `ImagingPolicy` to enforce both permission and record-level scope. Continue using `AccessControlHelper::staffHasPermission()` so grant and revoke overrides remain effective.

## 6. Required APIs

All routes should be authenticated and resolve under `/api/imaging`.

### Requests and Secretary Workflow

| Method and Path | Permission | Body / Validation | Response / Transition |
|---|---|---|---|
| `POST /requests` | Create request or create for patient | `patient_id`, optional `visit_record_id`, `appointment_id`, `room_id`, `notes`, `priority`, required `requested_types[]` containing `image_type`, optional `eye`, `region`, `notes` | Created request summary; usually `pending_payment` |
| `GET /requests` | View own or view all | Filters: status, patient, doctor, technician, dates, priority, pagination | Paginated request summaries scoped by actor |
| `GET /requests/{request}` | View own or all | Route ID | Request detail including items and permitted actions |
| `POST /requests/{request}/cancel` | Cancel imaging request | Optional `reason`; request must be cancellable | Request becomes `cancelled` |
| `POST /requests/{request}/confirm-payment` | Confirm imaging payment | Optional `invoice_item_id`, payment reference, notes | Payment becomes confirmed; request becomes `payment_confirmed` |
| `POST /requests/{request}/send-to-technician` | Send imaging request to technician | Optional `technician_id`, `room_id`, priority | Request becomes `ready_for_imaging`; queue row created or updated |

The final two APIs must remain separate.

### Technician Workflow

| Method and Path | Permission | Body / Validation | Response / Transition |
|---|---|---|---|
| `GET /technician/requests` | View imaging queue | Filters: room, priority, date, pagination | Ready queue plus actor's active request |
| `POST /requests/{request}/start` | Start imaging request | Optional `room_id`; must be ready | Assign or confirm technician; transition to `in_progress` |
| `POST /requests/{request}/files` | Upload imaging files | Multipart `files[]`; required `device_id`; per-file `image_type`, optional item ID, eye, region, label, captured_at | Uploaded file summaries; status remains `in_progress` |
| `POST /requests/{request}/complete` | Complete imaging request | Optional completion notes | Requires at least one active file; transition to `completed` |

Multi-file and folder upload should use one endpoint and optional batch metadata rather than separate file-storage logic.

### Direct and External Uploads

| Method and Path | Permission | Body / Validation | Response |
|---|---|---|---|
| `POST /direct-upload` | Upload doctor imaging files | Patient, optional visit, appointment and device, files and metadata | Creates completed direct-upload request container and files |
| `POST /external-upload` | Upload external imaging files | Patient, optional visit and appointment, files and metadata | Creates completed external request container and files |

These features are not currently implemented elsewhere.

### File Operations

| Method and Path | Permission | Rules |
|---|---|---|
| `DELETE /files/{file}` | Delete own or delete any | Soft-delete database record and retain or remove storage according to retention policy |

Do not add viewing, comparison, note, or report-attachment endpoints here.

### Device Management

| Method and Path | Permission | Body / Behavior |
|---|---|---|
| `GET /devices` | View devices | Filters by status, room, type |
| `POST /devices` | Create device | Name, identifier, type, manufacturer, model, room, status |
| `GET /devices/{device}` | View devices | Device summary or detail |
| `PATCH /devices/{device}` | Edit device | Editable metadata |
| `PATCH /devices/{device}/activate` | Toggle device status | Set status `active` |
| `PATCH /devices/{device}/deactivate` | Toggle device status | Set status `offline` |
| `DELETE /devices/{device}` | Delete device | Reject if historically referenced unless soft deletion is used |

### Statistics and Logs

```text
GET /statistics/overview
GET /statistics/by-device
GET /statistics/by-type
GET /activity-logs
```

The activity-log endpoint should only be added if `imaging_activity_logs` is implemented.

Statistics responses should support date filters and return aggregate arrays, never raw models.

## 7. Response Shape Standards

Controllers must return `ApiResponse`. Services must return arrays only.

### Imaging Request

```json
{
  "id": 42,
  "status": "ready_for_imaging",
  "status_label": "Ready for imaging",
  "priority": "normal",
  "patient": {
    "id": 8,
    "full_name": "Patient Name",
    "birth_date": "2000-01-01",
    "medical_file_number": "MF-0008"
  },
  "doctor": {
    "id": 3,
    "name": "Doctor Name"
  },
  "requested_types": [],
  "payment": {
    "status": "confirmed",
    "confirmed_at": "2026-06-10T10:00:00Z",
    "confirmed_by": {
      "id": 4,
      "name": "Secretary Name"
    }
  },
  "technician": {
    "id": 7,
    "name": "Technician Name"
  },
  "timestamps": {},
  "actions": {
    "can_confirm_payment": false,
    "can_send_to_technician": false,
    "can_start": true,
    "can_upload": false,
    "can_complete": false,
    "can_cancel": false
  }
}
```

### Imaging File

```json
{
  "id": 100,
  "label": "Macula OD",
  "image_type": "OCT",
  "eye": "OD",
  "region": "Macula",
  "file_url": "...",
  "thumbnail_url": "...",
  "captured_at": "...",
  "device": {
    "id": 5,
    "name": "OCT Room 1",
    "device_identifier": "OCT-001",
    "type": "OCT"
  },
  "uploaded_by": {
    "id": 7,
    "name": "Technician Name"
  },
  "source": "technician_upload"
}
```

Paginated results should follow the existing `items` and `pagination` structure.

## 8. Business Rules

- Doctor may create requests only for an accessible active patient and matching visit or appointment.
- Secretary payment confirmation and technician dispatch must be separate transactions and APIs.
- Dispatch requires confirmed or waived payment.
- Technician can start only `ready_for_imaging` requests.
- With `imaging_one_at_a_time=true`, a technician cannot start another request while owning an `in_progress` request.
- Use transactions and row locking for confirm, dispatch, start, complete, and cancel operations.
- Technician uploads require an active imaging device.
- Every file must link to patient, request, uploader, source, and actual image type.
- Uploaded image type should match a requested item unless explicitly recorded as an additional capture.
- Completion requires at least one non-deleted file.
- Prefer requiring all non-skipped request items to have a captured file before completion.
- Direct doctor uploads must use source `doctor_upload`.
- External uploads must use source `external`.
- Direct and external uploads should create completed request containers so MedicalRecords can continue treating requests as folders.
- Soft deletion is preferred for files.
- Completed and cancelled requests are terminal.
- Device deletion must not destroy historical imaging files.
- Status transitions must be centralized in the service layer.

## 9. Integration Points

### MedicalRecords

MedicalRecords already reads completed `imaging_requests` as folders and `imaging_files` as folder contents.

Preserve:

- Required `imaging_request_id` on files
- `request_type`
- `completed` status
- `image_type` with `modality` fallback
- Existing note and report relationships

Only minimal compatibility updates should be made to MedicalRecords status validation.

### Medical Reports

`medical_report_images` already supports request-folder and individual-file attachment. No Imaging endpoint should duplicate this.

### Appointments

Add `appointment_id` to imaging requests and files.

Secretary-created imaging appointments may create an imaging request immediately or during check-in, depending on the final business decision.

### Patients and Visits

Validate that the request patient matches the appointment and visit patient. Patient summaries should expose only frontend-required fields.

### Rooms and Devices

Reuse `rooms` and `clinic_devices`. Restrict device room assignment to active imaging rooms where practical.

### RolesPermissions

Use:

- `PermissionList`
- `RolesAndPermissionsSeeder`
- `AccessControlHelper`
- `ImagingPolicy`
- Existing grant and revoke override architecture

## 10. Implementation Phases

### Phase 1: Stabilize Tables and Models

Files:

- New additive migrations
- Update Imaging models
- Update `ClinicDevice`, `Room`, `Appointment`, `VisitRecord`, `Staff`, and `ClinicPatient` relationships

Changes:

- Add lifecycle fields, request items, file links, device fields, indexes, and soft deletes.
- Correct the invalid ClinicPatient import.
- Preserve MedicalRecords contracts.

Risks:

- Existing status values and required `room_id`.
- Cascade deletes.
- Legacy MedicalRecords filters.

Tests:

- Migration and backfill tests.
- Relationship and legacy-record tests.

### Phase 2: Permissions and Seeders

Files:

- `PermissionList.php`
- `RolesAndPermissionsSeeder.php`
- `ImagingPolicy.php`

Changes:

- Add granular permissions and role mappings.
- Enforce record-level scopes.

Tests:

- Role defaults.
- Permission grants and revokes.
- Unauthorized access.

### Phase 3: Imaging Request Workflow

Create:

- `ImagingRequestController`
- `ImagingRequestService`
- `ImagingRequestRepository`
- Store, list, and cancel request classes
- Imaging translations

Changes:

- Create, list, show, cancel, and format responses.

Tests:

- Multiple requested types.
- Patient access.
- Status filtering and action flags.

### Phase 4: Secretary Workflow

Create or update:

- Payment confirmation and dispatch request classes
- Secretary service methods
- Queue repository behavior

Changes:

- Implement two separate APIs and transitions.

Tests:

- Confirm without dispatch.
- Dispatch only after confirmation.
- Duplicate action and idempotency behavior.

### Phase 5: Technician Queue and Uploads

Create:

- Technician queue controller and service
- File upload controller, service, and repository
- Upload and complete requests

Changes:

- Queue, start, multi-file upload, complete, and soft delete.
- Enforce active device and one-request-at-a-time rule.

Tests:

- Concurrent starts.
- Device validation.
- Complete without files.
- File metadata persistence.

### Phase 6: Device Management

Create:

- Imaging device controller, service, repository, and requests

Changes:

- Expose `clinic_devices` through Imaging operational APIs.

Tests:

- Activate and deactivate.
- Room assignment.
- Referenced-device deletion.

### Phase 7: Direct and External Uploads

Changes:

- Create completed request containers.
- Mark upload source correctly.
- Reuse upload pipeline.

Tests:

- Doctor access.
- External upload without device.
- MedicalRecords folder visibility.

### Phase 8: Statistics and Logs

Changes:

- Aggregate repositories and endpoints.
- Add activity log table only if required.

Tests:

- Date filtering.
- Counts by status, device, and type.
- Soft-deleted files excluded.

### Phase 9: End-to-End Verification

Required scenarios:

1. Doctor creates a multi-type request.
2. Secretary confirms payment.
3. Request remains undispatched.
4. Secretary later sends it to the technician.
5. Technician starts the request.
6. Technician uploads files using an active device.
7. Technician completes the request.
8. MedicalRecords displays the resulting folder and files.
9. Doctor attaches resulting images to a report.
10. Unauthorized and invalid transition attempts fail cleanly.

## 11. Questions and Assumptions

1. Should `confirm-payment` merely record secretary confirmation, or must it verify a paid invoice or payment record?
2. Should imaging requests link to an invoice, invoice item, or remain financially independent?
3. Should image types remain strings, or should a managed image-type catalog be introduced?
4. Does `send-to-technician` assign a specific technician, or should technicians claim requests from a shared queue?
5. Is one-request-at-a-time enforced per technician or globally across the imaging department?
6. Should all requested items be captured or explicitly skipped before completion?
7. Should direct doctor uploads require a registered device?
8. Should folder upload accept an archive, a directory-import result, or only multiple files?
9. What is the required file retention and physical-storage deletion policy?
10. Are imaging-specific activity logs required, or is the existing broad activity-log permission reserved for a future shared audit module?
11. Should secretary-created imaging appointments automatically create requests?
12. During migration, should legacy `request_type` store the first requested type, `multiple`, or a comma-separated summary?


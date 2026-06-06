# Appointments Module API Documentation

## Overview
The Appointments module provides a complete workflow for managing clinic appointments, from creation through completion. It supports secretary operations, doctor assignment, and queue management.

## Authentication
All endpoints require Bearer token authentication using `auth:sanctum`.

```
Authorization: Bearer {token}
```

## Base URL
```
/api/appointments
```

## Status Constants
- **booked**: Appointment created, awaiting confirmation
- **confirmed**: Appointment confirmed by secretary
- **waiting**: Patient checked in, waiting in queue
- **in_progress**: Examination in progress
- **completed**: Appointment completed
- **cancelled**: Appointment cancelled
- **no_show**: Patient did not show up (future implementation)

## Appointment Types
- consultation
- follow_up
- imaging
- consultation_and_imaging
- surgery_preparation

## Endpoints

### List Appointments
**GET** `/api/appointments`

Query Parameters (optional):
- `date`: Filter by specific date (YYYY-MM-DD)
- `date_from`: Filter from date (YYYY-MM-DD)
- `date_to`: Filter to date (YYYY-MM-DD)
- `status`: Filter by one status
- `type`: Filter by type
- `patient_id`: Filter by patient
- `doctor_id`: Filter by doctor
- `keyword`: Search in patient name, phone, file number, reason, notes
- `per_page`: Items per page (1-100, default: 15)

**Permissions Required**: VIEW_APPOINTMENTS

**Response**:
```json
{
  "success": true,
  "status_code": 200,
  "message": "Appointments fetched successfully.",
  "data": {
    "items": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 50,
      "last_page": 4,
      "from": 1,
      "to": 15,
      "has_more": true
    }
  }
}
```

### Create Appointment
**POST** `/api/appointments`

**Permissions Required**: CREATE_APPOINTMENT

**Request Body**:
```json
{
  "patient_id": 1,
  "doctor_id": null,
  "appointment_at": "2026-06-10 14:30:00",
  "type": "consultation",
  "reason": "Eye checkup",
  "notes": "Patient with blurred vision"
}
```

**Validation Rules**:
- `patient_id`: Required, must exist in clinic_patients table
- `doctor_id`: Optional, must exist in staff table
- `appointment_at`: Required, must be datetime in future
- `type`: Required, must be one of allowed types
- `reason`: Optional, max 1000 characters
- `notes`: Optional, max 2000 characters

**Business Rules**:
- Cannot create appointment for archived patients
- Cannot create appointment for deceased patients  
- Cannot create appointment for inactive patients

**Response**:
```json
{
  "success": true,
  "status_code": 201,
  "message": "Appointment created successfully.",
  "data": {
    "appointment": {...}
  }
}
```

### Get Today's Appointments
**GET** `/api/appointments/today`

**Permissions Required**: VIEW_APPOINTMENTS

**Query Parameters**: Same as list (optional)

### Get Queue List
**GET** `/api/appointments/queue`

**Permissions Required**: VIEW_APPOINTMENTS

Shows all appointments in "waiting" or "in_progress" status with queue numbers.

**Query Parameters**:
- `date`: Filter by date (default: today)
- `per_page`: Items per page (1-100)

### Get Doctor's Today Appointments
**GET** `/api/appointments/doctor/today`

**Permissions Required**: VIEW_APPOINTMENTS

Returns authenticated doctor's appointments for today.

**Query Parameters**: Same as list (optional)

### Show Appointment
**GET** `/api/appointments/{appointment}`

**Permissions Required**: VIEW_APPOINTMENTS

**Response**:
```json
{
  "success": true,
  "status_code": 200,
  "message": "Appointment fetched successfully.",
  "data": {
    "appointment": {...}
  }
}
```

### Update Appointment
**POST** `/api/appointments/{appointment}`

**Permissions Required**: CREATE_APPOINTMENT

**Request Body** (all optional):
```json
{
  "doctor_id": 2,
  "appointment_at": "2026-06-10 15:00:00",
  "type": "follow_up",
  "reason": "Updated reason",
  "notes": "Updated notes"
}
```

**Business Rules**:
- Cannot update cancelled or completed appointments
- If appointment_at is changed, appointment_date is automatically updated

### Confirm Appointment
**POST** `/api/appointments/{appointment}/confirm`

**Permissions Required**: CONFIRM_APPOINTMENT

**Status Transition**: booked → confirmed

**Response**:
```json
{
  "success": true,
  "status_code": 200,
  "message": "Appointment confirmed successfully.",
  "data": {
    "appointment": {...}
  }
}
```

### Cancel Appointment
**POST** `/api/appointments/{appointment}/cancel`

**Permissions Required**: CANCEL_APPOINTMENT

**Request Body**:
```json
{
  "cancel_reason": "Patient requested cancellation"
}
```

**Status Transitions**: booked/confirmed/waiting → cancelled

**Business Rules**:
- Cannot cancel appointments in progress or already completed
- cancel_reason is required

### Check-in Appointment
**POST** `/api/appointments/{appointment}/check-in`

**Permissions Required**: MANAGE_PATIENT_STATUS

**Request Body** (optional):
```json
{
  "notes": "Patient arrived on time"
}
```

**Status Transition**: booked/confirmed → waiting

**Auto-Generated**:
- queue_number: Generated per appointment_date (max existing + 1)
- checked_in_at: Current timestamp
- checked_in_by: Current staff ID

### Assign Doctor
**POST** `/api/appointments/{appointment}/assign-doctor`

**Permissions Required**: ASSIGN_PATIENT_TO_DOCTOR

**Request Body**:
```json
{
  "doctor_id": 5
}
```

**Business Rules**:
- Cannot assign to cancelled or completed appointments
- doctor_id must exist and be valid staff

### Start Appointment
**POST** `/api/appointments/{appointment}/start`

**Permissions Required**: MANAGE_PATIENT_STATUS

**Status Transition**: waiting → in_progress

**Business Rules**:
- Must have doctor_id assigned
- Can only start from waiting status

**Response**:
```json
{
  "success": true,
  "status_code": 200,
  "message": "Appointment examination started successfully.",
  "data": {
    "appointment": {...}
  }
}
```

### Complete Appointment
**POST** `/api/appointments/{appointment}/complete`

**Permissions Required**: MANAGE_PATIENT_STATUS

**Request Body** (optional):
```json
{
  "completion_notes": "Prescription issued for 30 days"
}
```

**Status Transition**: in_progress → completed

**Response**:
```json
{
  "success": true,
  "status_code": 200,
  "message": "Appointment completed successfully.",
  "data": {
    "appointment": {...}
  }
}
```

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "status_code": 401,
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "status_code": 403,
  "message": "You are not allowed to create appointments."
}
```

### 404 Not Found
```json
{
  "success": false,
  "status_code": 404,
  "message": "Appointment not found."
}
```

### 422 Unprocessable Entity
```json
{
  "success": false,
  "status_code": 422,
  "message": "Patient file is archived. Cannot create appointment.",
  "code": null,
  "errors": null
}
```

### 422 Validation Error
```json
{
  "success": false,
  "status_code": 422,
  "message": "Validation failed.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "patient_id": ["Patient ID is required."],
    "appointment_at": ["Appointment date and time must be in the future."]
  }
}
```

## Required Permissions

- VIEW_APPOINTMENTS: List, show, today, queue, doctor/today
- CREATE_APPOINTMENT: Create, update
- CONFIRM_APPOINTMENT: Confirm
- CANCEL_APPOINTMENT: Cancel
- MANAGE_PATIENT_STATUS: Check-in, start, complete
- ASSIGN_PATIENT_TO_DOCTOR: Assign doctor

## Workflow Examples

### Complete Appointment Workflow

1. **Secretary creates appointment**
   ```
   POST /api/appointments
   Status: booked
   ```

2. **Secretary confirms appointment**
   ```
   POST /api/appointments/{id}/confirm
   Status: booked → confirmed
   ```

3. **Patient arrives, check-in**
   ```
   POST /api/appointments/{id}/check-in
   Status: confirmed → waiting
   Queue number assigned
   ```

4. **Secretary assigns doctor**
   ```
   POST /api/appointments/{id}/assign-doctor
   ```

5. **Doctor starts examination**
   ```
   POST /api/appointments/{id}/start
   Status: waiting → in_progress
   ```

6. **Doctor completes examination**
   ```
   POST /api/appointments/{id}/complete
   Status: in_progress → completed
   ```

### Alternative: Cancel Appointment

At any time before examination starts:
```
POST /api/appointments/{id}/cancel
Status: booked/confirmed/waiting → cancelled
```

## Language Support

All messages and validation errors support multilingual responses based on Accept-Language header:

```
Accept-Language: ar  # Arabic
Accept-Language: en  # English (default)
```

## Notes

- Queue numbers are generated per appointment_date
- Cannot create appointments for archived/deceased/inactive patients
- All timestamps are stored in UTC
- Soft deletes are not used (appointments are permanent)
- Patient status (active/archived/deceased) is separate from appointment status

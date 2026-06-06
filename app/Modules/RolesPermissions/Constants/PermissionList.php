<?php

namespace App\Modules\RolesPermissions\Constants;

class PermissionList
{

    //  medical-center-admin

    const VIEW_STAFF              = 'view staff';
    const CREATE_STAFF            = 'create staff';
    const EDIT_STAFF              = 'edit staff';
    const TOGGLE_STAFF_STATUS     = 'toggle staff status';

    const ASSIGN_ROLES            = 'assign roles';
    const REVOKE_ROLES            = 'revoke roles';
    const OVERRIDE_PERMISSIONS    = 'override permissions';

    const CREATE_CLINIC_ROOM      = 'create clinic room';
    const EDIT_CLINIC_ROOM        = 'edit clinic room';
    const VIEW_CLINIC_ROOMS       = 'view clinic rooms';

    const CREATE_DEVICE           = 'create device';
    const EDIT_DEVICE             = 'edit device';
    const DELETE_DEVICE           = 'delete device';
    const TOGGLE_DEVICE_STATUS    = 'toggle device status';
    const VIEW_DEVICES            = 'view devices';

    const MANAGE_SCHEDULES        = 'manage schedules';
    const VIEW_SCHEDULES          = 'view schedules';
    const MANAGE_WORKING_HOURS    = 'manage working hours';
    const MANAGE_HOLIDAYS         = 'manage holidays';

    const EDIT_CENTER_SETTINGS    = 'edit center settings';

    const VIEW_STATISTICS         = 'view statistics';
    const VIEW_FINANCIAL_SUMMARY  = 'view financial summary';
    const VIEW_ACTIVITY_LOG       = 'view activity log';

    const MANAGE_PAYMENT_UNIT     = 'manage payment unit';

    const VIEW_PATIENTS           = 'view patients';
    const CREATE_PATIENT          = 'create patient';
    const EDIT_PATIENT            = 'edit patient';
    const SEARCH_PATIENT          = 'search patient';
    const ARCHIVE_PATIENT        = 'archive patient';
    const RESTORE_PATIENT        = 'restore patient';
    const VIEW_ARCHIVED_PATIENTS = 'view archived patients';

    const VIEW_APPOINTMENTS       = 'view appointments';
    const CREATE_APPOINTMENT      = 'create appointment';
    const CANCEL_APPOINTMENT      = 'cancel appointment';
    const CONFIRM_APPOINTMENT     = 'confirm appointment';

    const MANAGE_PATIENT_STATUS   = 'manage patient status';
    const ASSIGN_PATIENT_TO_DOCTOR = 'assign patient to doctor';

    const BOOK_FOLLOW_UP          = 'book follow up';
    const BOOK_SURGERY            = 'book surgery';

    const VIEW_MEDICAL_RECORDS    = 'view medical records';
    const EDIT_MEDICAL_RECORDS    = 'edit medical records';

    const VIEW_VISIT_TIMELINE     = 'view visit timeline';
    const CREATE_VISIT_RECORD     = 'create visit record';

    const VIEW_IMAGING_TIMELINE   = 'view imaging timeline';
    const COMPARE_IMAGES          = 'compare images';
    const ANNOTATE_IMAGES         = 'annotate images';

    const VIEW_REPORTS            = 'view reports';
    const CREATE_REPORT           = 'create report';
    const PRINT_REPORT            = 'print report';
    const EXPORT_REPORT           = 'export report';

    const VIEW_PRESCRIPTIONS      = 'view prescriptions';
    const CREATE_PRESCRIPTION     = 'create prescription';
    const PRINT_PRESCRIPTION      = 'print prescription';

    const VIEW_MEASUREMENTS       = 'view measurements';
    const CREATE_MEASUREMENT      = 'create measurement';
    const PRINT_MEASUREMENT       = 'print measurement';

    const VIEW_DIAGNOSES          = 'view diagnoses';
    const CREATE_DIAGNOSIS        = 'create diagnosis';

    const VIEW_DISEASE_CLASSIFICATION = 'view disease classification';
    const ADD_DISEASE_CLASSIFICATION  = 'add disease classification';

    const VIEW_OWN_NOTES          = 'view own notes';
    const CREATE_NOTE             = 'create note';

    const CREATE_IMAGING_REQUEST   = 'create imaging request';
    const VIEW_IMAGING_REQUESTS    = 'view imaging requests';
    const CONFIRM_IMAGING_REQUEST  = 'confirm imaging request';
    const UPLOAD_IMAGING_FILES     = 'upload imaging files';
    const DELETE_IMAGING_FILE      = 'delete imaging file';
    const UPDATE_IMAGING_STATUS    = 'update imaging request status';
    const VIEW_IMAGING_QUEUE       = 'view imaging queue';
    const MANAGE_IMAGING_QUEUE     = 'manage imaging queue';

    const VIEW_SURGERIES          = 'view surgeries';
    const CREATE_SURGERY          = 'create surgery';
    const CONFIRM_SURGERY         = 'confirm surgery';

    const VIEW_INVOICES           = 'view invoices';
    const CREATE_INVOICE          = 'create invoice';
    const RECORD_PAYMENT          = 'record payment';
    const PRINT_INVOICE           = 'print invoice';
    const VIEW_FINANCIAL_DETAILS  = 'view financial details';

    const ASSIGN_REPORT_TO_SECRETARY = 'assign report to secretary';
    const VIEW_ASSIGNED_REPORTS      = 'view assigned reports';
    const SUBMIT_REPORT_DRAFT        = 'submit report draft';
    const APPROVE_REPORT_DRAFT       = 'approve report draft';

    const VIEW_CONVERSATIONS      = 'view conversations';
    const REPLY_CONVERSATIONS     = 'reply conversations';
    const MANAGE_CHAT             = 'manage chat';



    public static function all(): array
    {
        $reflection = new \ReflectionClass(static::class);
        return array_values(
            array_filter(
                $reflection->getConstants(),
                fn($value) => is_string($value)
            )
        );
    }
}

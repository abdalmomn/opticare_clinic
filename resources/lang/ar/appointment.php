<?php

return [

    /*
    |--------------------------------------------------------------------------
    | وحدة المواعيد – الترجمة العربية
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'appointments_fetched' => 'تم جلب المواعيد بنجاح.',
        'appointment_fetched' => 'تم جلب الموعد بنجاح.',
        'appointment_created' => 'تم إنشاء الموعد بنجاح.',
        'appointment_updated' => 'تم تحديث الموعد بنجاح.',
        'appointment_confirmed' => 'تم تأكيد الموعد بنجاح.',
        'appointment_cancelled' => 'تم إلغاء الموعد بنجاح.',
        'appointment_checked_in' => 'تم تسجيل وصول المريض بنجاح.',
        'doctor_assigned' => 'تم تعيين الطبيب للموعد بنجاح.',
        'appointment_started' => 'تم بدء الفحص بنجاح.',
        'appointment_completed' => 'تم إكمال الموعد بنجاح.',
        'queue_fetched' => 'تم جلب قائمة الانتظار بنجاح.',
        'today_appointments_fetched' => 'تم جلب مواعيد اليوم بنجاح.',
        'doctor_today_appointments_fetched' => 'تم جلب مواعيد الطبيب لليوم بنجاح.',
    ],

    'errors' => [
        'not_allowed_view' => 'لا يسمح لك بعرض المواعيد.',
        'not_allowed_create' => 'لا يسمح لك بإنشاء مواعيد.',
        'not_allowed_update' => 'لا يسمح لك بتحديث المواعيد.',
        'not_allowed_confirm' => 'لا يسمح لك بتأكيد المواعيد.',
        'not_allowed_cancel' => 'لا يسمح لك بإلغاء المواعيد.',
        'not_allowed_manage_status' => 'لا يسمح لك بإدارة حالة الموعد.',
        'not_allowed_assign_doctor' => 'لا يسمح لك بتعيين الأطباء للمواعيد.',
        'appointment_not_found' => 'الموعد غير موجود.',
        'patient_not_found' => 'المريض غير موجود.',
        'patient_inactive' => 'حساب المريض غير نشط. لا يمكن إنشاء موعد.',
        'patient_archived' => 'ملف المريض مؤرشف. لا يمكن إنشاء موعد.',
        'patient_deceased' => 'المريض مسجل كمتوفى. لا يمكن إنشاء موعد.',
        'doctor_not_found' => 'الطبيب غير موجود.',
        'invalid_status_transition' => 'انتقال حالة غير صحيح لهذا الموعد.',
        'appointment_already_cancelled' => 'الموعد ملغى بالفعل.',
        'appointment_already_completed' => 'الموعد مكتمل بالفعل.',
        'cannot_cancel_appointment' => 'لا يمكن إلغاء موعد جاري أو مكتمل.',
        'cannot_update_appointment' => 'لا يمكن تحديث موعد ملغى أو مكتمل.',
        'cannot_start_without_doctor' => 'لا يمكن بدء الموعد بدون طبيب معين.',
        'cannot_assign_doctor' => 'لا يمكن تعيين طبيب لموعد ملغى أو مكتمل.',
        'selected_staff_is_not_doctor' => 'الموظف المختار ليس طبيباً.',
    ],

    'validation' => [
        'patient_id_required' => 'معرف المريض مطلوب.',
        'patient_id_invalid' => 'معرف المريض غير صحيح أو المريض غير موجود.',
        'doctor_id_required' => 'معرف الطبيب مطلوب.',
        'doctor_id_invalid' => 'معرف الطبيب غير صحيح أو الطبيب غير موجود.',
        'appointment_at_required' => 'تاريخ ووقت الموعد مطلوب.',
        'appointment_at_invalid' => 'تاريخ ووقت الموعد يجب أن يكون تاريخاً صحيحاً.',
        'appointment_at_past' => 'تاريخ ووقت الموعد يجب أن يكون في المستقبل.',
        'type_required' => 'نوع الموعد مطلوب.',
        'type_invalid' => 'نوع الموعد غير صحيح.',
        'reason_max' => 'السبب قد لا يزيد عن 1000 حرف.',
        'notes_max' => 'الملاحظات قد لا تزيد عن 2000 حرف.',
        'cancel_reason_required' => 'سبب الإلغاء مطلوب.',
        'cancel_reason_max' => 'سبب الإلغاء قد لا يزيد عن 1000 حرف.',
        'completion_notes_max' => 'ملاحظات الإكمال قد لا تزيد عن 2000 حرف.',
        'date_invalid' => 'التاريخ يجب أن يكون تاريخاً صحيحاً.',
        'date_from_invalid' => 'تاريخ البداية يجب أن يكون تاريخاً صحيحاً.',
        'date_to_invalid' => 'تاريخ النهاية يجب أن يكون تاريخاً صحيحاً.',
        'date_to_before_from' => 'تاريخ النهاية يجب أن يكون بعد أو مساوياً لتاريخ البداية.',
        'status_invalid' => 'الحالة غير صحيحة.',
        'keyword_max' => 'الكلمة المفتاحية قد لا تزيد عن 255 حرف.',
        'per_page_max' => 'عدد العناصر في الصفحة قد لا يزيد عن 100.',
    ],
];

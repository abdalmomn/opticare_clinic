<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MedicalRecords Module – Arabic Translations
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'private_note_fetched' => 'تم جلب الملاحظة الخاصة بنجاح.',
        'unified_record_fetched' => 'تم جلب الملف الطبي الموحّد للمريض بنجاح.',
        'visit_session_fetched' => 'تم جلب جلسة المعاينة بنجاح.',
        'visit_session_created' => 'تم إنشاء جلسة المعاينة بنجاح.',
        'visit_session_saved' => 'تم حفظ جلسة المعاينة بنجاح.',
        'visit_finalized' => 'تم إنهاء جلسة المعاينة بنجاح.',
        'visits_timeline_fetched' => 'تم جلب الخط الزمني للزيارات بنجاح.',
        'reports_timeline_fetched' => 'تم جلب الخط الزمني للتقارير بنجاح.',
        'prescriptions_timeline_fetched' => 'تم جلب الخط الزمني للوصفات الطبية بنجاح.',
        'measurements_timeline_fetched' => 'تم جلب الخط الزمني للقياسات بنجاح.',
        'diagnoses_timeline_fetched' => 'تم جلب الخط الزمني للتشخيصات بنجاح.',
        'private_notes_timeline_fetched' => 'تم جلب الخط الزمني للملاحظات الخاصة بنجاح.',
        'diagnosis_codes_fetched' => 'تم جلب رموز التشخيص بنجاح.',
        'image_folders_fetched' => 'تم جلب مجلدات الصور بنجاح.',
        'folder_files_fetched' => 'تم جلب صور المجلد بنجاح.',
        'image_comparison_fetched' => 'تم جلب مقارنة الصور بنجاح.',
        'image_types_fetched' => 'تم جلب أنواع الصور بنجاح.',
        'imaging_file_fetched' => 'تم جلب الصورة بنجاح.',
        'image_note_saved' => 'تم حفظ ملاحظة الصورة بنجاح.',
        'report_images_attached' => 'تم إرفاق صور التقرير بنجاح.',
    ],

    'errors' => [
        'private_note_not_found' => 'الملاحظة الخاصة غير موجودة.',
        'not_allowed_view_record' => 'لا تملك صلاحية عرض هذا الملف الطبي.',
        'not_allowed_view_timeline' => 'لا تملك صلاحية عرض هذا الخط الزمني.',
        'not_allowed_create_visit' => 'لا تملك صلاحية إنشاء أو فتح جلسة معاينة.',
        'not_allowed_save_session' => 'لا تملك صلاحية حفظ جلسة المعاينة هذه.',
        'not_allowed_finalize' => 'لا تملك صلاحية إنهاء جلسة المعاينة هذه.',
        'not_allowed_create_measurement' => 'لا تملك صلاحية تسجيل قياسات العين.',
        'not_allowed_create_report' => 'لا تملك صلاحية إنشاء التقارير الطبية.',
        'not_allowed_create_prescription' => 'لا تملك صلاحية إنشاء الوصفات الطبية.',
        'not_allowed_create_diagnosis' => 'لا تملك صلاحية إنشاء التشخيصات.',
        'not_allowed_add_disease_classification' => 'لا تملك صلاحية إضافة رموز تصنيف الأمراض.',
        'not_allowed_create_note' => 'لا تملك صلاحية إنشاء الملاحظات الخاصة.',
        'not_allowed_view_diagnosis_codes' => 'لا تملك صلاحية عرض رموز التشخيص.',
        'not_allowed_view_notes' => 'لا تملك صلاحية عرض الملاحظات الخاصة.',

        'patient_not_found' => 'المريض غير موجود.',
        'appointment_not_found' => 'الموعد غير موجود.',
        'visit_not_found' => 'جلسة المعاينة غير موجودة.',

        'visit_already_finalized' => 'تم إنهاء جلسة المعاينة هذه ولا يمكن تعديلها بعد الآن.',
        'visit_cancelled' => 'جلسة المعاينة هذه ملغاة ولا يمكن تعديلها.',

        'appointment_not_in_progress' => 'لا يمكن فتح جلسة معاينة إلا لموعد قيد الفحص.',
        'appointment_no_doctor' => 'لا يوجد طبيب مسند لهذا الموعد.',
        'appointment_doctor_mismatch' => 'أنت لست الطبيب المسند لهذا الموعد.',
        'visit_doctor_mismatch' => 'أنت لست الطبيب صاحب جلسة المعاينة هذه.',
        'invalid_diagnosis_codes' => 'رمز تشخيص واحد أو أكثر غير صالح أو غير مُفعّل.',
        'invalid_imaging_reference' => 'مرجع تصوير واحد أو أكثر غير صالح.',

        'not_allowed_view_image_folders' => 'لا تملك صلاحية عرض مجلدات الصور.',
        'not_allowed_compare_images' => 'لا تملك صلاحية مقارنة الصور.',
        'not_allowed_save_image_note' => 'لا تملك صلاحية حفظ ملاحظات الصور.',
        'not_allowed_attach_images' => 'لا تملك صلاحية إرفاق الصور بالتقارير.',
        'folder_not_found' => 'مجلد الصور غير موجود.',
        'folder_not_for_patient' => 'مجلد الصور لا يخص هذا المريض.',
        'folder_missing_type' => 'المجلد لا يحتوي على صور من النوع المطلوب.',
        'file_not_found' => 'الصورة غير موجودة.',
        'file_not_for_patient' => 'الصورة لا تخص هذا المريض.',
        'files_type_mismatch' => 'يجب أن تكون الصورتان من نفس نوع الصورة.',
        'report_not_found' => 'التقرير الطبي غير موجود.',
        'image_not_for_patient' => 'صورة واحدة أو أكثر من المحدد لا تخص مريض التقرير.',
    ],

    'validation' => [
        'visit_type_invalid' => 'نوع المعاينة غير صالح.',
        'chief_complaint_max' => 'الشكوى الرئيسية يجب ألا تتجاوز 2000 حرف.',
        'symptoms_max' => 'الأعراض يجب ألا تتجاوز 2000 حرف.',
        'examination_notes_max' => 'ملاحظات الفحص يجب ألا تتجاوز 5000 حرف.',
        'diagnosis_max' => 'التشخيص يجب ألا يتجاوز 5000 حرف.',
        'treatment_plan_max' => 'الخطة العلاجية يجب ألا تتجاوز 5000 حرف.',
        'notes_max' => 'الملاحظات يجب ألا تتجاوز 5000 حرف.',

        'measured_at_invalid' => 'تاريخ القياس يجب أن يكون تاريخاً صالحاً.',
        'iop_invalid' => 'ضغط العين يجب أن يكون رقماً صالحاً.',
        'visual_acuity_max' => 'الرؤية البصرية يجب ألا تتجاوز 50 حرف.',

        'report_title_max' => 'عنوان التقرير يجب ألا يتجاوز 255 حرف.',
        'report_text_max' => 'نص التقرير يجب ألا يتجاوز 20000 حرف.',
        'imaging_request_invalid' => 'طلب التصوير غير صالح أو غير موجود.',
        'imaging_file_invalid' => 'ملف التصوير غير صالح أو غير موجود.',

        'prescription_text_max' => 'نص الوصفة الطبية يجب ألا يتجاوز 20000 حرف.',
        'medicine_name_required' => 'اسم الدواء مطلوب لكل عنصر في الوصفة.',
        'medicine_name_max' => 'اسم الدواء يجب ألا يتجاوز 255 حرف.',

        'diagnosis_codes_array' => 'رموز التشخيص يجب أن تكون قائمة من المعرّفات.',
        'diagnosis_code_invalid' => 'رمز تشخيص محدد غير صالح أو غير موجود.',

        'note_max' => 'الملاحظة الخاصة يجب ألا تتجاوز 5000 حرف.',
        'visibility_invalid' => 'قيمة ظهور الملاحظة غير صالحة.',

        'search_max' => 'كلمة البحث يجب ألا تتجاوز 255 حرف.',
        'per_page_max' => 'عدد العناصر لكل صفحة يجب ألا يتجاوز 100.',
        'date_invalid' => 'التاريخ يجب أن يكون تاريخاً صالحاً.',
        'status_invalid' => 'قيمة الحالة غير صالحة.',
        'left_folder_required' => 'المجلد الأيسر مطلوب.',
        'right_folder_required' => 'المجلد الأيمن مطلوب.',
        'folders_must_differ' => 'يجب أن يكون المجلدان المراد مقارنتهما مختلفين.',
        'folder_invalid' => 'مجلد الصور المحدد غير صالح أو غير موجود.',
    ],
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Patient Module – Arabic Translations (العربية)
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'patients_fetched'       => 'تم جلب بيانات المرضى بنجاح.',
        'patient_fetched'        => 'تم جلب ملف المريض بنجاح.',
        'patient_created'        => 'تم إنشاء ملف المريض بنجاح.',
        'patient_updated'        => 'تم تحديث ملف المريض بنجاح.',
        'patient_status_updated' => 'تم تحديث حالة المريض بنجاح.',
        'patient_archived' => 'تمت أرشفة ملف المريض بنجاح.',
        'patient_restored' => 'تمت استعادة ملف المريض بنجاح.',
        'patient_marked_deceased' => 'تم تعليم ملف المريض كمتوفى بنجاح.',
    ],

    'errors' => [
        'not_allowed_view'   => 'ليست لديك صلاحية لعرض المرضى.',
        'not_allowed_search' => 'ليست لديك صلاحية للبحث عن المرضى.',
        'not_allowed_create' => 'ليست لديك صلاحية لإنشاء ملف مريض.',
        'not_allowed_edit'   => 'ليست لديك صلاحية لتعديل ملف المريض.',
        'identity_exists'    => 'يوجد مريض مسجل بهذا الرقم الوطني أو رقم جواز السفر مسبقاً.',
        'patient_not_found'  => 'لم يتم العثور على ملف المريض.',
        'not_allowed_archive' => 'ليست لديك صلاحية لأرشفة ملفات المرضى.',
        'not_allowed_restore' => 'ليست لديك صلاحية لاستعادة ملفات المرضى.',
        'patient_already_archived' => 'ملف المريض مؤرشف بالفعل.',
        'patient_is_not_archived' => 'ملف المريض غير مؤرشف.',
        'patient_already_deceased' => 'ملف المريض معلّم كمتوفى بالفعل.',
        'deceased_patient_cannot_be_archived' => 'لا يمكن أرشفة مريض معلّم كمتوفى مرة أخرى.',
        'deceased_patient_cannot_be_restored' => 'لا يمكن استعادة ملف مريض معلّم كمتوفى.',
        'patient_status_cannot_be_toggled' => 'لا يمكن تغيير حالة مريض مؤرشف أو متوفى عبر التفعيل والتعطيل.',
    ],

    'validation' => [
        'first_name_required' => 'الاسم الأول مطلوب.',
        'first_name_max'      => 'الاسم الأول يجب ألا يتجاوز 255 حرفاً.',
        'last_name_required'  => 'اسم العائلة مطلوب.',
        'last_name_max'       => 'اسم العائلة يجب ألا يتجاوز 255 حرفاً.',

        'identity_type_required'   => 'نوع الهوية مطلوب.',
        'identity_type_in'         => 'نوع الهوية يجب أن يكون إما رقم وطني أو جواز سفر.',
        'identity_number_required' => 'رقم الهوية مطلوب.',
        'identity_number_max'      => 'رقم الهوية يجب ألا يتجاوز 50 حرفاً.',
        'identity_number_unique'   => 'يوجد مريض مسجل بهذا الرقم مسبقاً.',

        'gender_required'   => 'الجنس مطلوب.',
        'gender_in'         => 'الجنس يجب أن يكون ذكر أو أنثى.',
        'marital_status_in' => 'الحالة الاجتماعية يجب أن تكون: أعزب، متزوج، مطلق، أو أرمل.',
        'phone_max'         => 'رقم الهاتف يجب ألا يتجاوز 30 رمزاً.',

        'date_of_birth_date'            => 'تاريخ الميلاد يجب أن يكون تاريخاً صحيحاً.',
        'date_of_birth_before_or_equal' => 'تاريخ الميلاد لا يمكن أن يكون في المستقبل.',

        'height_cm_numeric' => 'الطول يجب أن يكون قيمة رقمية.',
        'height_cm_min'     => 'الطول يجب أن يكون على الأقل 0 سنتيمتر.',
        'height_cm_max'     => 'الطول لا يمكن أن يتجاوز 300 سنتيمتر.',
        'weight_kg_numeric' => 'الوزن يجب أن يكون قيمة رقمية.',
        'weight_kg_min'     => 'الوزن يجب أن يكون على الأقل 0 كيلوغرام.',
        'weight_kg_max'     => 'الوزن لا يمكن أن يتجاوز 500 كيلوغرام.',

        'keyword_max'      => 'كلمة البحث لا يمكن أن تتجاوز 255 حرفاً.',
        'per_page_integer' => 'عدد العناصر في الصفحة يجب أن يكون رقماً صحيحاً.',
        'per_page_min'     => 'عدد العناصر في الصفحة يجب أن يكون 1 على الأقل.',
        'per_page_max'     => 'عدد العناصر في الصفحة لا يتجاوز 100.',

        'status_in' => 'الحالة يجب أن تكون active أو inactive أو archived أو deceased.',
        'include_archived_boolean' => 'خيار تضمين المؤرشفين يجب أن يكون true أو false.',
        'archive_reason_required' => 'سبب الأرشفة مطلوب.',
        'archive_reason_in' => 'سبب الأرشفة غير صالح.',
        'archive_notes_string' => 'ملاحظات الأرشفة يجب أن تكون نصاً.',
        'archive_notes_max' => 'ملاحظات الأرشفة يجب ألا تتجاوز 2000 حرف.',
        'deceased_at_required' => 'تاريخ الوفاة مطلوب.',
        'deceased_at_date' => 'تاريخ الوفاة يجب أن يكون تاريخاً صالحاً.',
        'deceased_at_before_or_equal' => 'تاريخ الوفاة لا يمكن أن يكون في المستقبل.',
    ],

];

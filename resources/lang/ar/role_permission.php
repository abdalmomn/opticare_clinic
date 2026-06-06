<?php

return [
    'messages' => [
        'roles_fetched' => 'تم جلب الأدوار بنجاح.',
        'permissions_fetched' => 'تم جلب الصلاحيات بنجاح.',
        'role_assigned' => 'تم إسناد الدور بنجاح.',
        'role_revoked' => 'تم سحب الدور بنجاح.',
        'permission_granted' => 'تم منح الصلاحية بنجاح.',
        'permission_revoked' => 'تم منع الصلاحية بنجاح.',
        'permission_override_cleared' => 'تم إزالة تعديل الصلاحية بنجاح.',
        'all_permission_overrides_cleared' => 'تم إزالة جميع تعديلات الصلاحيات بنجاح.',
    ],

    'errors' => [
        'role_cannot_be_assigned_inside_clinic_system' => 'لا يمكن إسناد هذا الدور داخل نظام العيادة.',
        'not_allowed_assign_roles' => 'ليست لديك صلاحية لإسناد الأدوار.',
        'not_allowed_revoke_roles' => 'ليست لديك صلاحية لسحب الأدوار.',
        'cannot_revoke_own_admin_role' => 'لا يمكنك سحب الدور الإداري الخاص بك.',
        'staff_does_not_have_role' => 'الموظف المحدد لا يملك هذا الدور.',
        'not_allowed_override_permissions' => 'ليست لديك صلاحية لتعديل صلاحيات الموظفين.',
        'staff_has_no_permission_overrides' => 'الموظف المحدد لا يملك أي تعديلات على الصلاحيات.',
    ],

    'validation' => [
        'staff_required' => 'الموظف مطلوب.',
        'staff_exists' => 'الموظف المحدد غير موجود.',

        'role_required' => 'الدور مطلوب.',
        'role_invalid' => 'الدور المحدد غير صالح.',

        'permission_required' => 'الصلاحية مطلوبة.',
        'permission_invalid' => 'الصلاحية المحددة غير صالحة.',

        'clinic_id_integer' => 'رقم العيادة يجب أن يكون رقماً صالحاً.',

        'is_temporary_boolean' => 'حالة المؤقت يجب أن تكون true أو false.',

        'expires_at_date' => 'تاريخ الانتهاء يجب أن يكون تاريخاً صالحاً.',
        'expires_at_after' => 'تاريخ الانتهاء يجب أن يكون في المستقبل.',

        'notes_string' => 'الملاحظات يجب أن تكون نصاً.',
        'notes_max' => 'يجب ألا تتجاوز الملاحظات 1000 حرف.',
    ],

    'roles' => [
        'medical_center_admin' => 'مدير المركز الطبي',
        'doctor' => 'طبيب',
        'secretary' => 'سكرتير/سكرتيرة',
        'imaging_technician' => 'فني تصوير',
    ],
];

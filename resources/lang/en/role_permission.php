<?php

return [
    'messages' => [
        'roles_fetched' => 'Roles fetched successfully.',
        'permissions_fetched' => 'Permissions fetched successfully.',
        'role_assigned' => 'Role assigned successfully.',
        'role_revoked' => 'Role revoked successfully.',
        'permission_granted' => 'Permission granted successfully.',
        'permission_revoked' => 'Permission revoked successfully.',
        'permission_override_cleared' => 'Permission override cleared successfully.',
        'all_permission_overrides_cleared' => 'All permission overrides cleared successfully.',
    ],

    'errors' => [
        'role_cannot_be_assigned_inside_clinic_system' => 'This role cannot be assigned inside clinic system.',
        'not_allowed_assign_roles' => 'You are not allowed to assign roles.',
        'not_allowed_revoke_roles' => 'You are not allowed to revoke roles.',
        'cannot_revoke_own_admin_role' => 'You cannot revoke your own administrative role.',
        'staff_does_not_have_role' => 'The selected staff member does not have this role.',
        'not_allowed_override_permissions' => 'You are not allowed to override permissions.',
        'staff_has_no_permission_overrides' => 'The selected staff member does not have any permission overrides.',
    ],

    'validation' => [
        'staff_required' => 'Staff member is required.',
        'staff_exists' => 'Selected staff member does not exist.',

        'role_required' => 'Role is required.',
        'role_invalid' => 'Selected role is not valid.',

        'permission_required' => 'Permission is required.',
        'permission_invalid' => 'Selected permission is not valid.',

        'clinic_id_integer' => 'Clinic ID must be a valid number.',

        'is_temporary_boolean' => 'Temporary status must be true or false.',

        'expires_at_date' => 'Expiration date must be a valid date.',
        'expires_at_after' => 'Expiration date must be in the future.',

        'notes_string' => 'Notes must be text.',
        'notes_max' => 'Notes may not be greater than 1000 characters.',
    ],

    'roles' => [
        'medical_center_admin' => 'Medical Center Admin',
        'doctor' => 'Doctor',
        'secretary' => 'Secretary',
        'imaging_technician' => 'Imaging Technician',
    ],
];

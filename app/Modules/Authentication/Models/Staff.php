<?php

namespace App\Modules\Authentication\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\MedicalRecords\Models\VitalSign;
use App\Modules\MedicalRecords\Models\Prescription;
use App\Modules\MedicalRecords\Models\Surgery;
use App\Modules\MedicalRecords\Models\SharedMedicalFile;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\RolesPermissions\Models\StaffClinicRole;

class Staff extends Authenticatable
{
    use HasApiTokens, HasRoles;

    protected $guard_name = 'api';

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = ['password'];

    protected $casts = ['password' => 'hashed'];

    // -------------------------
    // Relationships
    // -------------------------

    public function visitRecords(): HasMany
    {
        return $this->hasMany(VisitRecord::class, 'doctor_id');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class, 'recorded_by');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    public function surgeries(): HasMany
    {
        return $this->hasMany(Surgery::class, 'doctor_id');
    }

    public function sharedMedicalFiles(): HasMany
    {
        return $this->hasMany(SharedMedicalFile::class, 'uploaded_by');
    }

    public function privateNotes(): HasMany
    {
        return $this->hasMany(DoctorPrivateNote::class, 'doctor_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'requested_by');
    }

    public function imagingFiles(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'uploaded_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_staff_id');
    }

    public function clinicDevices(): HasMany
    {
        return $this->hasMany(ClinicDevice::class, 'created_by');
    }

    public function schedules()
    {
        return $this->morphMany(\App\Modules\Scheduling\Models\Schedule::class, 'schedulable');
    }

    public function scheduleExceptions()
    {
        return $this->morphMany(\App\Modules\Scheduling\Models\ScheduleException::class, 'schedulable');
    }


    public function clinicRoles(): HasMany
    {
        return $this->hasMany(StaffClinicRole::class, 'staff_id');
    }
    public function activeClinicRoles(): HasMany
    {
        return $this->hasMany(StaffClinicRole::class, 'staff_id')
                    ->active();
    }

    public function belongsToClinic(int $clinicId): bool
    {
        return $this->clinicRoles()
                    ->where('clinic_id', $clinicId)
                    ->active()
                    ->exists();
    }

    public function roleInClinic(int $clinicId): ?StaffClinicRole
    {
        return $this->clinicRoles()
                    ->where('clinic_id', $clinicId)
                    ->active()
                    ->first();
    }

    public function isVisitingExpired(): bool
    {
        if (! $this->hasRole('visiting_doctor')) {
            return false;
        }

        return $this->clinicRoles()
                    ->where('role_name', 'visiting_doctor')
                    ->expired()
                    ->exists();
    }
}

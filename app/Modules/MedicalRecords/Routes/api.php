<?php

use App\Modules\MedicalRecords\Controllers\DiagnosisCodeController;
use App\Modules\MedicalRecords\Controllers\ImagingFileNoteController;
use App\Modules\MedicalRecords\Controllers\MedicalRecordController;
use App\Modules\MedicalRecords\Controllers\MedicalReportImageController;
use App\Modules\MedicalRecords\Controllers\PatientImageFolderController;
use App\Modules\MedicalRecords\Controllers\VisitSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/diagnosis-codes', [DiagnosisCodeController::class, 'index']);

    Route::controller(MedicalRecordController::class)->group(function () {
        Route::get('/patients/{patient}/unified-record', 'unifiedRecord');
        Route::get('/patients/{patient}/timeline/visits', 'visitsTimeline');
        Route::get('/patients/{patient}/timeline/reports', 'reportsTimeline');
        Route::get('/patients/{patient}/timeline/prescriptions', 'prescriptionsTimeline');
        Route::get('/patients/{patient}/timeline/measurements', 'measurementsTimeline');
        Route::get('/patients/{patient}/timeline/diagnoses', 'diagnosesTimeline');
        Route::get('/patients/{patient}/timeline/private-notes', 'privateNotesTimeline');
        Route::get('/private-notes/{note}', 'privateNoteDetails');
    });

    Route::controller(VisitSessionController::class)->group(function () {
        Route::get('/appointments/{appointment}/visit-session', 'show');
        Route::post('/appointments/{appointment}/visit-session', 'open');
        Route::post('/visits/{visit}/save-session', 'save');
        Route::post('/visits/{visit}/finalize', 'finalize');
    });

    Route::controller(PatientImageFolderController::class)->group(function () {
        Route::get('/patients/{patient}/image-types', 'imageTypes');
        Route::get('/patients/{patient}/image-folders', 'imageFolders');
        Route::get('/patients/{patient}/image-comparison/files', 'imageComparisonFiles');
        Route::get('/patients/{patient}/image-comparison', 'imageComparison');
        Route::get('/image-folders/{folder}/files', 'folderFiles');
        Route::get('/imaging-files/{file}', 'imagingFile');
    });

    Route::post('/imaging-files/{file}/notes', [ImagingFileNoteController::class, 'store']);

    Route::post('/reports/{report}/images', [MedicalReportImageController::class, 'store']);
});

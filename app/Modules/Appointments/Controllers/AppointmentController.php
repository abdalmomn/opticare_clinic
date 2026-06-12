<?php

namespace App\Modules\Appointments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Requests\AssignDoctorRequest;
use App\Modules\Appointments\Requests\CancelAppointmentRequest;
use App\Modules\Appointments\Requests\CheckInAppointmentRequest;
use App\Modules\Appointments\Requests\CompleteAppointmentRequest;
use App\Modules\Appointments\Requests\ListAppointmentsRequest;
use App\Modules\Appointments\Requests\StoreAppointmentRequest;
use App\Modules\Appointments\Requests\UpdateAppointmentRequest;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Core\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $service
    ) {}

    public function index(ListAppointmentsRequest $request): JsonResponse
    {
        $result = $this->service->listAppointments(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointments_fetched')
        );
    }

    public function today(ListAppointmentsRequest $request): JsonResponse
    {
        $result = $this->service->todayAppointments(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.today_appointments_fetched')
        );
    }

    public function queue(ListAppointmentsRequest $request): JsonResponse
    {
        $result = $this->service->queue(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.queue_fetched')
        );
    }

    public function doctorToday(ListAppointmentsRequest $request): JsonResponse
    {
        $result = $this->service->doctorTodayAppointments(
            Auth::user(),
            $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.doctor_today_appointments_fetched')
        );
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $result = $this->service->createAppointment(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            data: $result,
            message: __('appointment.messages.appointment_created')
        );
    }

    public function show(int $appointment): JsonResponse
    {
        $result = $this->service->showAppointment(
            $appointment,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointment_fetched')
        );
    }

    public function update(UpdateAppointmentRequest $request, int $appointment): JsonResponse
    {
        $result = $this->service->updateAppointment(
            $appointment,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointment_updated')
        );
    }

    public function confirm(int $appointment): JsonResponse
    {
        $result = $this->service->confirmAppointment(
            $appointment,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointment_confirmed')
        );
    }

    public function cancel(CancelAppointmentRequest $request, int $appointment): JsonResponse
    {
        $result = $this->service->cancelAppointment(
            $appointment,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointment_cancelled')
        );
    }

    public function checkIn(CheckInAppointmentRequest $request, int $appointment): JsonResponse
    {
        $result = $this->service->checkInAppointment(
            $appointment,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointment_checked_in')
        );
    }

    public function assignDoctor(AssignDoctorRequest $request, int $appointment): JsonResponse
    {
        $result = $this->service->assignDoctor(
            $appointment,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.doctor_assigned')
        );
    }

    public function start(int $appointment): JsonResponse
    {
        $result = $this->service->startAppointment(
            $appointment,
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointment_started')
        );
    }

    public function complete(CompleteAppointmentRequest $request, int $appointment): JsonResponse
    {
        $result = $this->service->completeAppointment(
            $appointment,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            data: $result,
            message: __('appointment.messages.appointment_completed')
        );
    }
}

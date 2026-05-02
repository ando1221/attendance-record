<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('status_id');
            $table->dateTime('requested_clock_in_at')->nullable();
            $table->dateTime('requested_clock_out_at')->nullable();
            $table->text('requested_note')->nullable();
            $table->timestamps();

            $table->foreign('attendance_id', 'acr_attendance_id_fk')
                ->references('id')
                ->on('attendances')
                ->onDelete('cascade');

            $table->foreign('user_id', 'acr_user_id_fk')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('status_id', 'acr_status_id_fk')
                ->references('id')
                ->on('attendance_correction_request_statuses')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
};

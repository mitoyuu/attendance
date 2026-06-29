<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakTimeCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_time_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stamp_correction_request_id');
            // 文字数オーバーエラー回避のため『fk_break_time_correction』に手動命名
            $table->foreign('stamp_correction_request_id', 'fk_break_time_correction')
                ->references('id')
                ->on('stamp_correction_requests')
                ->cascadeOnDelete();
            $table->timestamp('requested_break_start')->nullable();
            $table->timestamp('requested_break_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_time_correction_requests');
    }
}

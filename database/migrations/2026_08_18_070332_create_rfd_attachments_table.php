<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('rfd_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfd_header_id');
            $table->foreign('rfd_header_id')->references('id')->on('rfd_headers');
            $table->string('file_name');
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->timestamps();

            $table->index('rfd_header_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rfd_attachments');
    }
};
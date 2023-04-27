<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePiutangsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('piutangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualans');
            $table->date('tanggal_bayar')->nullable();
            $table->string('bulan', 10);
            $table->string('tahun', 10);
            $table->string('ket', 50);
            $table->decimal('debet', 15, 1);
            $table->decimal('kredit', 15, 1);
            $table->decimal('sisa', 15, 1);
            $table->enum('status', ['lunas', 'belum'])->default('belum');
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
        Schema::dropIfExists('piutangs');
    }
}

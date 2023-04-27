<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelians';
    protected $fillable = [
        'supplier_id',
        'invoice',
        'tanggal_beli',
        'bulan',
        'tahun',
        'dpp',
        'ppn',
        'total_disc',
        'grand_total',
    ];
}

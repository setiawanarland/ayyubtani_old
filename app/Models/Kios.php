<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kios extends Model
{
    use HasFactory;

    protected $table = 'kios';
    protected $fillable = [
        'nama_kios',
        'pemilik',
        'kabupaten',
        'alamat',
        'npwp',
        'nik',
    ];

    public function penjualan()
    {
        return $this->hasMany(penjualan::class, 'id', 'kios_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfDownload extends Model
{
    use HasFactory;

    protected $table = 'pdf_downloads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'view',
        'data1',
        'data2',
        'data3',
        'data4',
        'data5',
        'file_settings',
        'expires_at'
    ];
}

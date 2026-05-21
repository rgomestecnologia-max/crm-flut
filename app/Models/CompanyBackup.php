<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBackup extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'filename', 'size_bytes', 'tables_count',
        'records_count', 'status', 'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sizeFormatted(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}

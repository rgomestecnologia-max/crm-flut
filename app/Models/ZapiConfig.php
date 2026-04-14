<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ZapiConfig extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'instance_id', 'token', 'client_token', 'phone_number',
        'webhook_secret', 'connection_status', 'is_active',
    ];

    protected $hidden = ['token', 'client_token', 'webhook_secret'];

    protected $casts = ['is_active' => 'boolean'];

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function isConnected(): bool
    {
        return $this->connection_status === 'connected';
    }
}

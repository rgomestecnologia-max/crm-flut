<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class EvolutionApiConfig extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'server_url', 'global_api_key', 'instance_name', 'instance_api_key',
        'webhook_token', 'connection_status', 'phone_number', 'profile_name',
        'groups_ignore', 'always_online', 'read_messages', 'reject_call', 'msg_call',
        'qr_code', 'pairing_code',
        'is_active',
    ];

    protected $hidden = ['global_api_key', 'instance_api_key', 'webhook_token'];

    protected $casts = [
        'is_active'      => 'boolean',
        'groups_ignore'  => 'boolean',
        'always_online'  => 'boolean',
        'read_messages'  => 'boolean',
        'reject_call'    => 'boolean',
    ];

    public static function current(): ?self
    {
        return static::first();
    }

    public function isConnected(): bool
    {
        return $this->connection_status === 'open';
    }

    public function serverUrl(): string
    {
        return rtrim($this->server_url, '/');
    }
}

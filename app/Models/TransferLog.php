<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferLog extends Model
{
    use Auditable, BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id', 'conversation_id', 'from_department_id', 'to_department_id',
        'from_agent_id', 'to_agent_id', 'reason',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function conversation(): BelongsTo  { return $this->belongsTo(Conversation::class); }
    public function fromDepartment(): BelongsTo{ return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment(): BelongsTo  { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function fromAgent(): BelongsTo     { return $this->belongsTo(User::class, 'from_agent_id'); }
    public function toAgent(): BelongsTo       { return $this->belongsTo(User::class, 'to_agent_id'); }
}

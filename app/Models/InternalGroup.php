<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class InternalGroup extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'avatar_url', 'created_by'];

    public function members()
    {
        return $this->belongsToMany(User::class, 'internal_group_members', 'group_id', 'user_id')
            ->withPivot('joined_at');
    }

    public function messages()
    {
        return $this->hasMany(InternalMessage::class, 'group_id')->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(InternalMessage::class, 'group_id')->latestOfMany();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A message sent through the public Contact page; read by admins in Admin → Messages.
 */
class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'subject', 'message', 'ip_address'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}

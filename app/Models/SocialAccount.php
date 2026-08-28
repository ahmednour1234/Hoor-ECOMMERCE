<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A link between a HOOR account and an outside provider.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $email
 * @property string|null $name
 * @property string|null $avatar
 */
class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'provider', 'provider_id', 'email', 'name', 'avatar'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

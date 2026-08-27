<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One stored setting.
 *
 * Keyed by its name rather than an id: settings are addressed by key
 * everywhere, and an autoincrement column would be a second identity nobody
 * uses.
 *
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $group
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'group'];
}

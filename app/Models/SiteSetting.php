<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $project_name
 * @property string|null $description
 * @property string|null $keywords
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property string|null $address
 * @property string|null $phone
 * @property array<string, string|null>|null $social_networks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SiteSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_name',
        'description',
        'keywords',
        'logo_path',
        'favicon_path',
        'address',
        'phone',
        'social_networks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'social_networks' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}

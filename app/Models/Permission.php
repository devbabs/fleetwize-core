<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Obrainwave\AccessTree\Models\Permission as AccessTreePermission;

class Permission extends AccessTreePermission
{
    /**
     * @var string
     */
    protected $table = 'permissions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Return active permissions grouped by module for the Roles & Permissions UI.
     *
     * @return Collection<string, Collection<int, static>>
     */
    public static function groupedByModule(): Collection
    {
        return static::query()
            ->active()
            ->orderBy('module')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'module', 'description'])
            ->groupBy('module');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public    $incrementing = false;
    protected $keyType     = 'string';

    protected $fillable = ['key', 'value', 'type', 'group'];

    // ── Public helpers ────────────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        if (! $setting) return $default;
        return static::castValue($setting->value, $setting->type);
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = static::find($key);
        if ($setting) {
            $setting->value = is_array($value) ? json_encode($value) : $value;
            $setting->save();
        }
    }

    /** Returns all settings as a flat key→value map with types applied. */
    public static function allCast(): array
    {
        return static::all()
            ->mapWithKeys(fn ($s) => [$s->key => static::castValue($s->value, $s->type)])
            ->toArray();
    }

    /** Returns settings keyed by group → key → value. */
    public static function grouped(): array
    {
        return static::all()
            ->groupBy('group')
            ->map(fn ($group) => $group->mapWithKeys(fn ($s) => [
                $s->key => static::castValue($s->value, $s->type),
            ]))
            ->toArray();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    public static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float'   => (float) $value,
            'json'    => json_decode($value, true) ?? [],
            default   => $value,
        };
    }
}

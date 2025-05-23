<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property string|null $data1
 * @property string|null $data2
 * @property string|null $data3
 * @property string|null $data4
 * @property string|null $data5
 * @property string|null $data6
 * @property string|null $data7
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereData1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereData2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereData3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereData4($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereData5($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereData6($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereData7($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TestModel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TestModel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'data1',
        'data2',
        'data3',
        'data4',
        'data5',
        'data6',
        'data7'
    ];

    protected static function boot()
    {
        parent::boot();
    }
}

<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $synopsis
 * @property string|null $arguments
 * @property string|null $options
 * @property string|null $error
 * @property string|null $group
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereArguments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Command whereSynopsis($value)
 * @mixin \Eloquent
 */
	class Command extends \Eloquent {}
}


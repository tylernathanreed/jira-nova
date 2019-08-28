<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Lenses\Lens as NovaLens;

abstract class Lens extends NovaLens
{
    use Concerns\Nameable,
        Concerns\ResolvesResourceFields,
        Concerns\ResolvesResourceFilters;
}

<?php

namespace App\Repositories;

use App\Models\Openinghours;

class OpeninghoursRepository extends EloquentRepository
{
    public function __construct(Openinghours $openinghours)
    {
        parent::__construct($openinghours);
    }
}

<?php


namespace transformers;


use League\Fractal\TransformerAbstract;
use Status;

class StatusTransformer extends TransformerAbstract
{

    public function transform(Status $status)
    {
        return [
            'north_america' => (bool)$status->north_america,
            'europe'        => (bool)$status->europe,
            'pts'           => (bool)$status->pts,
        ];
    }
} 
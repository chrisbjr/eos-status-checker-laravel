<?php


class Status extends Eloquent
{

    protected $table = 'status';

    public static function getLatest()
    {
        return Status::orderBy('created_at', 'desc')->first();
    }

} 
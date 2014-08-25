<?php


namespace api\v1;


use Chrisbjr\ApiGuard\ApiGuardController;
use Status;
use transformers\StatusTransformer;

class EosStatusApiController extends ApiGuardController
{

    protected $apiMethods = [
        'read' => [
            'keyAuthentication' => false
        ]
    ];

    public function read()
    {
        $status = Status::getLatest();

        return $this->response->withItem($status, new StatusTransformer);
    }

} 
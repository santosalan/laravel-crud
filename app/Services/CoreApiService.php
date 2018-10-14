<?php

namespace App\Services;

use SantosAlan\ApiMagic\ApiMagic;

class CoreApiService extends ApiMagic
{

    /**
     * Base URI
     *
     * @var String
     */
    protected $host = 'core-lumen-crud.local';

    protected $port = ':8220';

    protected $prefix = '/api/v1';

    // protected $actionRoutes = null;

    protected $namedReturn = false;

    // protected $tokenField = null;

}
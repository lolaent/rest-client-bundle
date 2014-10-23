<?php

namespace Cti\RestClientBundle\Services;

class ResourceLocator
{

    public function __construct($kernel)
    {
        $this->kernel = $kernel;
    }

    public function locate($path)
    {
        return $this->kernel->locateResource($path);
    }

}

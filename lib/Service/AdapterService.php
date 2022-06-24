<?php

namespace OCA\SocialLogin\Service;

/**
 * An excerpt from upstream's ProviderService.
 *
 * Thus:
 * @author: zorn-v
 */
class AdapterService
{
    /**
     * @throws \Exception
     */
    public function new($class, $config, $storage){

        $adapter = new $class($config, null, $storage);
        return $adapter;
    }
}

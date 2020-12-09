<?php
namespace CarloNicora\Minimalism\Services\Cacher\Factories;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractServiceFactory;
use CarloNicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\Cacher\Cacher;
use CarloNicora\Minimalism\Services\Cacher\Configurations\CacheConfigurations;
use Exception;

class ServiceFactory extends AbstractServiceFactory
{
    /**
     * serviceFactory constructor.
     * @param ServicesFactory $services
     * @throws ConfigurationException
     */
    public function __construct(servicesFactory $services)
    {
        $this->configData = new CacheConfigurations();

        parent::__construct($services);
    }

    /**
     * @param servicesFactory $services
     * @return Cacher
     * @throws ServiceNotFoundException
     * @throws Exception
     */
    public function create(servicesFactory $services) : Cacher
    {
        return new Cacher($this->configData, $services);
    }
}
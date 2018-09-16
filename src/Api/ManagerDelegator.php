<?php
namespace Search\Api;

// use Search\Mvc\Controller\Plugin\ApiSearch;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;

/**
 * API manager service (delegator).
 */
class ManagerDelegator extends \Omeka\Api\Manager
{
    /**
     * @var ControllerPluginManager
     */
    protected $controllerPlugins;

    /**
     * Execute a search API request with an option to do a quick search.
     *
     * The quick search is enabled when the argument "quickSearch" is true in
     * the options or the argument "quick-search" is t rue in the data.
     *
     * It would be better to use the argument "options", but it is not available
     * in the admin user interface, for example in block layouts, neither in the
     * view helper api().
     * @todo Remove "quick-search" from the display if any.
     *
     * {@inheritDoc}
     * @see \Omeka\Api\Manager::search()
     */
    public function search($resource, array $data = [], array $options = [])
    {
        // ApiSearch is set static to avoid a loop during init of Api Manager.
        /** @var \Search\Mvc\Controller\Plugin\ApiSearch $apiSearch */
        static $apiSearch;
        if (empty($options['quickSearch']) && empty($data['quick-search'])) {
            return parent::search($resource, $data, $options);
        }
        if (is_null($apiSearch)) {
            $apiSearch = $this->controllerPlugins->get('apiSearch');
        }
        return $apiSearch($resource, $data, $options);
    }

    /**
     * @param ControllerPluginManager $controllerPlugins
     */
    public function setControllerPlugins(ControllerPluginManager $controllerPlugins)
    {
        $this->controllerPlugins = $controllerPlugins;
    }
}

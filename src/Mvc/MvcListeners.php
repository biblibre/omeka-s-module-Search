<?php
namespace Search\Mvc;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;

class MvcListeners extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'prepareSearchPage']
        );
    }

    public function prepareSearchPage(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch->getParam('__SEARCH_PAGE__')) {
            return;
        }

        $services = $event->getApplication()->getServiceManager();
        $id = $event->getRouteMatch()->getParam('id');

        try {
            $searchPage = $services->get('Omeka\ApiManager')->read('search_pages', $id)->getContent();
        } catch (\Exception $e) {
            $event->setError(Application::ERROR_EXCEPTION);
            $event->setParam('exception', $e);
            $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
            $event->getApplication()->getEventManager()->triggerEvent($event);
            return false;
        }

        $services->get('ViewHelperManager')->get('searchCurrentPage')->setSearchPage($searchPage);
    }
}

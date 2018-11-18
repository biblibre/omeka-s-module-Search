<?php
namespace Search\View\Helper;

use Omeka\Form\ConfirmForm;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * View helper for rendering a confirm partial.
 *
 * @note Similar to DeleteConfirm, but with a different link and partial.
 * @todo Modify the core view helper to manage any action and partial.
 * @see \Omeka\View\Helper\DeleteConfirm
 */
class SearchIndexConfirm extends AbstractHelper
{
    protected $formElementManager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $formElementManager
     */
    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    /**
     * Render the delete confirm partial.
     *
     * @param \Omeka\Api\Representation\RepresentationInterface $resource
     * @param string $resourceLabel
     * @param bool $wrapSidebar
     * @param int $totalJobs
     * @return string
     */
    public function __invoke($resource, $resourceLabel = null, $wrapSidebar = true, $totalJobs = 0)
    {
        $form = $this->formElementManager->get(ConfirmForm::class);
        $form->setAttribute('action', $resource->url('index'));

        return $this->getView()->partial(
            'search/admin/search-index/index-confirm',
            [
                'wrapSidebar' => $wrapSidebar,
                'resource' => $resource,
                'resourceLabel' => $resourceLabel,
                'form' => $form,
                'totalJobs' => $totalJobs,
            ]
        );
    }
}

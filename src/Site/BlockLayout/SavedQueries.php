<?php
namespace Search\Site\BlockLayout;

use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

/**
 * The block layout class encapsulates everything about your custom block.
 *
 * Everything the user sees about your block, both on the admin and public
 * sides, gets defined here.
 */
class SavedQueries extends AbstractBlockLayout
{
    /**
     * getLabel() is where you define the label users will see when selecting
     * your block.
     *
     * @return string
     */
    public function getLabel()
    {
        return 'Saved Queries'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();
        $label = isset($data['label']) ? $data['label'] : '';
        $data['label'] = $label;
        $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        $form = new Form();
        $label = new Element\Text("o:block[__blockIndex__][o:data][label]");
        $label->setOptions([
            'label' => 'Label', // @translate
            'info' => 'Label for this block', // @translate
        ]);

        if ($block) {
            $label->setValue($block->dataValue('label'));
        }
        $form->add($label);

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $label = $block->dataValue('label', '');

        return $view->partial('search/block-layout/saved-queries', [
            'label' => $label,
        ]);
    }
}

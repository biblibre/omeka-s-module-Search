<?php
namespace Search\Form\Element;

use Zend\Form\Element\Select;

class OptionalSelect extends Select
{
    /**
     * @see https://github.com/zendframework/zendframework/issues/2761#issuecomment-14488216
     *
     * {@inheritDoc}
     * @see \Zend\Form\Element\Select::getInputSpecification()
     */
    public function getInputSpecification()
    {
        $inputSpecification = parent::getInputSpecification();
        $inputSpecification['required'] = isset($this->attributes['required']) && $this->attributes['required'];
        return $inputSpecification;
    }
}

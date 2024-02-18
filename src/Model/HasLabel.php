<?php

namespace Luminix\Backend\Model;

trait HasLabel
{
    /**
     * Get the label for the model.
     * 
     * @return string
     */
    public function getLabel()
    {
        if ($this->labeledBy ?? false) {
            return $this->labeledBy;
        }

        return $this->fillable[0];
    }
}

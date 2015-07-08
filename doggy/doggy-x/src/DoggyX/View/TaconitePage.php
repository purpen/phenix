<?php
/**
 * Special response output for tacontie page(Dt template)
 * 
 * note: this will enable autoescape
 * @author n.s.
 */
class DoggyX_View_TaconitePage extends DoggyX_View_HtmlPage {
    public function render() {
        $action = $this->invocation->getAction();
        $action->stash['_view']['content_type'] = 'text/xml';
        $action->stash['_view']['charset'] = 'utf-8';
        $action->stash['_view']['autoescape'] = true;
        return parent::render();
    }
}
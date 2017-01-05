<?php

class BlockInstagramDefaultModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();

        $blockinstagram = Module::getInstanceByName('blockinstagram');
        $this->context->smarty->assign(array(
            'instagram_pics' => $blockinstagram->getPics(true),
            'instagram_user' => $blockinstagram->getAccount(Configuration::get('BI_USERNAME'))
        ));

        $this->setTemplate('default.tpl');
    }

}
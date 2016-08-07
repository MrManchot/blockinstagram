<?php

class BlockInstagram extends Module
{

    public function __construct()
    {
        $this->name = 'blockinstagram';
        $this->version = '1.0.0';
        $this->author = 'Anonymous';
        parent::__construct();
        $this->displayName = $this->l('Block Instagram');
        $this->description = $this->l('Display Instagram pics from an account');
        $this->bootstrap = 1;
    }

    public function install()
    {
        return parent::install() &&
        Configuration::updateValue('BI_USERNAME', 'taylorswift') &&
        Configuration::updateValue('BI_NB_IMAGE', 12) &&
        Configuration::updateValue('BI_SIZE', 195) &&
        Configuration::updateValue('BI_CACHE_DURATION', 'day') &&
        $this->registerHook('displayHome');
    }

    public function getContent()
    {
        return $this->_postProcess() . $this->_getForm();
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('subMOD')) {
            Configuration::updateValue('BI_USERNAME', Tools::getValue('username'));
            Configuration::updateValue('BI_NB_IMAGE', intval(Tools::getValue('nb_image')));
            Configuration::updateValue('BI_SIZE', intval(Tools::getValue('size')));
            Configuration::updateValue('BI_CACHE_DURATION', Tools::getValue('size'));
            return $this->displayConfirmation($this->l('Settings updated'));
        }
    }

    private function _getForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->controller->getLanguages();
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper->title = $this->displayName;

        $helper->fields_value['username'] = Configuration::get('BI_USERNAME');
        $helper->fields_value['nb_image'] = Configuration::get('BI_NB_IMAGE');
        $helper->fields_value['size'] = Configuration::get('BI_SIZE');
        $helper->fields_value['cache_duration'] = Configuration::get('BI_CACHE_DURATION');

        $helper->submit_action = 'subMOD';


        # form
        $this->fields_form[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->displayName
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Instagram Username :'),
                        'name' => 'username'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Image number :'),
                        'name' => 'nb_image'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Image size in pixel :'),
                        'name' => 'size'
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'cache_duration',
                        'label' => $this->l('Refresh :'),
                        'options' => array(
                            'query' => array(
                                array('id' => 'day', 'name' => $this->l('Each day')),
                                array('id' => 'hour', 'name' => $this->l('Each hour'))
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );

        return $helper->generateForm($this->fields_form);
    }

    public function hookDisplayHome($params)
    {
        $conf = Configuration::getMultiple(array('BI_USERNAME', 'BI_NB_IMAGE', 'BI_SIZE', 'BI_CACHE_DURATION'));

        # Gestion du slug du cache
        $cacheIdDate = $conf['BI_CACHE_DURATION'] == 'day' ? date('Ymd') : date('YmdH');
        $cache_array = array($this->name, $conf['BI_USERNAME'], $cacheIdDate, (int)$this->context->language->id);
        $cacheId = implode('|', $cache_array);

        if (!$this->isCached('blockinstagram.tpl', $cacheId)) {
            $instagram_pics = array();
            $json_url = 'https://www.instagram.com/' . $conf['BI_USERNAME'] . '/media/';
            $ctx = stream_context_create(array('http' => array('timeout' => 2)));
            $json = file_get_contents($json_url, false, $ctx);
            $values = json_decode($json);
            if ($values->status == 'ok') {
                $items = array_slice($values->items, 0, $conf['BI_NB_IMAGE']);
                foreach ($items as $item) {
                    $image = self::imagickResize($item->images->standard_resolution->url, 'crop', $conf['BI_SIZE']);
                    $instagram_pics[] = array(
                        'image' => $image,
                        'caption' => $item->caption->text,
                        'link' => $item->link
                    );
                }
                $this->context->smarty->assign(array(
                    'instagram_pics' => $instagram_pics,
                    'username' => $conf['BI_USERNAME'],
                    'size' => $conf['BI_SIZE']
                ));
            }
        }

        return $this->display(__FILE__, 'blockinstagram.tpl', $cacheId);
    }


    public static function imagickResize($image, $type, $width, $height = null)
    {
        if (!class_exists('Imagick'))
            return $image;

        if (is_null($height)) {
            $height = $width;
        }

        $image_name = md5($image) . '_' . $type . '_' . $width . '_' . $height . '.jpg';
        $image_local = _PS_TMP_IMG_DIR_ . $image_name;

        if (!file_exists($image_local)) {
            copy($image, $image_local);
            if (!file_exists($image_local)) {
                return;
            }
            chmod($image_local, 0755);
            $thumb = new Imagick($image_local);
            if ($type == 'crop') {
                $thumb->cropThumbnailImage($width, $height);
            } elseif ($type == 'resize') {
                $thumb->scaleImage($width, $height, true);
            }
            $thumb->writeImage($image_local);
        }

        return _PS_TMP_IMG_ . $image_name;
    }

}
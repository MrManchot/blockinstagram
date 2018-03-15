<?php

class BlockInstagram extends Module
{

    const BI_BASE_FEED = 'https://ig.axome.me/api/feed/';

    public $user_info;
    public $user_posts = array();

    public function __construct()
    {
        $this->name = 'blockinstagram';
        $this->version = '2.0.0';
        $this->author = 'Axome';
        parent::__construct();
        $this->displayName = $this->l('Block Instagram');
        $this->description = $this->l('Display Instagram pics from an account');
        $this->controllers = array('default');
        $this->bootstrap = 1;
    }

    public function install()
    {
        return parent::install() &&
            Configuration::updateValue('BI_NB_IMAGE', 8) &&
            Configuration::updateValue('BI_SIZE', 0) &&
            Configuration::updateValue('BI_CACHE_DURATION', 'day') &&
            $this->registerHook('blockInstagram') &&
            $this->registerHook('displayHome');
        $this->_clearCache('blockinstagram.tpl');
    }

    public function getContent()
    {

        $warning = '';
        if (!class_exists('Imagick')) {
            $warning = $this->displayError('Your server need the ImageMagick PHP extension to resize pics : <em>sudo apt-get install php-imagick</em>');
        }
        return $warning . $this->_postProcess() . $this->_getForm();
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('subMOD')) {
            $languages = Language::getLanguages(false);
            foreach ($languages as $lang) {
                Configuration::updateValue('BI_API_KEY_' . $lang['id_lang'],
                    Tools::getValue('api_key_' . $lang['id_lang']));
            }
            Configuration::updateValue('BI_NB_IMAGE', intval(Tools::getValue('nb_image')));
            Configuration::updateValue('BI_SIZE', intval(Tools::getValue('size')));
            Configuration::updateValue('BI_CACHE_DURATION', Tools::getValue('cache_duration'));
            $this->_clearCache('blockinstagram.tpl');
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

        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $helper->fields_value['api_key'][$lang['id_lang']] = Configuration::get('BI_API_KEY_' . $lang['id_lang']);
        }
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
                        'label' => $this->l('Instagram API KEY :'),
                        'name' => 'api_key',
                        'lang' => true,
                        'desc' => $this->l('Générer votre API KEY à cette adresse') . ' <a href="' . self::BI_BASE_FEED . '" target="_blank">' . self::BI_BASE_FEED . '</a>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Image number :'),
                        'name' => 'nb_image',
                        'desc' => $this->l('You can retry 12 pics maximum')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Resize size in pixel :'),
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

        # Gestion du slug du cache
        $cache_time = Configuration::get('BI_CACHE_DURATION') == 'day' ? date('Ymd') : date('YmdH');
        $cache_array = array($this->name, $cache_time, (int)$this->context->language->id);
        $cacheId = implode('|', $cache_array);

        if (!$this->isCached('blockinstagram.tpl', $cacheId)) {
            $feed = $this->getFeed($this->getApiKey());
            if (!$feed) {
                return;
            }

            $this->context->smarty->assign(array(
                'instagram_pics' => $this->getPics(),
                'instagram_user' => $this->getAccount()
            ));

        }

        return $this->display(__FILE__, 'blockinstagram.tpl', $cacheId);
    }

    public function getApiKey()
    {
        $username = Configuration::get('BI_API_KEY_' . $this->context->language->id);
        if ($username) {
            return $username;
        }

        $default_lang = Configuration::get('PS_LANG_DEFAULT');
        $username = Configuration::get('BI_API_KEY_' . $default_lang);
        if ($username) {
            return $username;
        }
    }


    # Use in *.tpl : {hook h='blockInstagram' mod='blockinstagram'}
    # Work only if not hook on displayHome
    public function hookBlockInstagram($params)
    {
        return $this->hookDisplayHome($params);
    }


    public function getAccount()
    {

        if (!$this->user_info instanceof stdClass) {
            return array();
        }

        return array(
            'followed_by' => $this->user_info->followed_by,
            'biography' => $this->user_info->biography,
            'external_url' => $this->user_info->external_url,
            'follows' => $this->user_info->follows,
            'profile_pic' => $this->user_info->profile_pic,
            'posts' => $this->user_info->posts,
            'full_name' => $this->user_info->full_name,
            'username' => $this->user_info->username
        );
    }


    public function getFeed($api_key)
    {
        $feed_url = self::BI_BASE_FEED . $api_key;
        $context = stream_context_create(array('http' => array('timeout' => 2)));
        $response = @file_get_contents($feed_url, false, $context);
        $response = $response ? json_decode($response) : false;

        # L'objet retourné contient toujours "success", "data" et "errors"
        if (is_object($response) && false !== $response->success) {
            $this->user_info = $response->data->user;
            $this->user_posts = $response->data->posts;
            return true;
        } else {
            return false;
        }
    }


    public function getPics($all = false)
    {
        if (empty($this->user_posts)) {
            return array();
        }

        $conf = Configuration::getMultiple(array('BI_NB_IMAGE', 'BI_SIZE'));

        $items = $this->user_posts;
        if (!$all && $conf['BI_NB_IMAGE'] > 0) {
            $items = array_slice($items, 0, $conf['BI_NB_IMAGE']);
        }

        $instagram_pics = array();
        foreach ($items as $item) {

            if ($conf['BI_SIZE']) {
                $image = self::imagickResize($item->image, 'crop', $conf['BI_SIZE']);
            } else {
                $image = $item->image;
            }
            $instagram_pics[] = array(
                'image' => $image,
                'original_image' => $item->image,
                'caption' => isset($item->caption) ? $item->caption : '',
                'link' => $item->link,
                'likes' => $item->likes,
                'comments' => $item->comments,
                'date' => date($this->context->language->date_format_full, $item->timestamp)
            );
        }
        return $instagram_pics;

    }


    public static function imagickResize($image, $type, $width, $height = null)
    {
        if (!class_exists('Imagick')) {
            return $image;
        }

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

        $context = Context::getContext();
        return $context->link->getMediaLink(_PS_TMP_IMG_ . $image_name);
    }

    public static function niceNumberDisplay($n)
    {
        $n = floatval($n);
        if ($n > 1000000) {
            return round($n / 1000000, 1) . 'm';
        } elseif ($n > 1000) {
            return round($n / 1000, 1) . 'k';
        } else {
            return number_format($n, 0, ' ', ' ');
        }
    }

}

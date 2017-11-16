<?php

class BlockInstagram extends Module
{

    const BI_BASE_FEED = 'https://www.instagram.com/';

    public function __construct()
    {
        $this->name = 'blockinstagram';
        $this->version = '1.2.1';
        $this->author = 'Cédric Mouleyre';
        parent::__construct();
        $this->displayName = $this->l('Block Instagram');
        $this->description = $this->l('Display Instagram pics from an account');
        $this->controllers = array('default');
        $this->bootstrap = 1;
    }

    public function install()
    {
        return parent::install() &&
        Configuration::updateValue('BI_USERNAME', 'instagram') &&
        Configuration::updateValue('BI_NB_IMAGE', 8) &&
        Configuration::updateValue('BI_SIZE', 300) &&
        Configuration::updateValue('BI_CACHE_DURATION', 'day') &&
        Configuration::updateValue('BI_IMAGE_FORMAT', 'standard_resolution') &&
        $this->registerHook('blockInstagram') &&
        $this->registerHook('displayHome');
	    $this->_clearCache('blockinstagram.tpl');
    }

    public function getContent()
    {
        return $this->_postProcess() . $this->_getForm();
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('subMOD')) {
	        $languages = Language::getLanguages(false);
	        foreach ($languages as $lang) {
		        Configuration::updateValue('BI_USERNAME_' . $lang['id_lang'], Tools::getValue('username_' . $lang['id_lang']));
	        }
            Configuration::updateValue('BI_NB_IMAGE', intval(Tools::getValue('nb_image')));
            Configuration::updateValue('BI_IMAGE_FORMAT', Tools::getValue('image_format'));
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
		    $helper->fields_value['username'][$lang['id_lang']] = Configuration::get('BI_USERNAME_' . $lang['id_lang']);
	    }
        $helper->fields_value['nb_image'] = Configuration::get('BI_NB_IMAGE');
        $helper->fields_value['size'] = Configuration::get('BI_SIZE');
        $helper->fields_value['cache_duration'] = Configuration::get('BI_CACHE_DURATION');
        $helper->fields_value['image_format'] = Configuration::get('BI_IMAGE_FORMAT');

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
                        'name' => 'username',
	                    'lang' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Image number :'),
                        'name' => 'nb_image',
                        'desc'  => $this->l('You can retry 12 pics maximum')
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Image format :'),
                        'name' => 'image_format',
                        'options'  => array(
                            'query' => array(
	                            array('id'   => 0, 'name' => $this->l('150 X 150')),
	                            array('id'   => 1, 'name' => $this->l('240 X 240')),
	                            array('id'   => 2, 'name' => $this->l('320 X 320')),
	                            array('id'   => 3, 'name' => $this->l('480 X 480')),
	                            array('id'   => 4, 'name' => $this->l('640 X 640')),
	                            array('id'   => 'standard_resolution', 'name' => $this->l('Standard resolution (1080 x 1080)'))
                            ),
                            'id'    => 'id',
                            'name'  => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Resize size in pixel :'),
                        'name' => 'size',
                        'desc'  => $this->l('Your server need the ImageMagick PHP extension to resize pics (0 to desactivate this option)')
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
            $this->context->smarty->assign(array(
                'instagram_pics' => $this->getPics(),
                'instagram_user' => $this->getAccount($this->getUsername())
            ));
        }

        return $this->display(__FILE__, 'blockinstagram.tpl', $cacheId);
    }

	public function getUsername()
	{
		$username = Configuration::get('BI_USERNAME_' . $this->context->language->id);
		if ($username) {
			return $username;
		}

		$default_lang = Configuration::get('PS_LANG_DEFAULT');
		$username = Configuration::get('BI_USERNAME_' . $default_lang);
		if ($username) {
			return $username;
		}

		# Backward compatibility
		return Configuration::get('BI_USERNAME');
	}
    
    
    # Use in *.tpl : {hook h='blockInstagram' mod='blockinstagram'}
    # Work only if not hook on displayHome
    public function hookBlockInstagram($params) {
        return $this->hookDisplayHome($params);
    }


    public function getAccount($username) {
        $account = $this->getFeed($username.'/?__a=1');
        if(!$account)
            return false;

        return array(
            'followed_by' => self::niceNumberDisplay($account->user->followed_by->count),
            'biography' => $account->user->biography,
            'external_url' => $account->user->external_url,
            'follows' => self::niceNumberDisplay($account->user->follows->count),
            'profile_pic' => $account->user->profile_pic_url,
            'posts' => self::niceNumberDisplay($account->user->media->count),
            'full_name' => $account->user->full_name,
            'username' => $account->user->username
        );
    }


    public static function getFeed($feed) {
        $json_url = self::BI_BASE_FEED . $feed;
        $ctx = stream_context_create(array('http' => array('timeout' => 2)));
        $json = @file_get_contents($json_url, false, $ctx);
        return $json ? json_decode($json) : false;
    }


    public function getPics($all = false) {

        $conf = Configuration::getMultiple(array('BI_NB_IMAGE', 'BI_SIZE', 'BI_IMAGE_FORMAT'));
		$username = $this->getUsername();
        $instagram_pics = array();
        $values = $this->getFeed($username . '/?__a=1');
        if (!$values)
            return array();

        $items = $values->user->media->nodes;
        if(!$all)
            $items = array_slice($items, 0, $conf['BI_NB_IMAGE']);

	    $image_format = $conf['BI_IMAGE_FORMAT'] ? $conf['BI_IMAGE_FORMAT'] : 'standard_resolution';

	    foreach ($items as $item) {
            if($image_format == 'standard_resolution') {
            	$image = $item->display_src;
            } else {
	            $image = $item->thumbnail_resources[$image_format]->src;
            }
            if($conf['BI_SIZE']) {
                $image = self::imagickResize($image, 'crop', $conf['BI_SIZE']);
            }
            $instagram_pics[] = array(
                'image' => $image,
                'original_image' => $item->display_src,
                'caption' => isset($item->caption) ? $item->caption : '',
                'link' => 'https://www.instagram.com/p/'.$item->code.'/',
                'likes' => self::niceNumberDisplay($item->likes->count),
                'comments' => self::niceNumberDisplay($item->likes->count),
                'date' => date($this->context->language->date_format_full, $item->date)
            );
        }
        return $instagram_pics;

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

        $context = Context::getContext();
        return $context->link->getMediaLink(_PS_TMP_IMG_ . $image_name);
    }

    public static function niceNumberDisplay($n) {
        $n = floatval($n);
        if($n > 1000000) {
            return round($n / 1000000, 1).'m';
        } elseif($n > 1000) {
            return round($n / 1000, 1).'k';
        } else {
            return number_format($n, 0, ' ', ' ');
        }
    }

}

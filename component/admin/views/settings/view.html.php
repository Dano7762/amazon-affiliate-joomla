<?php
defined('_JEXEC') or die;

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;

class AajViewSettings
{
    public array $config  = [];
    public bool  $apiReady = false;
    public array $stores  = [];

    public function __construct(array $config = [], array $paths = []) {}

    public function display($tpl = null): void
    {
        ToolbarHelper::title('AAJ - Settings', 'cog');
        ToolbarHelper::apply('settings.save', 'JTOOLBAR_SAVE');

        $p = ComponentHelper::getParams('com_aaj');
        $this->config = [
            'api_key'               => $p->get('api_key', ''),
            'api_secret'            => $p->get('api_secret', ''),
            'api_tag'               => $p->get('api_tag', ''),
            'api_country'           => $p->get('api_country', 'com'),
            'api_status'            => (bool) $p->get('api_status', false),
            'cache_duration'        => (int)  $p->get('cache_duration', 720),
            'shortcode_tag'         => $p->get('shortcode_tag', 'aaj'),
            'disclaimer'            => $p->get('disclaimer', ''),
            'title_length'          => (int)  $p->get('title_length', 100),
            'button_style'          => $p->get('button_style', 'amazon'),
            'box_template'          => $p->get('box_template', 'horizontal'),
            'bestseller_template'   => $p->get('bestseller_template', 'horizontal'),
            'new_releases_template' => $p->get('new_releases_template', 'horizontal'),
        ];
        $this->apiReady = $this->config['api_status'];
        $this->stores = [
            'com'=>'United States','ca'=>'Canada','co.uk'=>'United Kingdom',
            'de'=>'Germany','fr'=>'France','it'=>'Italy','es'=>'Spain',
            'co.jp'=>'Japan','com.au'=>'Australia','in'=>'India',
            'com.br'=>'Brazil','com.mx'=>'Mexico','nl'=>'Netherlands',
            'pl'=>'Poland','se'=>'Sweden','ae'=>'UAE','sg'=>'Singapore',
        ];

        $tmpl = __DIR__ . '/tmpl/default.php';
        if (is_file($tmpl)) include $tmpl;
    }
}

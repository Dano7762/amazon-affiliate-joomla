<?php
/**
 * Configuration helper for AAJ component
 *
 * @package Aaj\Component\Aaj
 */

namespace Aaj\Component\Aaj\Administrator\Helper;

defined('_JEXEC') or die;

use Aaj\Component\Aaj\Administrator\Api\AmazonApi;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * Centralised access to AAJ component configuration
 */
class ConfigHelper
{
    /** @var Registry|null Cached params */
    private static ?Registry $params = null;

    /**
     * Get component params object
     */
    public static function getComponent(): Registry
    {
        if (self::$params === null) {
            self::$params = ComponentHelper::getParams('com_aaj');
        }
        return self::$params;
    }

    /**
     * Get a single param value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getComponent()->get($key, $default);
    }

    /**
     * Get all configuration as a flat array
     */
    public static function getAll(): array
    {
        $params = self::getComponent();
        return [
            // API
            'api_key'        => $params->get('api_key', ''),
            'api_secret'     => $params->get('api_secret', ''),
            'api_tag'        => $params->get('api_tag', ''),
            'api_country'    => $params->get('api_country', 'com'),
            'api_status'     => (bool) $params->get('api_status', false),

            // General
            'cache_duration' => (int) $params->get('cache_duration', 720),
            'shortcode_tag'  => $params->get('shortcode_tag', 'aaj'),
            'disclaimer'     => $params->get('disclaimer', ''),

            // Output
            'title_length'          => (int) $params->get('title_length', 100),
            'description_items'     => (int) $params->get('description_items', 5),
            'description_length'    => (int) $params->get('description_length', 200),
            'button_text'           => $params->get('button_text', ''),
            'button_style'          => $params->get('button_style', 'amazon'),
            'check_prime'           => $params->get('check_prime', 'linked'),
            'show_reviews'          => (bool) $params->get('show_reviews', false),
            'pricing_reduction'     => $params->get('pricing_reduction', 'amount'),

            // Functions
            'box_template'          => $params->get('box_template', 'horizontal'),
            'bestseller_template'   => $params->get('bestseller_template', 'horizontal'),
            'new_releases_template' => $params->get('new_releases_template', 'horizontal'),
        ];
    }

    /**
     * Save component params
     */
    public static function saveComponent(Registry $params): bool
    {
        /** @var \Joomla\Database\DatabaseInterface $db */
        $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $paramsStr = $params->toString('JSON');

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = :params')
            ->bind(':params', $paramsStr)
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_aaj'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

        $db->setQuery($query);

        self::$params = null; // Bust cache

        return $db->execute();
    }

    /**
     * Build and return an AmazonApi instance from stored config
     */
    public static function buildApi(): AmazonApi
    {
        $cfg = self::getAll();
        $api = new AmazonApi(
            $cfg['api_key'],
            $cfg['api_secret'],
            $cfg['api_tag'],
            $cfg['api_country']
        );
        return $api;
    }

    /**
     * Is the API configured and verified?
     */
    public static function isApiReady(): bool
    {
        $cfg = self::getAll();
        return !empty($cfg['api_key'])
            && !empty($cfg['api_secret'])
            && !empty($cfg['api_tag'])
            && !empty($cfg['api_status']);
    }

    /**
     * Get Amazon store TLD for URL building
     */
    public static function getStoreTld(): string
    {
        $country = self::get('api_country', 'com');
        return $country;
    }
}

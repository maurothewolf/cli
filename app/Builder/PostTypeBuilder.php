<?php

namespace Wordrobe\Builder;

use Wordrobe\Config;
use Wordrobe\Helper\Dialog;
use Wordrobe\Helper\StringsManager;
use Wordrobe\Entity\Template;

class PostTypeBuilder extends TemplateBuilder implements Builder
{
  /**
   * Handles post type creation wizard
   */
  public static function startWizard()
  {
    $theme = self::askForTheme();
    $key = self::askForKey();
    $general_name = self::askForGeneralName($key);
    $singular_name = self::askForSingularName($general_name);
    $text_domain = self::askForTextDomain($theme);
    $capability_type = self::askForCapabilityType();
    $taxonomies = self::askForTaxonomies();
    $icon = self::askForIcon();
    $description = self::askForDescription();
    $build_single = self::askForSingleTemplateBuild($key);
    $build_archive = self::askForArchiveTemplateBuild($key);
  
    try {
      self::build([
        'key' => $key,
        'general-name' => $general_name,
        'singular-name' => $singular_name,
        'text-domain' => $text_domain,
        'capability-type' => $capability_type,
        'taxonomies' => $taxonomies,
        'icon' => $icon,
        'description' => $description,
        'theme' => $theme,
        'build-single' => $build_single,
        'build-archive' => $build_archive
      ]);
    } catch (\Exception $e) {
      Dialog::write($e->getMessage(), 'red');
      exit;
    }
  
    Dialog::write('Post type added!', 'green');
  }
  
  /**
   * Builds post type
   * @param array $params
   * @example PostTypeBuilder::create([
   *  'key' => $key,
   *  'general-name' => $general_name,
   *  'singular-name' => $singular_name,
   *  'text-domain' => $text_domain,
   *  'capability-type' => $capability_type,
   *  'taxonomies' => $taxonomies,
   *  'icon' => $icon,
   *  'description' => $description,
   *  'theme' => $theme,
   *  'build-single' => $build_single,
   *  'build-archive' => $build_archive
   * ]);
   */
  public static function build($params)
  {
    $params = self::checkParams($params);
    $theme_path = PROJECT_ROOT . '/' . Config::get('themes-path') . '/' . $params['theme'];
    $post_type = new Template('post-type', [
      '{KEY}' => $params['key'],
      '{GENERAL_NAME}' => $params['general-name'],
      '{SINGULAR_NAME}' => $params['singular-name'],
      '{TEXT_DOMAIN}' => $params['text-domain'],
      '{CAPABILITY_TYPE}' => $params['capability-type'],
      '{TAXONOMIES}' => $params['taxonomies'],
      '{ICON}' => $params['icon'],
      '{DESCRIPTION}' => $params['description']
    ]);
    $post_type->save("$theme_path/includes/post-types/" . $params['key'] . ".php");
    Config::add('themes.' . $params['theme'] . '.post-types', $params['key']);
    
    if ($params['build-single']) {
      SingleBuilder::build([
        'post-type' => $params['key'],
        'theme' => $params['theme']
      ]);
    }
    
    if ($params['build-archive']) {
      ArchiveBuilder::build([
        'type' => 'post-type',
        'key' => $params['key'],
        'theme' => $params['theme']
      ]);
    }
  }
  
  /**
   * Asks for post type key
   * @return mixed
   */
  private static function askForKey()
  {
    $key = Dialog::getAnswer('Post type key (e.g. event):');
    return $key ? $key : self::askForKey();
  }
  
  /**
   * Asks for general name
   * @param $key
   * @return string
   */
  private static function askForGeneralName($key)
  {
    $default = ucwords(str_replace('-', ' ', $key)) . 's';
    $general_name = Dialog::getAnswer("General name [$default]:", $default);
    return $general_name ? $general_name : self::askForGeneralName($key);
  }
  
  /**
   * Asks for singular name
   * @param $general_name
   * @return string
   */
  private static function askForSingularName($general_name)
  {
    $default = substr($general_name, -1) === 's' ? substr($general_name, 0, -1) : $general_name;
    $singular_name = Dialog::getAnswer("Singular name [$default]:", $default);
    return $singular_name ? $singular_name : self::askForSingularName($general_name);
  }
  
  /**
   * Asks for text domain
   * @param $theme
   * @return mixed
   */
  private static function askForTextDomain($theme)
  {
    $text_domain = Dialog::getAnswer("Text domain [$theme]:", $theme);
    return $text_domain ? $text_domain : self::askForTextDomain($theme);
  }
  
  /**
   * Asks for capability type
   * @return mixed
   */
  private static function askForCapabilityType()
  {
    return Dialog::getChoice('Capability type:', ['post', 'page'], null);
  }
  
  /**
   * Asks for taxonomies
   * @return array|mixed
   */
  private static function askForTaxonomies()
  {
    return Dialog::getAnswer('Taxonomies (comma separated):');
  }
  
  /**
   * Asks for icon
   * @return mixed
   */
  private static function askForIcon()
  {
    return Dialog::getAnswer('Icon [dashicons-admin-post]:', 'dashicons-admin-post');
  }
  
  /**
   * Asks for description
   * @return string
   */
  private static function askForDescription()
  {
    return Dialog::getAnswer('Description:');
  }
  
  /**
   * Asks for single template auto-build confirmation
   * @param $key
   * @return mixed
   */
  private static function askForSingleTemplateBuild($key)
  {
    return Dialog::getConfirmation("Do you want to automatically create a single template for '$key' post type?", true, 'yellow');
  }
  
  /**
   * Asks for archive template auto-build confirmation
   * @param $key
   * @return mixed
   */
  private static function askForArchiveTemplateBuild($key)
  {
    return Dialog::getConfirmation("Do you want to automatically create an archive template for '$key' post type?", true, 'yellow');
  }
  
  /**
   * Checks params existence and normalizes them
   * @param $params
   * @return mixed
   * @throws \Exception
   */
  private static function checkParams($params)
  {
    // checking existence
    if (!$params['key'] || !$params['general-name'] || !$params['singular-name'] || !$params['text-domain'] || !$params['capability-type'] || !$params['theme']) {
      throw new \Exception('Error: unable to create post type because of missing parameters.');
    }
    
    // normalizing
    $key = StringsManager::toKebabCase($params['key']);
    $general_name = ucwords($params['general-name']);
    $singular_name = ucwords($params['singular-name']);
    $text_domain = StringsManager::toKebabCase($params['text-domain']);
    $capability_type = strtolower($params['capability-type']);
    $taxonomies = implode(',', array_map(function ($entry) {
      return StringsManager::toKebabCase($entry);
    }, explode(',', $params['taxonomies'])));
    $icon = StringsManager::toKebabCase($params['icon']);
    $description = ucfirst($params['description']);
    $theme = StringsManager::toKebabCase($params['theme']);
    $build_single = $params['build-single'] || false;
    $build_archive = $params['build-archive'] || false;
    
    if (!Config::get("themes.$theme")) {
      throw new \Exception("Error: theme '$theme' doesn't exist.");
    }
    
    return [
      'key' => $key,
      'general-name' => $general_name,
      'singular-name' => $singular_name,
      'text-domain' => $text_domain,
      'capability-type' => $capability_type,
      'taxonomies' => $taxonomies,
      'icon' => $icon,
      'description' => $description,
      'theme' => $theme,
      'build-single' => $build_single,
      'build-archive' => $build_archive
    ];
  }
}

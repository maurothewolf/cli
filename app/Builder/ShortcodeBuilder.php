<?php

namespace Wordrobe\Builder;

use Wordrobe\Helper\Config;
use Wordrobe\Helper\Dialog;
use Wordrobe\Helper\StringsManager;
use Wordrobe\Entity\Template;

/**
 * Class ShortcodeBuilder
 * @package Wordrobe\Builder
 */
class ShortcodeBuilder extends TemplateBuilder implements WizardBuilder
{
  /**
   * Handles shortcode template build wizard
   */
  public static function startWizard()
  {
    try {
      $theme = self::askForTheme();
      $key = self::askForKey();
      $attributes = self::askForAttributes();
      $title = self::askForTitle($key);
      $icon = self::askForIcon();
      self::build([
        'key' => $key,
        'attributes' => $attributes,
        'title' => $title,
        'icon' => $icon,
        'theme' => $theme,
        'override' => 'ask'
      ]);
      Dialog::write('Shortcode added!', 'green');
    } catch (\Exception $e) {
      Dialog::write($e->getMessage(), 'red');
      exit;
    }
  }

  /**
   * Builds shortcode template
   * @param array $params
   * @example ShortcodeBuilder::build([
   *  'key' => $key,
   *  'attributes' => $attributes,
   *  'title' => $title,
   *  'icon' => $icon,
   *  'theme' => $theme,
   *  'override' => 'ask'|'force'|false
   * ]);
   * @throws \Exception
   */
  public static function build($params)
  {
    $params = self::prepareParams($params);
    $shortcode_ctrl = new Template('shortcode', ['{KEY}' => $params['key']], $params['basepath'] . '/core/shortcodes');
    $shortcode_plugin = new Template('shortcode-plugin', [
      '{TITLE}' => $params['title'],
      '{KEY}' => $params['plugin-key'],
      '{ICON}' => $params['icon'],
      '{SHORTCODE}' => $params['key'],
      '{ATTRIBUTES}' => $params['attributes']
    ], $params['basepath'] . '/core/shortcodes');
    $shortcode_view = new Template('partial', [
      '{CLASS_NAME}' => $params['key'],
      '{CONTENT}' => '{{ content|shortcodes }}'
    ], $params['basepath'] . '/templates/partials/shortcodes');
    $shortcode_ctrl->save($params['ctrl-filename'], $params['override']);
    $shortcode_plugin->save($params['plugin-filename'], $params['override']);
    $shortcode_view->save($params['view-filename'], $params['override']);
  }

  /**
   * Asks for shortcode key
   * @return string
   */
  private static function askForKey()
  {
    $name = Dialog::getAnswer('Key (e.g. my-shortcode):');
    return $name ?: self::askForKey();
  }

  /**
   * Asks for shortcode's attributes
   * @return string
   */
  private static function askForAttributes()
  {
    return Dialog::getAnswer('Attributes (comma separated):');
  }

  /**
   * Asks for shortcode title
   * @param string $key
   * @return mixed
   */
  private static function askForTitle($key)
  {
    $default = ucwords(StringsManager::removeDashes($key, ' '));
    return Dialog::getAnswer("Title [$default]:", $default);
  }

  /**
   * Asks for shortcode icon
   * @return mixed
   */
  private static function askForIcon()
  {
    return Dialog::getAnswer('Icon [dashicons-editor-code]:', 'dashicons-editor-code');
  }

  /**
   * Checks params existence and normalizes them
   * @param array $params
   * @return mixed
   * @throws \Exception
   */
  private static function prepareParams($params)
  {
    // checking theme
    $theme = StringsManager::toKebabCase($params['theme']);
    Config::check("themes.$theme", 'array', "Error: theme '$theme' doesn't exist.");

    // checking params
    if (!$params['key']) {
      throw new \Exception('Error: unable to create shortcode because of missing parameters.');
    }

    // normalizing
    $key = StringsManager::toKebabCase($params['key']);
    $plugin_key = StringsManager::toSnakeCase($key);
    $title = $params['title'] ? StringsManager::removeMultipleSpaces($params['title']) : StringsManager::removeDashes($key, ' ');
    $icon = $params['icon'] ? StringsManager::toKebabCase($params['icon']) : 'dashicons-editor-code';
    $override = strtolower($params['override']);
    $attributes = '';

    if (!empty($params['attributes'])) {
      $attributesList = explode(',', $params['attributes']);

      foreach ($attributesList as $attr) {
        if (!empty(trim($attr))) {
          $attributes .= ' ' . StringsManager::toKebabCase($attr) . '=""';
        }
      }
    }

    if ($override !== 'ask' && $override !== 'force') {
      $override = false;
    }

    // paths
    $basepath = Config::getRootPath() . '/' . Config::get('themes-path', true) . '/' . $theme;
    $ctrl_filename = "$key/index.php";
    $plugin_filename = "$key/index.js";
    $view_filename = "$key.html.twig";

    return [
      'key' => $key,
      'plugin-key' => $plugin_key,
      'attributes' => $attributes,
      'title' => ucwords($title),
      'icon' => $icon,
      'basepath' => $basepath,
      'ctrl-filename' => $ctrl_filename,
      'plugin-filename' => $plugin_filename,
      'view-filename' => $view_filename,
      'override' => $override,
      'theme' => $theme
    ];
  }
}

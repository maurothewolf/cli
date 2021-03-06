<?php

namespace Wordrobe\Builder;

use Wordrobe\Helper\Config;
use Wordrobe\Helper\Schema;
use Wordrobe\Helper\Dialog;
use Wordrobe\Helper\StringsManager;
use Wordrobe\Entity\Template;

/**
 * Class ServiceBuilder
 * @package Wordrobe\Builder
 */
class ServiceBuilder extends TemplateBuilder implements WizardBuilder
{
  const METHODS = ['GET', 'POST'];

  /**
   * Handles service template build wizard
   * @param null|array $args
   */
  public static function startWizard($args = null)
  {
    try {
      $theme = self::askForTheme();
      $namespace = self::askForNamespace();
      $route = self::askForRoute();
      $method = self::askForMethod();
      self::build([
        'namespace' => $namespace,
        'route' => $route,
        'method' => $method,
        'theme' => $theme,
        'override' => 'ask'
      ]);
      Dialog::write('Service added!', 'green');
    } catch (\Exception $e) {
      Dialog::write($e->getMessage(), 'red');
      exit;
    }
  }

  /**
   * Builds service
   * @param array $params
   * @example ServiceBuilder::build([
   *  'namespace' => $namespace,
   *  'route' => $route,
   *  'method' => $method,
   *  'theme' => $theme,
   *  'override' => 'ask'|'force'|false
   * ]);
   * @throws \Exception
   */
  public static function build($params)
  {
    $params = self::prepareParams($params);
    $service = new Template(
      $params['theme-path'] . '/core/services',
      'service',
      [
        '{NAMESPACE}' => $params['namespace'],
        '{ROUTE}' => $params['encoded-route'],
        '{METHOD}' => $params['method'],
        '{TEXT_DOMAIN}' => $params['text-domain']
      ]
    );
    $service->save($params['filename'], $params['override']);

    Schema::add($params['theme'], 'service', [
      'namespace' => $params['namespace'],
      'route' => $params['route'],
      'method' => $params['method']
    ]);
  }

  /**
   * Asks for namespace
   * @return string
   */
  private static function askForNamespace()
  {
    $namespace = Dialog::getAnswer('Namespace (e.g. my-plugin/v1):');
    return $namespace ?: self::askForNamespace();
  }

  /**
   * Asks for route
   * @return string
   */
  private static function askForRoute()
  {
    $route = Dialog::getAnswer('Route (e.g. /endpoint/{param}):');
    return $route ?: self::askForRoute();
  }

  /**
   * Asks for method
   * @return string
   */
  private static function askForMethod()
  {
    return Dialog::getChoice('Method [GET]:', self::METHODS, 0);
  }

  /**
   * Asks for path param
   * @param string $route
   * @return array|null
   */
  private static function checkPathParams($route)
  {
    $matches = [];
    preg_match_all('/({[^\/]+})/', $route, $matches);

    if (!empty($matches)) {
      return array_map(function($match) {
        return StringsManager::toKebabCase($match);
      }, array_unique($matches)[0]);
    }

    return null;
  }

  /**
   * Route name getter
   * @param string $route
   * @return mixed
   */
  private static function getRouteName($route)
  {
    $name = preg_replace('/(\/{[^\/]+}(\/)?)/', '/', $route);
    $name = str_replace('/', '-', $name);
    return StringsManager::toKebabCase(trim($name, '-'));
  }

  /**
   * Sanitizes route
   * @param string $route
   * @return string
   */
  private static function sanitizeRoute($route)
  {
    $paths = array_map(function($path) {
      return StringsManager::sanitize($path, true, '{}-');
    }, array_filter(explode('/', $route), function($path) {
      return !empty($path);
    }));
    return str_replace(' ', '-', implode('/', $paths));
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
    if (!$params['namespace'] || !$params['route']) {
      throw new \Exception('Error: unable to create service because of missing parameters.');
    }

    // normalizing
    $namespace = self::sanitizeRoute($params['namespace']);
    $route = '/' . self::sanitizeRoute($params['route']) . '/';
    $path_params = self::checkPathParams($params['route']);
    $method = $params['method'] && in_array($params['method'], self::METHODS) ? $params['method'] : self::METHODS[0];
    $override = strtolower($params['override']);
    $encoded_route = $route;

    if ($path_params) {
      foreach ($path_params as $param) {
        $param_regex = "(?P<$param>[a-zA-Z0-9-]+)";
        $encoded_route = str_replace('{' . $param . '}', $param_regex, $route);
      }
    }

    if ($override !== 'ask' && $override !== 'force') {
      $override = false;
    }

    // paths
    $theme_path = Config::getThemePath($theme, true);
    $filename = $namespace . '/' . self::getRouteName($params['route']) . '.php';

    return [
      'namespace' => $namespace,
      'route' => rtrim($route, '/'),
      'encoded-route' => rtrim($encoded_route, '/'),
      'method' => $method,
      'theme-path' => $theme_path,
      'filename' => $filename,
      'override' => $override,
      'theme' => $theme,
      'text-domain' => Config::get("themes.$theme.text-domain")
    ];
  }
}

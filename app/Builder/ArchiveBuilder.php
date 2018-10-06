<?php

namespace Wordrobe\Builder;

use Wordrobe\Helper\Config;
use Wordrobe\Helper\Dialog;
use Wordrobe\Entity\Template;
use Wordrobe\Helper\StringsManager;

/**
 * Class ConfigBuilder
 * @package Wordrobe\Builder
 */
class ArchiveBuilder extends TemplateBuilder implements WizardBuilder
{
  const TYPES = [
    'post-type',
    'category',
    'taxonomy',
    'tag'
  ];
  
  /**
   * Handles archive template build wizard
   */
  public static function startWizard()
  {
    try {
      $theme = self::askForTheme();
      $type = self::askForType();
  
      switch ($type) {
        case 'post-type':
          $key = self::askForPostType($theme);
          $post_type = $key;
          $taxonomy = null;
          break;
        case 'taxonomy':
          $key = self::askForTaxonomy($theme);
          $post_type = self::askForPostType($theme);
          $taxonomy = $key;
          break;
        default:
          $key = self::askForTerm();
          $post_type = self::askForPostType($theme);
          $taxonomy = self::askForTaxonomy($theme);
          break;
      }

      $entity_name = self::askForEntityName($post_type);
      
      self::build([
        'key' => $key,
        'post-type' => $post_type,
        'taxonomy' => $taxonomy,
        'entity-name' => $entity_name,
        'theme' => $theme,
        'override' => 'ask'
      ]);
      Dialog::write('Archive template added!', 'green');
    } catch (\Exception $e) {
      Dialog::write($e->getMessage(), 'red');
      exit;
    }
  }
  
  /**
   * Builds archive template
   * @param array $params
   * @example ArchiveBuilder::build([
   *  'key' => $key,
   *  'post-type' => $post_type,
   *  'taxonomy' => $taxonomy,
   *  'entity-name' => $entity_name,
   *  'theme' => $theme,
   *  'override' => 'ask'|'force'|false
   * ]);
   * @throws \Exception
   */
  public static function build($params)
  {
    $params = self::prepareParams($params);
    $archive_ctrl = new Template('archive', [
      '{TITLE}' => $params['title'],
      '{NAMESPACE}' => $params['namespace'],
      '{ENTITY_NAME}' => $params['entity-name'],
      '{QUERY}' => $params['query'],
      '{VIEW_FILENAME}' => $params['filename']
    ], $params['basepath']);
    $archive_view = new Template('view', null, $params['basepath'] . '/templates/default');
    $archive_ctrl->save($params['ctrl-filename'], $params['override']);
    $archive_view->save($params['view-filename'], $params['override']);
  }
  
  /**
   * Asks for archive type
   * @return mixed
   */
  private static function askForType()
  {
    return Dialog::getChoice('What type of archive do you want to add?', self::TYPES, null);
  }
  
  /**
   * Asks for post type
   * @param string $theme
   * @return mixed
   * @throws \Exception
   */
  private static function askForPostType($theme)
  {
    $post_types = array_keys(Config::get("themes.$theme.post-types", ['type' => 'array']));
    $post_types = array_diff($post_types, ['post']);
    
    if (!empty($post_types)) {
      return Dialog::getChoice('Post type:', array_values($post_types), null);
    }
    
    Dialog::write('Error: before creating a post-type based archive, you need to define a custom post type.', 'red');
    exit;
  }
  
  /**
   * Asks for taxonomy
   * @param string $theme
   * @return mixed
   * @throws \Exception
   */
  private static function askForTaxonomy($theme)
  {
    $taxonomies = Config::get("themes.$theme.taxonomies", ['type' => 'array']);
    $taxonomies = array_diff($taxonomies, ['category', 'tag']);
    
    if (!empty($taxonomies)) {
      return Dialog::getChoice('Taxonomy:', array_values($taxonomies), null);
    }
    
    Dialog::write('Error: before creating a taxonomy based archive, you need to define a custom taxonomy.', 'red');
    exit;
  }
  
  /**
   * Asks for term
   * @return mixed
   */
  private static function askForTerm()
  {
    $term = Dialog::getAnswer('Term:');
    return $term ?: self::askForTerm();
  }

  /**
   * Asks for entity name
   * @param string $key
   * @return mixed
   */
  private static function askForEntityName($key)
  {
    $default = StringsManager::toPascalCase($key);
    $entity_name = Dialog::getAnswer("Entity name [$default]:", $default);
    return StringsManager::toPascalCase($entity_name);
  }
  
  /**
   * Checks params existence and normalizes them
   * @param array $params
   * @return array
   * @throws \Exception
   */
  private static function prepareParams($params)
  {
    // checking theme
    $theme = StringsManager::toKebabCase($params['theme']);
    Config::check("themes.$theme", 'array', "Error: theme '$theme' doesn't exist.");

    // checking params
    if (!$params['type'] || !$params['key'] || !$params['entity-name'] || !$params['theme']) {
      throw new \Exception('Error: unable to create archive template because of missing parameters.');
    }
    
    // normalizing
    $type = StringsManager::toKebabCase($params['type']);
    $key = StringsManager::toKebabCase($params['key']);
    $title = trim(str_replace("''", '', $params['type'] . " '" . $params['key'] . "'"));
    $entity_name = StringsManager::toPascalCase($params['entity-name']);
    $override = strtolower($params['override']);
  
    if ($override !== 'ask' && $override !== 'force') {
      $override = false;
    }
    
    if (!in_array($type, self::TYPES)) {
      throw new \Exception("Error: archive type '$type' not found.");
    }
    
    if ($type === 'archive' && !in_array($key, array_keys(Config::get("themes.$theme.post-types", ['type' => 'array'])))) {
      throw new \Exception("Error: post type '$key' not found in '$theme' theme.");
    }

    // paths
    $namespace = Config::get("themes.$theme.namespace", true);
    $basepath = Config::getRootPath() . '/' . Config::get('themes-path', true) . '/' . $theme;
    $basename = $type === 'post-type' ? 'archive' : $type;
    $filename = $key ? $basename . '-' . $key : $basename;
    $ctrl_filename = "$filename.php";
    $view_filename = "$filename.html.twig";
    
    return [
      'type' => $type,
      'key' => $key,
      'title' => $title,
      'namespace' => $namespace,
      'entity-name' => $entity_name,
      'filename' => $filename,
      'basepath' => $basepath,
      'ctrl-filename' => $ctrl_filename,
      'view-filename' => $view_filename,
      'override' => $override,
      'theme' => $theme
    ];
  }
}

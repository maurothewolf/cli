<?php

namespace Wordrobe\Entity;

use Wordrobe\Config;
use Wordrobe\Helper\FilesManager;

/**
 * Class Theme
 * @package Wordrobe\Entity
 */
class Theme
{
  protected $theme_name;
  protected $theme_uri;
  protected $author;
  protected $author_uri;
  protected $description;
  protected $version;
  protected $license;
  protected $license_uri;
  protected $text_domain;
  protected $tags;
  protected $folder_name;
  protected $template_engine;
  protected $path;
  
  /**
   * Theme constructor.
   * @param $theme_name
   * @param $theme_uri
   * @param $author
   * @param $author_uri
   * @param $description
   * @param $version
   * @param $license
   * @param $license_uri
   * @param $text_domain
   * @param $tags
   * @param $folder_name
   * @param $template_engine
   */
  public function __construct($theme_name, $theme_uri, $author, $author_uri, $description, $version, $license, $license_uri, $text_domain, $tags, $folder_name, $template_engine)
  {
    $themes_path = Config::get('themes-path', true);
    $this->theme_name = $theme_name;
    $this->theme_uri = $theme_uri;
    $this->author = $author;
    $this->author_uri = $author_uri;
    $this->description = $description;
    $this->version = $version;
    $this->license = $license;
    $this->license_uri = $license_uri;
    $this->text_domain = $text_domain;
    $this->tags = $tags;
    $this->folder_name = $folder_name;
    $this->template_engine = $template_engine;
    $this->path = PROJECT_ROOT . "/$themes_path/$this->folder_name";
  }
  
  /**
   * Installs theme
   * @throws \Exception
   */
  public function install()
  {
    FilesManager::createDirectory($this->path);
    $this->copyBoilerplate();
    $this->addFunctions();
    $this->addStylesheet();
    $this->updateConfig();
  }
  
  /**
   * Adds functions.php to theme
   * @throws \Exception
   */
  protected function addFunctions()
  {
    $subdirs = explode('/', $this->path);
    $root_path = '';
    
    for ($i = 0; $i < count($subdirs) - 1; $i++) {
      $root_path .= '../';
    }
    
    $functions = new Template('theme-functions', ['{PROJECT_ROOT}' => $root_path]);
    $functions->save("$this->path/functions.php");
  }

  /**
   * Adds style.css to theme
   * @throws \Exception
   */
  protected function addStylesheet()
  {
    $stylesheet = new Template('theme-stylesheet', [
      '{THEME_NAME}' => $this->theme_name,
      '{THEME_URI}' => $this->theme_uri,
      '{AUTHOR}' => $this->author,
      '{AUTHOR_URI}' => $this->author_uri,
      '{DESCRIPTION}' => $this->description,
      '{VERSION}' => $this->version,
      '{LICENSE}' => $this->license,
      '{LICENSE_URI}' => $this->license_uri,
      '{TEXT_DOMAIN}' => $this->text_domain,
      '{TAGS}' => $this->tags
    ]);
    $stylesheet->save("$this->path/style.css");
  }
  
  /**
   * Adds theme params to Config
   * @return array
   * @throws \Exception
   */
  protected function updateConfig()
  {
    $themeConfig = new Template('theme-config', ['{TEMPLATE_ENGINE}' => $this->template_engine]);
    $content = $themeConfig->getContent();
    return Config::set("themes.$this->folder_name", json_decode($content));
  }
  
  /**
   * Copies theme boilerplate
   * @throws \Exception
   */
  private function copyBoilerplate()
  {
    $commonsFilesPath = BOILERPLATES_PATH . '/commons';
    $specificFilesPath = BOILERPLATES_PATH . '/' . $this->template_engine;
    FilesManager::copyFiles($commonsFilesPath, $this->path);
    FilesManager::copyFiles($specificFilesPath, $this->path);
  }
}

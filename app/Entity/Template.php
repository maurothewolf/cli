<?php

namespace Wordrobe\Entity;

use Wordrobe\Helper\FilesManager;
use Wordrobe\Helper\Dialog;

/**
 * Class Template
 * @package Wordrobe\Entity
 */
class Template
{
  protected $content;
  
  /**
   * Template constructor.
   * @param string $model
   * @param null|array $replacements
   * @throws \Exception
   */
  public function __construct($model, $replacements = null)
  {
    $this->content = self::getModelContent($model);
    // auto-fill
    if (is_array($replacements)) {
      foreach ($replacements as $placeholder => $replacement) {
        $this->fill($placeholder, $replacement);
      }
    }
  }
  
  /**
   * Content getter
   * @return string
   */
  public function getContent()
  {
    return $this->content;
  }
  
  /**
   * Content setter
   * @param string $content
   */
  public function setContent($content)
  {
    $this->content = $content;
  }
  
  /**
   * Replaces template placeholder
   * @param string $placeholder
   * @param string $value
   */
  public function fill($placeholder, $value)
  {
    $this->content = str_replace($placeholder, $value, $this->content);
  }
  
  /**
   * Saves template in a file
   * @param string $filepath
   * @param mixed $override
   * @throws \Exception
   */
  public function save($filepath, $override = false)
  {
    $force_override = false;
    
    switch ($override) {
      case 'force':
        $force_override = true;
        break;
      case 'ask':
        if (FilesManager::fileExists($filepath)) {
          $force_override = Dialog::getConfirmation('Attention: ' . $filepath . ' already exists! Do you want to override it?', false, 'red');
        }
        break;
      default:
        break;
    }
  
    FilesManager::writeFile($filepath, $this->content, $force_override);
  }
  
  /**
   * Model content getter
   * @param string $model
   * @return string
   * @throws \Exception
   */
  private static function getModelContent($model)
  {
    return FilesManager::readFile(dirname(__DIR__) . '/templates/' . $model);
  }
}

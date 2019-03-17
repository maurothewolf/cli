<?php

namespace Example\Entity;

use Wordrobe\Feature\EntityInterface;

/**
 * Class Entity
 * @package Example\Entity
 */
class Entity implements EntityInterface
{
  /**
   * @var string $title
   */
  protected $title;

  /**
   * @var string $url
   */
  protected $url;

  /**
   * @var string $content
   */
  protected $content;

  /**
   * @var array $custom_fields
   */
  protected $custom_fields;

  /**
   * Entity's constructor
   * @param \Timber\Post $post
   */
  public function __construct(\Timber\Post $post)
  {
    $this->title = $post->title();
    $this->content = $post->content();
    $this->url = $post->link();
    $this->custom_fields = function_exists('get_fields') ? get_fields($post->ID) : [];
  }

  /**
   * Entity's title getter
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Entity's content getter
   * @return string
   */
  public function getContent()
  {
    return $this->content;
  }

  /**
   * Entity's url getter
   * @return string
   */
  public function getUrl()
  {
    return $this->url;
  }

  /**
   * Entity's custom fields getter
   * @return array
   */
  public function getCustomFields()
  {
    return $this->custom_fields;
  }
}

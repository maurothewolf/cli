<?php

namespace Wordrobe\Builder;

use Wordrobe\Config;
use Wordrobe\Helper\Dialog;
use Wordrobe\Helper\StringsManager;
use Wordrobe\Entity\Template;

class TaxonomyBuilder extends TemplateBuilder implements Builder
{
	/**
	 * Handles taxonomy creation wizard
	 */
	public static function startWizard()
	{
		$theme = self::askForTheme();
		$key = self::askForKey();
		$general_name = self::askForGeneralName($key);
		$singular_name = self::askForSingularName($general_name);
		$text_domain = self::askForTextDomain($theme);
		$post_types = self::askForPostTypes($theme);
		$hierarchical = self::askForHierarchy();
		self::build([
			'key' => $key,
			'general_name' => $general_name,
			'singular_name' => $singular_name,
			'text_domain' => $text_domain,
			'post_types' => $post_types,
			'hierarchical' => $hierarchical,
			'theme' => $theme
		]);
	}

	/**
	 * Builds taxonomy
	 * @param array $params
	 * @example TaxonomyBuilder::create([
	 * 	'key' => $key,
	 *	'general_name' => $general_name,
	 *	'singular_name' => $singular_name,
	 *	'text_domain' => $text_domain,
	 *	'post_types' => $post_types,
	 *	'hierarchical' => $hierarchical,
	 *	'theme' => $theme
	 * ]);
	 */
	public static function build($params)
	{
		$key = $params['key'];
		$general_name = $params['general_name'];
		$singular_name = $params['singular_name'];
		$text_domain = $params['text_domain'];
		$post_types = $params['post_types'];
		$hierarchical = $params['hierarchical'];
		$theme = $params['theme'];

		if (!$key || !$general_name || !$singular_name || !$text_domain || !$post_types || !$theme) {
			Dialog::write('Error: unable to create taxonomy because of missing parameters.', 'red');
			exit;
		}

		$theme_path = PROJECT_ROOT . '/' . Config::expect('themes_path') . '/' . $theme;
		$taxonomy = new Template('taxonomy', [
			'{KEY}' => $key,
			'{GENERAL_NAME}' => $general_name,
			'{SINGULAR_NAME}' => $singular_name,
			'{TEXT_DOMAIN}' => $text_domain,
			'{POST_TYPES}' => $post_types,
			'{HIERARCHICAL}' => $hierarchical
		]);
		$saved = $taxonomy->save("$theme_path/includes/taxonomies/$key.php");
		Config::add("themes.$theme.taxonomies", $key);

		if ($saved) {
			Dialog::write("Taxonomy '$key' added!", 'green');
		}

		ArchiveBuilder::build([
			'type' => 'taxonomy',
			'key' => $key,
			'theme' => $theme
		]);
	}

	/**
	 * Asks for taxonomy key
	 * @return mixed
	 */
	private static function askForKey()
	{
		$key = Dialog::getAnswer('Taxonomy key (e.g. type):');

		if (!$key) {
			return self::askForKey();
		}

		return StringsManager::toKebabCase($key);
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

		if (!$general_name) {
			return self::askForGeneralName($key);
		}

		return ucwords($general_name);
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

		if (!$singular_name) {
			return self::askForSingularName($general_name);
		}

		return ucwords($singular_name);
	}

	/**
	 * Asks for text domain
	 * @param $theme
	 * @return mixed
	 */
	private static function askForTextDomain($theme)
	{
		$text_domain = Dialog::getAnswer("Text domain [$theme]:", $theme);

		if (!$text_domain) {
			return self::askForTextDomain($theme);
		}

		return StringsManager::toKebabCase($text_domain);
	}

	/**
	 * Asks for post types
	 * @param $theme
	 * @return string
	 */
	private static function askForPostTypes($theme)
	{
		$post_types = Dialog::getChoice('Post types:', Config::expect("themes.$theme.post_types", 'array'), null, true);
		return implode(',', $post_types);

	}

	/**
	 * Asks for hierarchy
	 */
	private static function askForHierarchy()
	{
		return Dialog::getConfirmation('Is hierarchical?', true, 'blue');
	}
}

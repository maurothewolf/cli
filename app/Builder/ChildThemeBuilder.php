<?php

namespace Wordrobe\Builder;

use Wordrobe\Config;
use Wordrobe\Helper\Dialog;
use Wordrobe\Helper\StringsManager;
use Wordrobe\Entity\ChildTheme;

/**
 * Class ChildThemeBuilder
 * @package Wordrobe\Builder
 */
class ChildThemeBuilder extends ThemeBuilder
{
    /**
     * Handles child theme creation wizard
     */
    public static function startWizard()
    {
        Config::expect('themes-path');
        $theme_name = parent::askForThemeName();
        $theme_uri = parent::askForThemeURI();
        $author = parent::askForAuthor();
        $author_uri = parent::askForAuthorURI();
        $description = parent::askForDescription();
        $version = parent::askForVersion();
        $license = parent::askForLicense();
        $license_uri = parent::askForLicenseURI();
        $text_domain = parent::askForTextDomain($theme_name);
        $tags = parent::askForTags();
        $folder_name = parent::askForFolderName($theme_name);
        $parent = self::askForParentTheme();
        self::build([
            'theme-name' => $theme_name,
            'theme-uri' => $theme_uri,
            'author' => $author,
            'author-uri' => $author_uri,
            'description' => $description,
            'version' => $version,
            'license' => $license,
            'license-uri' => $license_uri,
            'text-domain' => $text_domain,
            'tags' => $tags,
            'folder-name' => $folder_name,
            'parent' => $parent
        ]);
    }

    /**
     * Builds child theme
     * @param array $params
     * @example ChildThemeBuilder::create([
     *	'theme-name' => $theme_name,
     *	'theme-uri' => $theme_uri,
     *	'author' => $author,
     *	'author-uri' => $author_uri,
     *	'description' => $description,
     *	'version' => $version,
     *	'license' => $license,
     *	'license-uri' => $license_uri,
     *	'text-domain' => $text_domain,
     *	'tags' => $tags,
     *	'folder-name' => $folder_name,
     *	'parent' => $parent
     * ]);
     */
    public static function build($params)
    {
        $theme_name = $params['theme-name'];
        $theme_uri = $params['theme-uri'];
        $author = $params['author'];
        $author_uri = $params['author-uri'];
        $description = $params['description'];
        $version = $params['version'];
        $license = $params['license'];
        $license_uri = $params['license-uri'];
        $text_domain = $params['text-domain'];
        $tags = $params['tags'];
        $folder_name = $params['folder-name'];
        $parent = $params['parent'];

        if (!$theme_name || !$text_domain || !$folder_name || !$parent) {
            Dialog::write('Error: unable to create archive because of missing parameters.', 'red');
            exit;
        }

        $theme = new ChildTheme($theme_name, $theme_uri, $author, $author_uri, $description, $version, $license, $license_uri, $text_domain, $tags, $folder_name, $parent);
		$installed = $theme->install();

		if ($installed) {
			Dialog::write('Child theme installed!', 'green');
		}
    }

    /**
     * Asks for child theme's parent
     * @return mixed
     */
    protected static function askForParentTheme()
    {
        $parent_theme = Dialog::getAnswer('Parent theme:');

        if (!$parent_theme) {
            return self::askForParentTheme();
        }

        return StringsManager::toKebabCase($parent_theme);
    }
}

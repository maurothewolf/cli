<?php

namespace Wordrobe\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wordrobe\Config;
use Wordrobe\Helper\Dialog;
use Wordrobe\Helper\StringsManager;

/**
 * Class AddCommand
 * @package Wordrobe\Command
 */
class AddCommand extends BaseCommand
{
	const CONTENT_TYPES = [
		'theme',
		'child-theme',
		'page',
		'single',
		'archive',
		'post-type',
		'taxonomy',
		'term',
		'ajax-service',
		'shortcode',
		'widget'
	];

	protected function configure()
	{
		$this->setName('add');
		$this->setDescription("Adds a new content to your project");
		$this->addArgument('content-type', InputArgument::OPTIONAL, 'The content type');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		if (!Config::exists()) {
			$runInit = Dialog::getConfirmation('Your project is not configured yet. Do you want to run setup right now?', true, 'yellow');
			if (!$runInit) {
				exit();
			}
			$command = $this->getApplication()->find('init');
			$arguments = ['command' => 'init'];
			$command->run(new ArrayInput($arguments), Dialog::$output);
			return self::execute($input, $output);
		}

		if (!$content_type = Dialog::read('content-type')) {
			$content_type = Dialog::getChoice('What kind of content do you want to add?', self::CONTENT_TYPES, null);
		}

		if (in_array($content_type, self::CONTENT_TYPES) && $factory = self::getFactory($content_type)) {
			$factory::startWizard();
		} else {
			Dialog::write("Content type '$content_type' not found", 'red');
			$blank_add_command = $this->getApplication()->find('add');
			$blank_add_command->run(new ArrayInput([]), $output);
		}
	}

	/**
	 * Entity factory getter
	 *
	 * @param $name
	 * @return string
	 */
	protected function getFactory($name)
	{
		$factory = 'Wordrobe\Factory\\' . StringsManager::toPascalCase($name) . 'Factory';
		if (!class_exists($factory)) {
			Dialog::write('Error: ' . $factory . ' is not defined.', 'red');
			return null;
		}
		return $factory;
	}
}

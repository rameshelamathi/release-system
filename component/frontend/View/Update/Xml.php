<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Model\Environments;
use FOF30\Container\Container;
use FOF30\Date\Date;
use FOF30\Model\DataModel\Exception\RecordNotLoaded;
use FOF30\View\DataView\Raw;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\ComponentHelper as JComponentHelper;

class Xml extends Raw
{
	use Common;

	public $items = [];

	public $published = false;

	public $updates_name = '';

	public $updates_desc = '';

	public $category = 0;

	public $envs = [];

	public $showChecksums = false;

	public $filteredItemIDs = null;

	public function display($tpl = null): bool
	{
		$task = $this->getModel()->getState('task', 'all');

		if (!in_array($task, ['all', 'category', 'stream', 'jed']))
		{
			$this->doTask = 'all';
		}

		$this->container->platform->getDocument()->setMimeEncoding('text/xml');

		@ob_start();

		$ret      = parent::display($tpl);
		$document = @ob_get_clean();

		$minifyXML = $this->container->params->get('minify_xml', 1) == 1;

		$dom                     = new \DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = !$minifyXML;

		$dom->loadXML($document);
		unset($document);

		/**
		 * Insert a vanity comment in the non-minified output.
		 *
		 * I only ever use this when debugging the update stream generation. The production site always has minification
		 * turned on. Since we're charged per byte transferred for updates it makes sense :)
		 */
		if (!$minifyXML)
		{
			$rootNode = $dom->firstChild;

			$dom->removeChild($rootNode);

			$comment = $dom->createComment(sprintf('Generated by Akeeba Release System on %s', (new Date())->format('Y-m-d H:i:s T')));

			$dom->insertBefore($comment);
			$dom->insertBefore($rootNode);
		}

		echo $dom->saveXML();

		return $ret;
	}

	protected function onBeforeAll(): void
	{
		$this->commonSetup();

		$params = JComponentHelper::getParams('com_ars');

		$this->updates_name = $params->get('updates_name', '');
		$this->updates_desc = $params->get('updates_desc', '');

		$this->setLayout('all');
	}

	protected function onBeforeCategory(): void
	{
		$this->commonSetup();

		$category       = $this->input->getCmd('id', '');
		$this->category = $category;

		$this->setLayout('category');
	}

	protected function onBeforeStream(): void
	{
		$this->commonSetup();

		/** @var Environments $envmodel */
		$envmodel = $this->container->factory->model('Environments')->tmpInstance();
		$rawenvs  = $envmodel->get(true);
		$envs     = [];

		if ($rawenvs->count())
		{
			foreach ($rawenvs as $env)
			{
				$envs[$env->id] = $env;
			}
		}

		$this->envs          = $envs;
		$this->showChecksums = $this->container->params->get('show_checksums', 0) == 1;
		$this->setLayout('stream');

		/**
		 * Use Version Compatibility information to cut down the number of displayed versions?
		 */
		if ($this->container->params->get('use_compatibility', 1) == 1)
		{
			$this->applyVersionCompatibilityUpdateStreamFilter();
		}
	}

	protected function applyVersionCompatibilityUpdateStreamFilter(): void
	{
		if (!ComponentHelper::isEnabled('com_compatibility'))
		{
			return;
		}

		$container = Container::getInstance('com_compatibility', [
			'tempInstance' => true,
		]);

		if (empty($this->category))
		{
			return;
		}

		try
		{
			$updateStream = $this->container->factory->model('UpdateStreams')->tmpInstance()
				->findOrFail($this->category);
			$category     = $this->container->factory->model('Categories')->tmpInstance()
				->findOrFail($updateStream->category);
		}
		catch (RecordNotLoaded $e)
		{
			return;
		}

		$displayData = $container->factory->model('Compatibility')->tmpInstance()->getDisplayData();
		$displayData = array_filter($displayData, function ($extensionData) use ($category) {
			return $extensionData['slug'] == $category->slug;
		});

		if (empty($displayData))
		{
			return;
		}

		$extensionData         = array_pop($displayData);
		$this->filteredItemIDs = [];

		foreach ($extensionData['matrix'] as $jVersion => $perPHPVersion)
		{
			foreach ($perPHPVersion as $phpVersion => $versionInfo)
			{
				if (empty($versionInfo))
				{
					continue;
				}

				$id = $versionInfo['id'] ?? null;

				if (empty($id))
				{
					continue;
				}

				$this->filteredItemIDs[] = $id;
			}
		}

		$this->filteredItemIDs = array_unique($this->filteredItemIDs);
	}
}

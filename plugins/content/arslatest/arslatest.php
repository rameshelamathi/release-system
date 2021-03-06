<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Akeeba\ReleaseSystem\Site\Helper\Filter;
use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF30\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\String\StringHelper;

defined('_JEXEC') or die();

class plgContentArslatest extends CMSPlugin
{
	/**
	 * The component container
	 *
	 * @var   Container
	 */
	protected $container;

	/** @var bool Is this category prepared? */
	private $prepared = false;

	/** @var array Category titles to category IDs */
	private $categoryTitles = array();

	/** @var array The latest release per category, including files */
	private $categoryLatest = array();

	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the ARS component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			$this->enabled = false;
		}

		// Do not run if Akeeba Subscriptions is not enabled
		if (!ComponentHelper::isEnabled('com_ars'))
		{
			$this->enabled = false;

			return;
		}

		if ($this->enabled)
		{
			$this->container = Container::getInstance('com_ars');
		}
	}

	/**
	 * Content preparation plugin hook
	 *
	 * @param string $context
	 * @param object $row
	 * @param array  $params
	 * @param int    $limitstart
	 */
	public function onContentPrepare($context, &$row, &$params, $limitstart = 0)
	{
		if (!$this->enabled)
		{
			return true;
		}

		$text = is_object($row) ? $row->text : $row;

		if (StringHelper::strpos($row->text, 'arslatest') !== false)
		{
			if (!$this->prepared)
			{
				// Deferred initialisation to the very last possible minute
				$this->initialise();
			}

			$regex = "#{arslatest(.*?)}#s";
			$text  = preg_replace_callback($regex, array($this, 'process'), $text);
		}

		if (is_object($row))
		{
			$row->text = $text;
		}
		else
		{
			$row = $text;
		}
	}

	/**
	 * preg_match callback to process each match
	 */
	private function process(array $match): string
	{
		$ret = '';

		[$op, $content, $pattern] = $this->analyzeString($match[1]);

		switch (strtolower($op))
		{
			case 'release':
				$ret = $this->parseRelease($content);
				break;
			case 'release_link':
				$ret = $this->parseReleaseLink($content);
				break;
			case 'item_link':
				$ret = $this->parseItemLink($content, $pattern);
				break;
			case 'stream_link':
				$ret = $this->parseStreamLink($content);
				break;
			case 'installfromweb':
				$installat  = $this->container->platform->getSessionVar('installat', null, 'arsjed');
				$installapp = $this->container->platform->getSessionVar('installapp', null, 'arsjed');

				if (!empty($installapp) && !empty($installat))
				{
					$ret = $this->parseIFWLink();
				}
				else
				{
					$ret = $this->parseStreamLink($content);
				}
				break;
		}

		return $ret;
	}

	/**
	 * Inisialises the arrays.
	 */
	private function initialise(): void
	{
		// Make sure our auto-loader is set up and ready
		$container = \FOF30\Container\Container::getInstance('com_ars');

		/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $model */
		$model = $container->factory->model('Releases')->tmpInstance();
		$model->reset(true)
		      ->published(1)
		      ->latest(true)
		      ->access_user($container->platform->getUser()->id)
		      ->with(['items', 'category']);

		/** @var \FOF30\Model\DataModel\Collection $releases */
		$releases = $model->get(true)->filter(function ($item)
		{
			return \Akeeba\ReleaseSystem\Site\Helper\Filter::filterItem($item, true);
		});

		$cats = [];

		if ($releases->count())
		{
			/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $release */
			foreach ($releases as $release)
			{
				$cat                                 = $release->category;
				$cat->title                          = trim(strtoupper($cat->title));
				$cats[]                              = $cat;
				$this->categoryTitles[ $cat->title ] = $cat->id;
				$this->categoryLatest[ $cat->id ]    = $release;
			}
		}

		$this->prepared = true;
	}

	private function analyzeString(string $string): array
	{
		$op      = '';
		$content = '';
		$pattern = '';

		$string = trim($string);
		$string = strtoupper($string);
		$parts  = explode(' ', $string, 2);

		if (count($parts) == 2)
		{
			$op = trim($parts[0]);
			if (in_array($op, array('RELEASE', 'RELEASE_LINK', 'STREAMLINK', 'INSTALLFROMWEB')))
			{
				$content = trim($parts[1]);
			}
			elseif ($op == 'ITEM_LINK')
			{
				$content    = trim($parts[1]);
				$firstquote = strpos($content, "'");

				if ($firstquote !== false)
				{
					$secondquote = strpos($content, "'", $firstquote + 1);
				}
				else
				{
					$secondquote = false;
				}

				if ($secondquote !== false)
				{
					$pattern = trim(substr($content, 0, $secondquote), "'");
					$content = trim(substr($content, $secondquote + 1));
				}
			}
			else
			{
				$op = '';
			}
		}

		if (empty($op))
		{
			$content = '';
		}

		if (empty($content))
		{
			$op = '';
		}

		if (empty($content))
		{
			$pattern = '';
		}

		return array($op, $content, $pattern);
	}

	/**
	 * @param   string  $content
	 *
	 * @return  Releases
	 */
	private function getLatestRelease(string $content): ?Releases
	{
		$release = null;

		if (array_key_exists($content, $this->categoryTitles))
		{
			$catid = $this->categoryTitles[ $content ];
		}
		else
		{
			// guessing it is a category id
			$catid = (int) $content;
		}

		if (!array_key_exists($catid, $this->categoryLatest))
		{
			return $release;
		}

		$release = $this->categoryLatest[ $catid ];

		if (empty($release))
		{
			$release = null;
		}

		return $release;
	}

	/**
	 * @param   string  $content
	 *
	 * @return  string
	 */
	private function parseRelease(string $content): string
	{
		$release = $this->getLatestRelease($content);

		if (empty($release))
		{
			return '';
		}

		return $release->version;
	}

	/**
	 * @param   string  $content
	 *
	 * @return  string
	 */
	private function parseReleaseLink(string $content): string
	{
		$release = $this->getLatestRelease($content);

		if (empty($release))
		{
			return '';
		}

		$link      = Route::_('index.php?option=com_ars&view=Items&release_id=' . $release->id);
        $container = \FOF30\Container\Container::getInstance('com_ars');

        if (!Filter::filterItem($release, false) && !empty($release->redirect_unauth))
        {
	        $link = $release->redirect_unauth;
        }

		return $link;
	}

	/**
	 * @param   string  $content
	 * @param   string  $pattern
	 *
	 * @return  string
	 */
	private function parseItemLink(string $content, string $pattern): string
	{
		$release = $this->getLatestRelease($content);

		if (empty($release))
		{
			return '';
		}

		$item = null;

		/** @var \Akeeba\ReleaseSystem\Site\Model\Items $file */
		foreach ($release->items as $file)
		{
			if ($file->type == 'file')
			{
				$fname = $file->filename;
			}
			else
			{
				$fname = $file->url;
			}

			$fname = strtoupper(basename($fname));

			if (fnmatch($pattern, $fname))
			{
				$item = $file;
				break;
			}
		}

		if (empty($item))
		{
			return '';
		}

		$link = Route::_('index.php?option=com_ars&view=Item&task=download&format=raw&id=' . $item->id);

        $container = \FOF30\Container\Container::getInstance('com_ars');

		if (!Filter::filterItem($item, false) && !empty($item->redirect_unauth))
		{
			$link = $item->redirect_unauth;
		}

		return $link;
	}

	private function parseStreamLink(string $content): string
	{
		static $dlid = '';

		$user = $this->container->platform->getUser();

		if (empty($dlid) && !$user->guest)
		{
			$dlid = \Akeeba\ReleaseSystem\Site\Helper\Filter::myDownloadID();
		}

		$url  = 'index.php?option=com_ars&view=update&task=Item&format=raw&id=' . (int) $content;

		if (!empty($dlid))
		{
			$url .= '&dlid=' . $dlid;
		}

		$link = Route::_($url, false);

		return $link;
	}

	/**
	 * Provide the Install from Web link
	 *
	 * @return string
	 */
	private function parseIFWLink(): string
	{
		$installat  = $this->container->platform->getSessionVar('installat', null, 'arsjed');
		$installapp = (int) ($this->container->platform->getSessionVar('installapp', null, 'arsjed'));

		// Find the stream ID based on the $installapp key
		$db    = $this->container->db;
		$query = $db->getQuery(true)
		            ->select($db->qn('id'))
		            ->from('#__ars_updatestreams')
		            ->where($db->qn('jedid') . '=' . $db->q($installapp));
		$db->setQuery($query);
		$streamId = $db->loadResult();

		$downloadLink = $this->parseStreamLink($streamId);

		$link = $installat . '&installfrom=' . base64_encode($downloadLink);

		return $link;
	}
}

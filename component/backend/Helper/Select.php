<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use Akeeba\ReleaseSystem\Admin\Model\Categories;
use Akeeba\ReleaseSystem\Admin\Model\Environments;
use Akeeba\ReleaseSystem\Admin\Model\Items;
use Akeeba\ReleaseSystem\Admin\Model\Releases;
use Akeeba\ReleaseSystem\Admin\Model\SubscriptionIntegration;
use Akeeba\ReleaseSystem\Admin\Model\UpdateStreams;
use FOF30\Container\Container;
use Joomla\CMS\Filesystem\File as JFile;
use Joomla\CMS\Filesystem\Folder as JFolder;
use Joomla\CMS\Filesystem\Path as JPath;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\LanguageHelper as JLanguageHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

abstract class Select
{

	/**
	 * A list of all the ISO 2-country codes and the full country names in English
	 *
	 * @var array
	 */
	private static $countries = [
		''   => '----',
		'AD' => 'Andorra',
		'AE' => 'United Arab Emirates',
		'AF' => 'Afghanistan',
		'AG' => 'Antigua and Barbuda',
		'AI' => 'Anguilla',
		'AL' => 'Albania',
		'AM' => 'Armenia',
		'AN' => 'Netherlands Antilles',
		'AO' => 'Angola',
		'AQ' => 'Antarctica',
		'AR' => 'Argentina',
		'AS' => 'American Samoa',
		'AT' => 'Austria',
		'AU' => 'Australia',
		'AW' => 'Aruba',
		'AX' => 'Aland Islands',
		'AZ' => 'Azerbaijan',
		'BA' => 'Bosnia and Herzegovina',
		'BB' => 'Barbados',
		'BD' => 'Bangladesh',
		'BE' => 'Belgium',
		'BF' => 'Burkina Faso',
		'BG' => 'Bulgaria',
		'BH' => 'Bahrain',
		'BI' => 'Burundi',
		'BJ' => 'Benin',
		'BL' => 'Saint Barthélemy',
		'BM' => 'Bermuda',
		'BN' => 'Brunei Darussalam',
		'BO' => 'Bolivia, Plurinational State of',
		'BR' => 'Brazil',
		'BS' => 'Bahamas',
		'BT' => 'Bhutan',
		'BV' => 'Bouvet Island',
		'BW' => 'Botswana',
		'BY' => 'Belarus',
		'BZ' => 'Belize',
		'CA' => 'Canada',
		'CC' => 'Cocos (Keeling) Islands',
		'CD' => 'Congo, the Democratic Republic of the',
		'CF' => 'Central African Republic',
		'CG' => 'Congo',
		'CH' => 'Switzerland',
		'CI' => 'Cote d\'Ivoire',
		'CK' => 'Cook Islands',
		'CL' => 'Chile',
		'CM' => 'Cameroon',
		'CN' => 'China',
		'CO' => 'Colombia',
		'CR' => 'Costa Rica',
		'CU' => 'Cuba',
		'CV' => 'Cape Verde',
		'CX' => 'Christmas Island',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DE' => 'Germany',
		'DJ' => 'Djibouti',
		'DK' => 'Denmark',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'DZ' => 'Algeria',
		'EC' => 'Ecuador',
		'EE' => 'Estonia',
		'EG' => 'Egypt',
		'EH' => 'Western Sahara',
		'ER' => 'Eritrea',
		'ES' => 'Spain',
		'ET' => 'Ethiopia',
		'FI' => 'Finland',
		'FJ' => 'Fiji',
		'FK' => 'Falkland Islands (Malvinas)',
		'FM' => 'Micronesia, Federated States of',
		'FO' => 'Faroe Islands',
		'FR' => 'France',
		'GA' => 'Gabon',
		'GB' => 'United Kingdom',
		'GD' => 'Grenada',
		'GE' => 'Georgia',
		'GF' => 'French Guiana',
		'GG' => 'Guernsey',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GL' => 'Greenland',
		'GM' => 'Gambia',
		'GN' => 'Guinea',
		'GP' => 'Guadeloupe',
		'GQ' => 'Equatorial Guinea',
		'GR' => 'Greece',
		'GS' => 'South Georgia and the South Sandwich Islands',
		'GT' => 'Guatemala',
		'GU' => 'Guam',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HK' => 'Hong Kong',
		'HM' => 'Heard Island and McDonald Islands',
		'HN' => 'Honduras',
		'HR' => 'Croatia',
		'HT' => 'Haiti',
		'HU' => 'Hungary',
		'ID' => 'Indonesia',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IM' => 'Isle of Man',
		'IN' => 'India',
		'IO' => 'British Indian Ocean Territory',
		'IQ' => 'Iraq',
		'IR' => 'Iran, Islamic Republic of',
		'IS' => 'Iceland',
		'IT' => 'Italy',
		'JE' => 'Jersey',
		'JM' => 'Jamaica',
		'JO' => 'Jordan',
		'JP' => 'Japan',
		'KE' => 'Kenya',
		'KG' => 'Kyrgyzstan',
		'KH' => 'Cambodia',
		'KI' => 'Kiribati',
		'KM' => 'Comoros',
		'KN' => 'Saint Kitts and Nevis',
		'KP' => 'Korea, Democratic People\'s Republic of',
		'KR' => 'Korea, Republic of',
		'KW' => 'Kuwait',
		'KY' => 'Cayman Islands',
		'KZ' => 'Kazakhstan',
		'LA' => 'Lao People\'s Democratic Republic',
		'LB' => 'Lebanon',
		'LC' => 'Saint Lucia',
		'LI' => 'Liechtenstein',
		'LK' => 'Sri Lanka',
		'LR' => 'Liberia',
		'LS' => 'Lesotho',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'LV' => 'Latvia',
		'LY' => 'Libyan Arab Jamahiriya',
		'MA' => 'Morocco',
		'MC' => 'Monaco',
		'MD' => 'Moldova, Republic of',
		'ME' => 'Montenegro',
		'MF' => 'Saint Martin (French part)',
		'MG' => 'Madagascar',
		'MH' => 'Marshall Islands',
		'MK' => 'Macedonia, the former Yugoslav Republic of',
		'ML' => 'Mali',
		'MM' => 'Myanmar',
		'MN' => 'Mongolia',
		'MO' => 'Macao',
		'MP' => 'Northern Mariana Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MS' => 'Montserrat',
		'MT' => 'Malta',
		'MU' => 'Mauritius',
		'MV' => 'Maldives',
		'MW' => 'Malawi',
		'MX' => 'Mexico',
		'MY' => 'Malaysia',
		'MZ' => 'Mozambique',
		'NA' => 'Namibia',
		'NC' => 'New Caledonia',
		'NE' => 'Niger',
		'NF' => 'Norfolk Island',
		'NG' => 'Nigeria',
		'NI' => 'Nicaragua',
		'NL' => 'Netherlands',
		'NO' => 'Norway',
		'NP' => 'Nepal',
		'NR' => 'Nauru',
		'NU' => 'Niue',
		'NZ' => 'New Zealand',
		'OM' => 'Oman',
		'PA' => 'Panama',
		'PE' => 'Peru',
		'PF' => 'French Polynesia',
		'PG' => 'Papua New Guinea',
		'PH' => 'Philippines',
		'PK' => 'Pakistan',
		'PL' => 'Poland',
		'PM' => 'Saint Pierre and Miquelon',
		'PN' => 'Pitcairn',
		'PR' => 'Puerto Rico',
		'PS' => 'Palestinian Territory, Occupied',
		'PT' => 'Portugal',
		'PW' => 'Palau',
		'PY' => 'Paraguay',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RS' => 'Serbia',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'SA' => 'Saudi Arabia',
		'SB' => 'Solomon Islands',
		'SC' => 'Seychelles',
		'SD' => 'Sudan',
		'SE' => 'Sweden',
		'SG' => 'Singapore',
		'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
		'SI' => 'Slovenia',
		'SJ' => 'Svalbard and Jan Mayen',
		'SK' => 'Slovakia',
		'SL' => 'Sierra Leone',
		'SM' => 'San Marino',
		'SN' => 'Senegal',
		'SO' => 'Somalia',
		'SR' => 'Suriname',
		'ST' => 'Sao Tome and Principe',
		'SV' => 'El Salvador',
		'SY' => 'Syrian Arab Republic',
		'SZ' => 'Swaziland',
		'TC' => 'Turks and Caicos Islands',
		'TD' => 'Chad',
		'TF' => 'French Southern Territories',
		'TG' => 'Togo',
		'TH' => 'Thailand',
		'TJ' => 'Tajikistan',
		'TK' => 'Tokelau',
		'TL' => 'Timor-Leste',
		'TM' => 'Turkmenistan',
		'TN' => 'Tunisia',
		'TO' => 'Tonga',
		'TR' => 'Turkey',
		'TT' => 'Trinidad and Tobago',
		'TV' => 'Tuvalu',
		'TW' => 'Taiwan, Province of China',
		'TZ' => 'Tanzania, United Republic of',
		'UA' => 'Ukraine',
		'UG' => 'Uganda',
		'UM' => 'United States Minor Outlying Islands',
		'US' => 'United States',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VA' => 'Holy See (Vatican City State)',
		'VC' => 'Saint Vincent and the Grenadines',
		'VE' => 'Venezuela, Bolivarian Republic of',
		'VG' => 'Virgin Islands, British',
		'VI' => 'Virgin Islands, U.S.',
		'VN' => 'Viet Nam',
		'VU' => 'Vanuatu',
		'WF' => 'Wallis and Futuna',
		'WS' => 'Samoa',
		'YE' => 'Yemen',
		'YT' => 'Mayotte',
		'ZA' => 'South Africa',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
	];

	/**
	 * The component container
	 *
	 * @var   Container
	 */
	private static $container;

	/**
	 * Cache of environment IDs to their titles
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	private static $environmentTitles;

	/**
	 * Get the component's container
	 *
	 * @return  Container
	 */
	private static function getContainer(): Container
	{
		if (is_null(self::$container))
		{
			self::$container = Container::getInstance('com_ars');
		}

		return self::$container;
	}

	/**
	 * Creates a generic SELECT element
	 *
	 * @param array  $list     A list of options generated by JHtml::_('FEFHelper.select.option'), calls
	 * @param string $name     The field name
	 * @param array  $attribs  HTML attributes for the field
	 * @param mixed  $selected The pre-selected value
	 * @param string $idTag    The HTML id attribute of the field (do NOT add in $attribs)
	 *
	 * @return  string  The HTML for the SELECT field
	 */
	protected static function genericlist(array $list, string $name, array $attribs, $selected, string $idTag)
	{
		if (empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';

			foreach ($attribs as $key => $value)
			{
				$temp .= $key . ' = "' . $value . '"';
			}

			$attribs = $temp;
		}

		return JHtml::_('FEFHelper.select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	/**
	 * Convert the ISO-3316 country code (e.g. US) to its human-readable, English country name (e.g. United States of
	 * America).
	 *
	 * @param string $country
	 *
	 * @return string
	 *
	 * @since 5.0.0
	 */
	public static function countryDecode(string $country): string
	{
		if (isset(static::$countries[$country]))
		{
			return static::$countries[$country];
		}

		return '---';
	}

	/**
	 * Converts an ISO country code to an emoji flag.
	 *
	 * This is stupidly easy. An emoji flag is the country code using Unicode Regional Indicator Symbol Letter glyphs
	 * instead of the regular ASCII characters. Thus US becomes \u1F1FA\u1F1F8 which is incidentally the emoji for the
	 * US flag :)
	 *
	 * On really old browsers (pre-2015) this still renders as the country code since the Regional Indicator Symbol
	 * Letter glyphs were added to Unicode in 2010. Now, if you have an even older browser -- what the heck, dude?!
	 *
	 * @param string $cCode
	 *
	 * @return string
	 *
	 * @since 5.0.0
	 */
	public static function countryToEmoji(string $cCode = ''): string
	{
		$name = self::countryDecode($cCode);

		if (empty($cCode) || ($name == $cCode) || ($name == '---'))
		{
			// Black flag
			return '&#x1F3F4;&#x200D;&#x2620;&#xFE0F;';
		}

		$cCode = strtoupper($cCode);

		// Uppercase letter to Unicode Regional Indicator Symbol Letter
		$letterToRISL = [
			'A' => "&#x1F1E6;",
			'B' => "&#x1F1E7;",
			'C' => "&#x1F1E8;",
			'D' => "&#x1F1E9;",
			'E' => "&#x1F1EA;",
			'F' => "&#x1F1EB;",
			'G' => "&#x1F1EC;",
			'H' => "&#x1F1ED;",
			'I' => "&#x1F1EE;",
			'J' => "&#x1F1EF;",
			'K' => "&#x1F1F0;",
			'L' => "&#x1F1F1;",
			'M' => "&#x1F1F2;",
			'N' => "&#x1F1F3;",
			'O' => "&#x1F1F4;",
			'P' => "&#x1F1F5;",
			'Q' => "&#x1F1F6;",
			'R' => "&#x1F1F7;",
			'S' => "&#x1F1F8;",
			'T' => "&#x1F1F9;",
			'U' => "&#x1F1FA;",
			'V' => "&#x1F1FB;",
			'W' => "&#x1F1FC;",
			'X' => "&#x1F1FD;",
			'Y' => "&#x1F1FE;",
			'Z' => "&#x1F1FF;",
		];

		return $letterToRISL[substr($cCode, 0, 1)] . $letterToRISL[substr($cCode, 1, 1)];
	}

	/**
	 * Returns the title of the specified environment ID
	 *
	 * @param int   $id      Environment ID
	 * @param array $attribs Any HTML attributes for the IMG element
	 *
	 * @return  string  The title of the environment
	 */
	public static function environmentTitle(int $id, array $attribs = []): string
	{
		if (is_null(self::$environmentTitles))
		{
			/** @var Environments $environmentsModel */
			$environmentsModel = Container::getInstance('com_ars')->factory->model('Environments')->tmpInstance();
			// We use getItemsArray instead of get to fetch an associative array
			self::$environmentTitles = $environmentsModel
				->get(true)
				->transform(function(Environments $item) {
					return $item->title;
				});
		}

		if (!isset(self::$environmentTitles[$id]))
		{
			return '';
		}

		return self::$environmentTitles[$id];
	}

	/**
	 * Return an options list for all Environments
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function environments(): array
	{
		/** @var Environments $environmentsModel */
		$environmentsModel = Container::getInstance('com_ars')
			->factory->model('Environments')->tmpInstance();
		$options           = $environmentsModel
			->filter_order('title')
			->filter_order_Dir('ASC')
			->get(true)
			->transform(function (Environments $item) {
				return JHtml::_('FEFHelper.select.option', $item->id, $item->title);
			})->toArray();

		array_unshift($options, JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('LBL_ITEMS_ENVIRONMENT_SELECT') . ' -'));

		return $options;
	}

	/**
	 * Return a grouped options list for all releases (grouped by category) and ordered by category and version
	 * ascending.
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function releases(bool $addDefault = false): array
	{
		/** @var Releases $model */
		$model = Container::getInstance('com_ars')
			->factory->model('Releases')->tmpInstance();

		// We want all releases, but avoid the ones belonging to unpublished Bleeding Edge categories
		$options = [];
		$lastCat = null;
		$model
			->published(null)
			->nobeunpub(1)
			->filter_order('version')
			->filter_order_Dir('ASC')
			->get(true)
			// Convert to a simple list of keyed arrays containing category name, release ID and version.
			->transform(function (Releases $release) {
				return [
					'cat'     => $release->category->title,
					'id'      => $release->id,
					'version' => $release->version,
				];
				// Order by category and version
			})->sort(function (array $a, array $b) {
				$catCompare = $a['cat'] <=> $b['cat'];

				if ($catCompare !== 0)
				{
					return $catCompare;
				}

				return version_compare($a['version'], $b['version']);
			})
			->each(function (array $item) use (&$options, &$lastCat) {
				if ($item['cat'] !== $lastCat)
				{
					if ($lastCat !== null)
					{
						$options[] = JHtml::_('FEFHelper.select.option', '</OPTGROUP>');
					}

					$options[] = JHtml::_('FEFHelper.select.option', '<OPTGROUP>', $item['cat']);
					$lastCat   = $item['cat'];
				}

				$options[] = JHtml::_('FEFHelper.select.option', $item['id'], $item['version']);
			});

		if ($lastCat !== null)
		{
			$options[] = JHtml::_('FEFHelper.select.option', '</OPTGROUP>');
		}

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelper.select.option', 0, '- ' . Text::_('COM_ARS_COMMON_SELECT_RELEASE_LABEL') . ' -'));
		}

		return $options;
	}

	/**
	 * Return an options list for all categories
	 *
	 * @param bool $addDefault                     Add default select text?
	 * @param bool $excludeBleedingEdgeUnpublished Should I exclude unpublished Bleeding Edge categories? Default: true.
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function categories(bool $addDefault = false, bool $excludeBleedingEdgeUnpublished = true): array
	{
		/** @var Categories $categoriesModel */
		$categoriesModel = Container::getInstance('com_ars')
			->factory->model('Categories')->tmpInstance();

		$options = $categoriesModel
			->nobeunpub($excludeBleedingEdgeUnpublished ? 1 : 0)
			->filter_order('title')
			->filter_order_Dir('ASC')
			->get(true)
			->transform(function (Categories $item) {
				return JHtml::_('FEFHelper.select.option', $item->id, $item->title);
			})->toArray();

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('COM_ARS_COMMON_CATEGORY_SELECT_LABEL') . ' -'));
		}

		return $options;
	}

	/**
	 * Return an options list for all Joomla client IDs. Used to set up update streams.
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function client_id(): array
	{
		return [
			JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('LBL_RELEASES_CLIENT_ID') . ' -'),
			JHtml::_('FEFHelper.select.option', '1', Text::_('LBL_CLIENTID_BACKEND')),
			JHtml::_('FEFHelper.select.option', '0', Text::_('LBL_CLIENTID_FRONTEND')),
		];
	}

	/**
	 * Return an options list with Joomla update types
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function updateTypes(bool $addDefault = false): array
	{
		$options = [
			JHtml::_('FEFHelper.select.option', 'components', Text::_('LBL_UPDATETYPES_COMPONENTS')),
			JHtml::_('FEFHelper.select.option', 'libraries', Text::_('LBL_UPDATETYPES_LIBRARIES')),
			JHtml::_('FEFHelper.select.option', 'modules', Text::_('LBL_UPDATETYPES_MODULES')),
			JHtml::_('FEFHelper.select.option', 'packages', Text::_('LBL_UPDATETYPES_PACKAGES')),
			JHtml::_('FEFHelper.select.option', 'plugins', Text::_('LBL_UPDATETYPES_PLUGINS')),
			JHtml::_('FEFHelper.select.option', 'templates', Text::_('LBL_UPDATETYPES_TEMPLATES')),
			JHtml::_('FEFHelper.select.option', 'files', Text::_('LBL_UPDATETYPES_FILES')),
		];

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('LBL_UPDATES_TYPE') . ' -'));
		}

		return $options;
	}

	/**
	 * Returns an options list with all Update Streams
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function updateStreams(bool $addDefault = false): array
	{
		/** @var UpdateStreams $streamModel */
		$streamModel = Container::getInstance('com_ars')
			->factory->model('UpdateStreams')->tmpInstance();

		$options = $streamModel
			->filter_order('name')
			->filter_order_Dir('ASC')
			->get(true)
			->transform(function (UpdateStreams $item) {
				return JHtml::_('FEFHelper.select.option', $item->id, $item->name);
			})->toArray();

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('LBL_ITEMS_UPDATESTREAM_SELECT') . ' -'));
		}

		return $options;
	}

	public static function published(?string $selected = null, string $id = 'enabled', array $attribs = []): string
	{
		$options   = [];
		$options[] = JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('COM_ARS_LBL_COMMON_SELECTPUBLISHSTATE') . ' -');
		$options[] = JHtml::_('FEFHelper.select.option', 0, Text::_('JUNPUBLISHED'));
		$options[] = JHtml::_('FEFHelper.select.option', 1, Text::_('JPUBLISHED'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function languages(string $id, ?string $selected = null, array $attribs = [], string $client = 'site'): string
	{
		if ($client != 'site' && $client != 'administrator')
		{
			$client = 'site';
		}

		$languages = JLanguageHelper::createLanguageList($selected, constant('JPATH_' . strtoupper($client)), true, true);

		if (count($languages) > 1)
		{
			usort(
				$languages,
				function ($a, $b) {
					return strcmp($a['value'], $b['value']);
				}
			);
		}

		$options[] = JHtml::_('FEFHelper.select.option', '*', Text::_('JALL_LANGUAGE'));
		$options   = array_merge($options, $languages);

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function categoryType(string $id, ?string $selected = null, array $attribs = []): string
	{
		$options   = [];
		$options[] = JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('COM_ARS_LBL_COMMON_SELECTCATTYPE') . ' -');
		$options[] = JHtml::_('FEFHelper.select.option', 'normal', Text::_('COM_ARS_CATEGORIES_TYPE_NORMAL'));
		$options[] = JHtml::_('FEFHelper.select.option', 'bleedingedge', Text::_('COM_ARS_CATEGORIES_TYPE_BLEEDINGEDGE'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function itemType(string $id, ?string $selected = null, array $attribs = []): string
	{
		$options   = [];
		$options[] = JHtml::_('FEFHelper.select.option', '', '- ' . Text::_('LBL_ITEMS_TYPE_SELECT') . ' -');
		$options[] = JHtml::_('FEFHelper.select.option', 'link', Text::_('LBL_ITEMS_TYPE_LINK'));
		$options[] = JHtml::_('FEFHelper.select.option', 'file', Text::_('LBL_ITEMS_TYPE_FILE'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function subscriptionGroups(string $id, $selected = null, array $attribs = []): string
	{
		$options[] = JHtml::_('FEFHelper.select.option', '', Text::_('COM_ARS_COMMON_SELECT_GENERIC'));
		$options   = array_merge($options, SubscriptionIntegration::getGroupsForSelect());

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function maturity(string $id, ?string $selected = null, array $attribs = []): string
	{
		$options[] = JHtml::_('FEFHelper.select.option', '', Text::_('COM_ARS_RELEASES_MATURITY_SELECT'));
		$options[] = JHtml::_('FEFHelper.select.option', 'alpha', Text::_('COM_ARS_RELEASES_MATURITY_ALPHA'));
		$options[] = JHtml::_('FEFHelper.select.option', 'beta', Text::_('COM_ARS_RELEASES_MATURITY_BETA'));
		$options[] = JHtml::_('FEFHelper.select.option', 'rc', Text::_('COM_ARS_RELEASES_MATURITY_RC'));
		$options[] = JHtml::_('FEFHelper.select.option', 'stable', Text::_('COM_ARS_RELEASES_MATURITY_STABLE'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function imageList(string $id, ?string $selected, string $path, array $attribs = []): string
	{
		$options  = [];
		$filter   = '\.png$|\.gif$|\.jpg$|\.bmp$|\.ico$|\.jpeg$|\.psd$|\.eps$';
		$exclude  = false;
		$stripExt = false;

		if (!is_dir($path))
		{
			$path = JPATH_ROOT . '/' . $path;
		}

		$path = JPath::clean($path);

		// Prepend some default options based on field attributes.
		if (isset($attribs['hideNone']))
		{
			unset($attribs['hideNone']);
		}
		else
		{
			$options[] = JHtml::_('FEFHelper.select.option', '-1', Text::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id)));
		}

		if (isset($attribs['hideDefault']))
		{
			unset($attribs['hideDefault']);
		}
		else
		{
			$options[] = JHtml::_('FEFHelper.select.option', '', Text::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id)));
		}

		if (isset($attribs['filter']))
		{
			$filter = $attribs['filter'];
			unset($attribs['filter']);
		}

		if (isset($attribs['exclude']))
		{
			$exclude = true;
			unset($attribs['exclude']);
		}

		if (isset($attribs['stripExt']))
		{
			$stripExt = true;
			unset($attribs['stripExt']);
		}

		// Get a list of files in the search path with the given filter.
		$files = JFolder::files($path, $filter);

		// Build the options list from the list of files.
		if (is_array($files))
		{
			foreach ($files as $file)
			{
				// Check to see if the file is in the exclude mask.
				if ($exclude)
				{
					if (preg_match(chr(1) . $exclude . chr(1), $file))
					{
						continue;
					}
				}

				// If the extension is to be stripped, do it.
				if ($stripExt)
				{
					$file = JFile::stripExt($file);
				}

				$options[] = JHtml::_('FEFHelper.select.option', $file, $file);
			}
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Static function to get a select list of all access levels. We have to copy Joomla code since it will force the
	 * usage of Bootstrap classes, instead of using our FEFHelper to create the options.
	 *
	 * @param       $id
	 * @param null  $selected
	 * @param array $attribs
	 *
	 * @return string
	 */
	public static function accessLevel(string $id, ?string $selected = null, array $attribs = []): string
	{
		$container = static::getContainer();

		$db    = $container->db;
		$query = $db->getQuery(true)
			->select($db->qn('a.id', 'value') . ', ' . $db->qn('a.title', 'text'))
			->from($db->qn('#__viewlevels', 'a'))
			->group($db->qn(['a.id', 'a.title', 'a.ordering']))
			->order($db->qn('a.ordering') . ' ASC')
			->order($db->qn('title') . ' ASC');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList();

		array_unshift($options, JHtml::_('FEFHelper.select.option', '', Text::_('COM_ARS_COMMON_SHOW_ALL_LEVELS')));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
}

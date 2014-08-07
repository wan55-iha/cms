<?php
/**
 * Licensed under The GPL-3.0 License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @since	 2.0.0
 * @author	 Christopher Castro <chris@quickapps.es>
 * @link	 http://www.quickappscms.org
 * @license	 http://opensource.org/licenses/gpl-3.0.html GPL-3.0 License
 */
namespace Block\View\Helper;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use QuickApps\View\Helper\AppHelper;
use QuickApps\Utility\DetectorTrait;

/**
 * Block Helper.
 *
 * For handling block rendering.
 */
class BlockHelper extends AppHelper {

/**
 * Render all blocks for a particular region.
 *
 * @param string $region Region alias to render
 * @return string HTML blocks
 */
	public function region($region) {
		$this->alter('BlockHelper.region', $region);
		$html = '';
		$blocks = $this->blocksIn($region);

		foreach ($blocks as $block) {
			$html .= $this->render($block);
		}

		return $html;
	}

/**
 * Returns a list of block entities within the given region.
 *
 * @param string $region
 * @param boolean $all True will return the whole list, that is including blocks that will never
 * be rendered because of its visibility, language or role settings. Set to false (by default) will return
 * only blocks that we are sure will be rendered.
 * @return \Cake\Collection\Iterator\FilterIterator
 */
	public function blocksIn($region, $all = false) {
		$Blocks = TableRegistry::get('Block.Blocks');
		$cacheKey = "blocksIn_{$this->_View->theme}_{$region}_{$all}";
		$blocks = $this->_cache($cacheKey);

		if ($blocks === null) {
			$blocks = $Blocks->find()
				->contain(['Roles'])
				->matching('BlockRegions', function($q) use ($region) {
					return $q->where([
						'BlockRegions.theme' => $this->_View->theme,
						'BlockRegions.region' => $region,
					]);
				})
				->where(['Blocks.status' => 1])
				->all()
				->filter(function ($block) {
					// we have to remove all blocks that belongs to a disabled plugin
					if ($block->handler === 'Block') {
						return true;
					}
					foreach ($this->_listeners() as $listener) {
						if (str_starts_with($listener, "Block.{$block->handler}")) {
							return true;
						}
					}
					return false;
				});

			if (!$all) {
				$blocks
					->filter(function ($block) {
						// we do a second pass to remove blocks that will never be rendered
						return $this->allowed($block);
					});
			}

			$blocks
				->sortBy(function ($block) {
					return $block->block_regions->ordering;
				}, SORT_ASC);
			$this->_cache($cacheKey, $blocks);
		}

		return $blocks;
	}

/**
 * Renders a single block.
 *
 * @param \Block\Model\Entity\Block $block Block entity to render
 * @param array $options Array of options
 * @return string HTML
 */
	public function render($block, $options = []) {
		$this->alter('BlockHelper.render', $block, $options);
		$html = '';
		if ($this->allowed($block)) {
			$html = $this->invoke("Block.{$block->handler}.display", $this, $block, $options)->result;
		}
		return $html;
	}

/**
 * Checks if the given block can be rendered.
 *
 * @param array $block Block structure
 * @return boolean
 */
	public function allowed($block) {
		$this->alter('BlockHelper.allowed', $block);
		$cacheKey = "allowed_{$block->id}";
		$cache = static::_cache($cacheKey);

		if ($cache !== null) {
			return $cache;
		}

		if (
			!empty($block->locale) &&
			!in_array(Configure::read('Config.language'), (array)$block->locale)
		) {
			return static::_cache($cacheKey, false);
		}

		if ($block->has('roles') && !empty($block->roles)) {
			$rolesIds = [];
			$userRoles = userRoles();
			$allowed = false;
			foreach ($block->roles as $role) {
				$rolesIds[] = $role->id;
			}
			foreach ($userRoles as $role) {
				if (in_array($role, $rolesIds)) {
					$allowed = true;
					break;
				}
			}
			if (!$allowed) {
				return static::_cache($cacheKey, false);
			}
		}

		switch ($block->visibility) {
			case 'except':
				// Show on all pages except listed pages
				$allowed = !$this->_urlMatch($block->pages);
			break;
			case 'only':
				// Show only on listed pages
				$allowed = $this->_urlMatch($block->pages);
			break;
			case 'php':
				// Use custom PHP code to determine visibility
				//@codingStandardsIgnoreStart
				$allowed = @eval($block->pages);
				//@codingStandardsIgnoreEnd
			break;
		}

		if (!$allowed) {
			return static::_cache($cacheKey, false);
		}

		return static::_cache($cacheKey, true);
	}

/**
 * Returns all eventKeys that starts with `Block.`
 * 
 * @return array
 */
	protected function _listeners() {
		$cackeKey = '_listeners';
		$cache = static::_cache($cacheKey);

		if (!$cache) {
			$cache = [];
			foreach (listeners() as $listener) {
				if (str_starts_with($listener, 'Block.')) {
					$cache[] = $listener;
				}
			}
			static::_cache($cacheKey, $cache);
		}

		return $cache;
	}

/**
 * Check if a path matches any pattern in a set of patterns.
 *
 * @param string $patterns String containing a set of patterns separated by \n, \r or \r\n
 * @return boolean TRUE if the path matches a pattern, FALSE otherwise
 */
	protected function _urlMatch($patterns) {
		if (empty($patterns)) {
			return false;
		}

		$request = Router::getRequest();
		$path = str_starts_with($request->url, '/') ? str_replace_once('/', '', $request->url) : $request->url;

		if (option('url_locale_prefix')) {
			$patterns = explode("\n", $patterns);
			$locales = array_keys(Configure::read('QuickApps.languages'));
			$localesPattern = '(' . implode('|', array_map('preg_quote', $locales)) . ')';

			foreach ($patterns as &$p) {
				if (!preg_match("/^{$localesPattern}\//", $p)) {
					$p = Configure::read('Config.language') . '/' . $p;
					$p = str_replace('//', '/', $p);
				}
			}

			$patterns = implode("\n", $patterns);
		}

		// Convert path settings to a regular expression.
		// Therefore replace newlines with a logical or, /* with asterisks and  "/" with the frontpage.
		$to_replace = [
			'/(\r\n?|\n)/', // newlines
			'/\\\\\*/',	 // asterisks
			'/(^|\|)\/($|\|)/' // front '/'
		];

		$replacements = [
			'|',
			'.*',
			'\1' . preg_quote(Router::url('/'), '/') . '\2'
		];

		$patterns_quoted = preg_quote($patterns, '/');
		$regexps[$patterns] = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')$/';
		return (bool)preg_match($regexps[$patterns], $path);
	}

}

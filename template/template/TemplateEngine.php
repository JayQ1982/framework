<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\template;

use ArrayObject;
use Exception;
use framework\common\StringUtils;
use framework\template\htmlparser\CDataSectionNode;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\HtmlDoc;
use framework\template\htmlparser\TextNode;
use ReflectionProperty;
use Throwable;

class TemplateEngine
{
	protected ?HtmlDoc $htmlDoc = null;
	protected string $tplNsPrefix;
	protected ArrayObject $dataPool;
	protected ArrayObject $dataTable;
	protected array $customTags = [];
	protected ?TemplateCacheEntry $cached = null;
	protected TemplateCacheStrategy $templateCacheInterface;
	protected string $currentTemplateFile = '';
	protected ?TemplateTag $lastTplTag = null;
	protected array $getterMethodPrefixes = ['get', 'is', 'has'];

	/**
	 * @param TemplateCacheStrategy $tplCacheInterface The template cache object
	 * @param string                $tplNsPrefix       The prefix for custom tags in the template file
	 * @param array                 $customTags        Additional custom tags to be loaded
	 */
	public function __construct(TemplateCacheStrategy $tplCacheInterface, string $tplNsPrefix, array $customTags = [])
	{
		$this->templateCacheInterface = $tplCacheInterface;
		$this->tplNsPrefix = $tplNsPrefix;
		$this->customTags = array_merge(TemplateEngine::getDefaultCustomTags(), $customTags);

		$this->dataPool = new ArrayObject();
		$this->dataTable = new ArrayObject();
	}

	protected static function getDefaultCustomTags(): array
	{
		return [
			'checkboxOptions' => 'framework\template\customtags\CheckboxOptionsTag',
			'checkbox'        => 'framework\template\customtags\CheckboxTag',
			'date'            => 'framework\template\customtags\DateTag',
			'elseif'          => 'framework\template\customtags\ElseifTag',
			'else'            => 'framework\template\customtags\ElseTag',
			'for2'            => 'framework\template\customtags\For2Tag',
			'forgroup'        => 'framework\template\customtags\ForgroupTag',
			'formComponent'   => 'framework\template\customtags\FormComponentTag',
			'for'             => 'framework\template\customtags\ForTag',
			'if'              => 'framework\template\customtags\IfTag',
			'lang'            => 'framework\template\customtags\LangTag',
			'loadSubTpl'      => 'framework\template\customtags\LoadSubTplTag',
			'options'         => 'framework\template\customtags\OptionsTag',
			'option'          => 'framework\template\customtags\OptionTag',
			'radioOptions'    => 'framework\template\customtags\RadioOptionsTag',
			'radio'           => 'framework\template\customtags\RadioTag',
			'subSite'         => 'framework\template\customtags\SubSiteTag',
			'text'            => 'framework\template\customtags\TextTag',
			'print'           => 'framework\template\customtags\PrintTag',
			'formAddRemove'   => 'framework\template\customtags\FormAddRemoveTag',
		];
	}

	protected function load(): void
	{
		$this->lastTplTag = null;
		$this->htmlDoc->parse();

		$nodeList = $this->htmlDoc->getNodeTree()->childNodes;

		if (count($nodeList) === 0) {
			throw new Exception('Invalid template-file: ' . $this->currentTemplateFile);
		}

		try {
			$this->copyNodes($nodeList);
		} catch (Throwable $e) {
			throw new Exception('Error while processing the template file ' . $this->currentTemplateFile . ': ' . $e->getMessage());
		}
	}

	/**
	 * @param array $nodeList
	 *
	 * @throws Exception
	 */
	protected function copyNodes(array $nodeList)
	{
		foreach ($nodeList as $node) {
			// Parse inline tags if activated
			if ($node instanceof ElementNode === true) {
				foreach ($node->getAttributes() as $name => $htmlTagAttribute) {
					$htmlTagAttribute->setValue($this->replaceInlineTag($htmlTagAttribute->getValue()));
					$node->updateAttribute($name, $htmlTagAttribute);
				}
			} else {
				if ($node instanceof TextNode || /*$node instanceof CommentNode ||*/
					$node instanceof CDataSectionNode) {
					$node->content = $this->replaceInlineTag($node->content);
				}

				continue;
			}

			if (count($node->childNodes) > 0) {
				$this->copyNodes($node->childNodes);
			}

			if ($node->namespace !== $this->tplNsPrefix) {
				continue;
			}

			if (isset($this->customTags[$node->tagName]) === false) {
				throw new Exception('The custom tag "' . $node->tagName . '" is not registered in this template engine instance');
			}

			$tagClassName = $this->customTags[$node->tagName];

			if (class_exists($tagClassName) === false) {
				throw new Exception('The Tag "' . $tagClassName . '" does not exist');
			}

			$tagInstance = new $tagClassName;
			if (($tagInstance instanceof TemplateTag) === false) {
				$this->templateCacheInterface->setSaveOnDestruct(false);
				throw new Exception('The class "' . $tagClassName . '" does not extend the abstract class "TemplateTag" and is so recognized as an illegal class for a custom tag."');
			}

			try {
				$tagInstance->replaceNode($this, $node);
			} catch (Throwable $e) {
				$this->templateCacheInterface->setSaveOnDestruct(false);
				throw $e;
			}

			$this->lastTplTag = $tagInstance;
		}
	}

	protected function replaceInlineTag(string $value): string
	{
		$inlineTags = null;

		preg_match_all('@{' . $this->tplNsPrefix . ':(.+?)(?:\\s+(\\w+=\'.+?\'))?\\s*}@', $value, $inlineTags, PREG_SET_ORDER);

		if (count($inlineTags) <= 0) {
			return $value;
		}

		for ($j = 0; $j < count($inlineTags); $j++) {
			$tagName = $inlineTags[$j][1];

			if (isset($this->customTags[$tagName]) === false) {
				throw new Exception('The custom tag "' . $tagName . '" is not registered in this template engine instance');
			}

			$tagClassName = $this->customTags[$tagName];

			/** @var TagInline $tagInstance */
			$tagInstance = new $tagClassName;

			if ($tagInstance instanceof TemplateTag === false) {
				$this->templateCacheInterface->setSaveOnDestruct(false);
				throw new Exception('The class "' . $tagClassName . '" does not extend the abstract class "TemplateTag" and is so not recognized as an illegal class for a custom tag."');
			}

			if ($tagInstance instanceof TagInline === false) {
				throw new Exception('CustomTag "' . $tagClassName . '" is not allowed to use inline.');
			}

			// Params
			$params = $parsedParams = [];

			if (array_key_exists(2, $inlineTags[$j])) {
				preg_match_all('@(\w+)=\'(.+?)\'@', $inlineTags[$j][2], $parsedParams, PREG_SET_ORDER);

				$countParams = count($parsedParams);
				for ($p = 0; $p < $countParams; $p++) {
					$params[$parsedParams[$p][1]] = $parsedParams[$p][2];
				}
			}

			try {
				$repl = $tagInstance->replaceInline($this, $params);
				$value = str_replace($inlineTags[$j][0], $repl, $value);
			} catch (Throwable $e) {
				$this->templateCacheInterface->setSaveOnDestruct(false);
				throw $e;
			}
		}

		return $value;
	}

	/**
	 * @param string $tplFile The path to the template file to parse
	 *
	 * @return TemplateCacheEntry
	 */
	public function parse(string $tplFile): TemplateCacheEntry
	{
		$this->cached = $this->getTemplateCacheEntry($tplFile);
		if ($this->cached !== null) {
			return $this->cached;
		}

		// PARSE IT NEW: No NodeList given? Okay! I'll load defaults for you
		return $this->cache($tplFile);
	}

	/**
	 * Returns cached template file
	 *
	 * @param string $filePath Path to the template file that should be checked
	 *
	 * @return ?TemplateCacheEntry
	 * @throws Exception
	 */
	private function getTemplateCacheEntry(string $filePath): ?TemplateCacheEntry
	{
		if (stream_resolve_include_path($filePath) === false) {
			throw new Exception('Could not find template file: ' . $filePath);
		}

		$tplCacheEntry = $this->templateCacheInterface->getCachedTplFile($filePath);

		if ($tplCacheEntry === null) {
			return null;
		}

		$changeTime = @filemtime($filePath);
		if ($changeTime === false) {
			$changeTime = @filectime($filePath);
		}

		if (($tplCacheEntry->getSize() >= 0 && $tplCacheEntry->getSize() !== @filesize($filePath)) || $tplCacheEntry->getChangeTime() < $changeTime) {
			return null;
		}

		return $tplCacheEntry;
	}

	/**
	 * Returns the final HTML-code or includes the cached file (if caching is enabled)
	 *
	 * @param string $tplFile
	 * @param array  $tplVars
	 *
	 * @return string
	 */
	public function getResultAsHtml(string $tplFile, array $tplVars = []): string
	{
		$this->currentTemplateFile = $tplFile;
		$this->dataPool = new ArrayObject($tplVars);

		$templateCacheEntry = $this->parse($tplFile);

		try {
			ob_start();

			require $this->templateCacheInterface->getCachePath() . $templateCacheEntry->getPath();

			return ob_get_clean();
		} catch (Throwable $e) {
			// Throw away the whole template code till now
			ob_clean();

			// Throw the Exception again
			throw $e;
		}
	}

	protected function cache($tplFile): TemplateCacheEntry
	{
		if (stream_resolve_include_path($tplFile) === false) {
			throw new Exception('Template file \'' . $tplFile . '\' does not exists');
		}

		$currentCacheEntry = $this->templateCacheInterface->getCachedTplFile($tplFile);

		// Render tpl
		$content = file_get_contents($tplFile);
		$this->htmlDoc = new HtmlDoc($content, $this->tplNsPrefix);

		foreach ($this->customTags as $customTag) {
			if (in_array('framework\template\template\TagNode', class_implements($customTag)) === false || $customTag::isSelfClosing() === false) {
				continue;
			}

			$this->htmlDoc->addSelfClosingTag($this->tplNsPrefix . ':' . $customTag::getName());
		}

		$this->load();

		$compiledTemplateContent = $this->htmlDoc->getHtml();
		$this->templateCacheInterface->setSaveOnDestruct(false);

		return $this->templateCacheInterface->addCachedTplFile($tplFile, $currentCacheEntry, $compiledTemplateContent);
	}

	public function getDomReader(): HtmlDoc
	{
		return $this->htmlDoc;
	}

	/**
	 * Checks if a template node is followed by another template tag with a specific tagName.
	 *
	 * @param ElementNode $elementNode The template tag
	 * @param array       $tagNames    Array with tagName(s) of the following template tag(s)
	 *
	 * @return bool
	 */
	public function isFollowedBy(ElementNode $elementNode, array $tagNames): bool
	{
		$nextSibling = $elementNode->getNextSibling();

		return !($nextSibling === null || $nextSibling->namespace !== $this->getTplNsPrefix() || in_array($nextSibling->tagName, $tagNames) === false);
	}

	/**
	 * Register a value to make it accessible for the engine
	 *
	 * @param string  $key
	 * @param mixed   $value
	 * @param boolean $overwrite
	 *
	 * @throws Exception
	 */
	public function addData(string $key, mixed $value, bool $overwrite = false)
	{
		if ($this->dataPool->offsetExists($key) === true && $overwrite === false) {
			throw new Exception("Data with the key '" . $key . "' is already registered");
		}

		$this->dataPool->offsetSet($key, $value);
	}

	public function unsetData($key)
	{
		if ($this->dataPool->offsetExists($key) === false) {
			return;
		}

		$this->dataPool->offsetUnset($key);
	}

	/**
	 * Returns a registered data entry with the given key
	 *
	 * @param string $key The key of the data element
	 *
	 * @return mixed The value for that key or the key itself
	 */
	public function getData(string $key): mixed
	{
		if ($this->dataPool->offsetExists($key) === false) {
			return null;
		}

		return $this->dataPool->offsetGet($key);
	}

	public function getDataFromSelector($selector)
	{
		return $this->getSelectorValue($selector);
	}

	public function setAllData($dataPool)
	{
		foreach ($dataPool as $key => $val) {
			$this->dataPool->offsetSet($key, $val);
		}
	}

	public function getAllData(): ArrayObject
	{
		return $this->dataPool;
	}

	public function getTplNsPrefix(): string
	{
		return $this->tplNsPrefix;
	}

	public function getTemplateCacheInterface(): TemplateCacheStrategy
	{
		return $this->templateCacheInterface;
	}

	/**
	 * Returns the latest template tag found by the engine
	 *
	 * @return ?TemplateTag
	 */
	public function getLastTplTag(): ?TemplateTag
	{
		return $this->lastTplTag;
	}

	/**
	 * @return string The template file path which gets parsed at the moment
	 */
	public function getCurrentTemplateFile(): string
	{
		return $this->currentTemplateFile;
	}

	/**
	 * @param ElementNode $contextTag
	 * @param array       $attributes
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function checkRequiredAttributes(ElementNode $contextTag, array $attributes): bool
	{
		foreach ($attributes as $attribute) {
			$val = $contextTag->getAttribute($attribute)->getValue();

			if (!is_null($val)) {
				continue;
			}

			throw new Exception('Could not parse the template: Missing attribute \'' . $attribute . '\' for custom tag \'' . $contextTag->tagName . '\' in ' . $this->currentTemplateFile . ' on line ' . $contextTag->line);
		}

		return true;
	}

	/**
	 * Register a new tag for the this TemplateEngine instance
	 *
	 * @param string $tagName  The name of the tag
	 * @param string $tagClass The class name of the tag
	 */
	public function registerTag(string $tagName, string $tagClass)
	{
		$this->customTags[$tagName] = $tagClass;
	}

	/**
	 * @param string $selectorStr
	 * @param bool   $returnNull
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function getSelectorValue(string $selectorStr, bool $returnNull = false): mixed
	{
		$selParts = explode('.', $selectorStr);
		$firstPart = array_shift($selParts);
		$currentSel = $firstPart;

		if ($this->dataPool->offsetExists($firstPart) === false) {
			if ($returnNull === false) {
				throw new Exception('The data with offset "' . $currentSel . '" does not exist for template file ' . $this->currentTemplateFile);
			}

			return null;
		}

		$varData = $this->dataPool->offsetGet($firstPart);

		foreach ($selParts as $part) {
			$nextSel = $currentSel . '.' . $part;

			if ($varData instanceof ArrayObject === true) {
				if ($varData->offsetExists($part) === false) {
					throw new Exception('Array key "' . $part . '" does not exist in ArrayObject "' . $currentSel . '"');
				}

				$varData = $varData->offsetGet($part);
			} else if (is_object($varData) === true) {
				$args = [];

				$argPos = strpos($part, '(');
				if ($argPos !== false) {
					$argStr = substr($part, $argPos + 1, -1);
					$part = substr($part, 0, $argPos);

					foreach (preg_split('/,/x', $argStr) as $no => $arg) {

						if (StringUtils::startsWith($argStr, '\'') === false || StringUtils::endsWith($argStr, '\'') === false) {
							$args[$no] = $this->getSelectorValue($argStr, $returnNull);
						} else {
							$args[$no] = substr($arg, 1, -1);
						}
					}
				}

				if (property_exists($varData, $part) === true) {
					$getProperty = new ReflectionProperty($varData, $part);

					if ($getProperty->isPublic() === true) {
						$varData = $varData->$part;
					} else {
						$getterMethodName = null;

						foreach ($this->getterMethodPrefixes as $mp) {
							$getterMethodName = $mp . ucfirst($part);

							if (method_exists($varData, $getterMethodName) === true) {
								break;
							}

							$getterMethodName = null;
						}

						if ($getterMethodName === null) {
							throw new Exception('Could not access protected/private property "' . $part . '". Please provide a getter method');
						}

						$varData = call_user_func([$varData, $getterMethodName]);
					}
				} else if (method_exists($varData, $part) === true) {
					$varData = call_user_func_array([$varData, $part], $args);
				} else {
					throw new Exception('Don\'t know how to handle selector part "' . $part . '"');
				}
			} else if (is_array($varData)) {
				$varData = (array)$varData;
				if (array_key_exists($part, $varData) === false) {
					throw new Exception('Array key "' . $part . '" does not exist in array "' . $currentSel . '"');
				}

				$varData = $varData[$part];
			} else {
				throw new Exception('The data with offset "' . $currentSel . '" is not an object nor an array.');
			}

			$currentSel = $nextSel;
			$this->dataTable->offsetSet($currentSel, $varData);
		}

		return $varData;
	}
}
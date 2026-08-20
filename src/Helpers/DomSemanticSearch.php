<?php

namespace TorneLIB\Helpers;

/**
 * Generic semantic discovery helpers for parsed DOM models.
 *
 * The class deliberately knows nothing about RSS database fields. It exposes
 * reusable primitives for finding elements whose tag/attributes look like a
 * requested concept, inventorying a document, and locating repeated structures
 * that callers such as ToolsAPI can rank as feed rows.
 *
 * @since 6.1.11
 */
class DomSemanticSearch
{
    /** @var string[] */
    private static $semanticAttributes = [
        'id', 'class', 'name', 'itemprop', 'property', 'rel', 'role', 'aria-label', 'itemtype',
    ];

    /**
     * Find elements by semantic names derived from tag names and common
     * descriptive attributes such as class, id, itemprop, role and rel.
     *
     * Examples:
     * - headline/title/heading matches h1-h6 and elements with matching classes
     * - link/url matches anchors and elements whose attributes contain those names
     * - date/published/time matches <time> and date-like semantic attributes
     *
     * @param DomDocumentModel|DomNodeModel $context
     * @param string[] $names
     * @param int $limit
     * @return DomNodeCollection
     */
    public static function findBySemanticNames($context, array $names, $limit = 100)
    {
        $needles = self::normalizeNeedles($names);
        if (!count($needles)) {
            return new DomNodeCollection();
        }

        $root = self::resolveRoot($context);
        $matches = [];
        self::walk($root, function (DomNodeModel $node) use (&$matches, $needles, $limit) {
            if (count($matches) >= $limit) {
                return false;
            }

            $tokens = self::getSemanticTokens($node);
            if (count(array_intersect($needles, $tokens))) {
                $matches[] = $node;
            }

            return true;
        });

        return new DomNodeCollection($matches);
    }

    /**
     * Return a count-based inventory useful for interactive DOM exploration.
     *
     * @param DomDocumentModel $document
     * @return array
     */
    public static function getElementInventory(DomDocumentModel $document)
    {
        $inventory = [
            'tags' => [],
            'classes' => [],
            'ids' => [],
            'names' => [],
            'itemprops' => [],
            'properties' => [],
            'roles' => [],
            'rels' => [],
            'semantic_names' => [],
        ];

        self::walk($document->getRoot(), function (DomNodeModel $node) use (&$inventory) {
            self::increment($inventory['tags'], strtolower((string)$node->getName()));

            $attributes = $node->getAttributes();
            self::incrementAttributeTokens($inventory['classes'], isset($attributes['class']) ? $attributes['class'] : null);
            self::increment($inventory['ids'], isset($attributes['id']) ? trim((string)$attributes['id']) : '');
            self::incrementAttributeTokens($inventory['names'], isset($attributes['name']) ? $attributes['name'] : null);
            self::incrementAttributeTokens($inventory['itemprops'], isset($attributes['itemprop']) ? $attributes['itemprop'] : null);
            self::incrementAttributeTokens($inventory['properties'], isset($attributes['property']) ? $attributes['property'] : null);
            self::incrementAttributeTokens($inventory['roles'], isset($attributes['role']) ? $attributes['role'] : null);
            self::incrementAttributeTokens($inventory['rels'], isset($attributes['rel']) ? $attributes['rel'] : null);

            foreach (self::getSemanticTokens($node) as $token) {
                self::increment($inventory['semantic_names'], $token);
            }

            return true;
        });

        foreach ($inventory as $key => $values) {
            arsort($values);
            $inventory[$key] = $values;
        }

        return $inventory;
    }

    /**
     * Discover repeated DOM structures that may represent rows/cards/items.
     *
     * Returned candidates contain a namespace-safe XPath, count, generic score,
     * semantic names and one text/attribute sample. The score is intentionally
     * generic; callers can add domain-specific ranking on top.
     *
     * @param DomDocumentModel $document
     * @param int $minimumOccurrences
     * @param int $limit
     * @return array
     */
    public static function discoverRepeatedStructures(
        DomDocumentModel $document,
        $minimumOccurrences = 2,
        $limit = 25
    ) {
        $minimumOccurrences = max(2, (int)$minimumOccurrences);
        $limit = max(1, (int)$limit);
        $groups = [];

        self::walk($document->getRoot(), function (DomNodeModel $node) use (&$groups) {
            $signature = self::getStructureSignature($node);
            if ($signature === null) {
                return true;
            }

            if (!isset($groups[$signature['key']])) {
                $groups[$signature['key']] = [
                    'signature' => $signature,
                    'nodes' => [],
                ];
            }
            $groups[$signature['key']]['nodes'][] = $node;

            return true;
        });

        $candidates = [];
        foreach ($groups as $group) {
            $count = count($group['nodes']);
            if ($count < $minimumOccurrences) {
                continue;
            }

            /** @var DomNodeModel $sample */
            $sample = $group['nodes'][0];
            $signature = $group['signature'];
            $semanticNames = self::getSemanticTokens($sample);
            $candidates[] = [
                'xpath' => self::buildStructureXPath($signature),
                'count' => $count,
                'score' => self::scoreStructure($sample, $count, $semanticNames),
                'tag' => $signature['tag'],
                'classes' => $signature['classes'],
                'role' => $signature['role'],
                'itemprop' => $signature['itemprop'],
                'itemtype' => $signature['itemtype'],
                'semantic_names' => $semanticNames,
                'sample' => [
                    'attributes' => $sample->getAttributes(),
                    'text' => self::preview(self::getTextContent($sample), 240),
                ],
            ];
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['count'] <=> $a['count'];
            }
            return $b['score'] <=> $a['score'];
        });

        return array_slice($candidates, 0, $limit);
    }

    /**
     * Return normalized visible text from a node and all descendants.
     *
     * @param DomNodeModel $node
     * @return string
     */
    public static function getTextContent(DomNodeModel $node)
    {
        $parts = [];

        try {
            $textNodes = $node->query('.//text()');
            foreach ($textNodes as $textNode) {
                $value = trim((string)$textNode->getValue());
                if ($value !== '') {
                    $parts[] = $value;
                }
            }
        } catch (\Throwable $e) {
            self::collectTextFallback($node, $parts);
        }

        $text = trim(implode(' ', $parts));
        $text = preg_replace('/\s+/u', ' ', $text);

        return $text === null ? '' : trim($text);
    }

    /**
     * Expose semantic tokens for debugging/discovery UIs.
     *
     * @param DomNodeModel $node
     * @return string[]
     */
    public static function getSemanticTokens(DomNodeModel $node)
    {
        $tokens = [];
        $name = strtolower((string)$node->getName());
        self::addTokens($tokens, $name);

        if (strpos($name, ':') !== false) {
            $parts = explode(':', $name);
            self::addTokens($tokens, end($parts));
        }

        foreach (self::tagAliases($name) as $alias) {
            self::addTokens($tokens, $alias);
        }

        foreach ($node->getAttributes() as $attributeName => $attributeValue) {
            $attributeName = strtolower((string)$attributeName);
            $isSemantic = in_array($attributeName, self::$semanticAttributes, true)
                || strpos($attributeName, 'data-') === 0;
            if (!$isSemantic) {
                continue;
            }

            self::addTokens($tokens, $attributeName);
            self::addTokens($tokens, (string)$attributeValue);
        }

        $tokens = array_values(array_unique(array_filter($tokens, function ($token) {
            return $token !== '';
        })));

        return $tokens;
    }

    /**
     * @param DomDocumentModel|DomNodeModel $context
     * @return DomNodeModel
     */
    private static function resolveRoot($context)
    {
        if ($context instanceof DomDocumentModel) {
            return $context->getRoot();
        }
        if ($context instanceof DomNodeModel) {
            return $context;
        }

        throw new \InvalidArgumentException('Semantic DOM search requires DomDocumentModel or DomNodeModel context.');
    }

    /**
     * Depth-first traversal. Returning false from callback stops traversal.
     *
     * @param DomNodeModel $node
     * @param callable $callback
     * @return bool
     */
    private static function walk(DomNodeModel $node, callable $callback)
    {
        if ($callback($node) === false) {
            return false;
        }

        foreach ($node->children() as $child) {
            if (self::walk($child, $callback) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $names
     * @return string[]
     */
    private static function normalizeNeedles(array $names)
    {
        $tokens = [];
        foreach ($names as $name) {
            self::addTokens($tokens, (string)$name);
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @param string[] $tokens
     * @param string $value
     */
    private static function addTokens(array &$tokens, $value)
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return;
        }

        $tokens[] = $value;
        $parts = preg_split('/[^\pL\pN]+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($parts)) {
            foreach ($parts as $part) {
                $part = strtolower(trim((string)$part));
                if ($part !== '') {
                    $tokens[] = $part;
                }
            }
        }
    }

    /**
     * @param string $tag
     * @return string[]
     */
    private static function tagAliases($tag)
    {
        $local = $tag;
        if (strpos($local, ':') !== false) {
            $parts = explode(':', $local);
            $local = end($parts);
        }

        if (preg_match('/^h[1-6]$/', $local)) {
            return ['heading', 'headline', 'title'];
        }

        $map = [
            'a' => ['link', 'url'],
            'article' => ['article', 'content', 'entry', 'item', 'row'],
            'entry' => ['entry', 'item', 'row'],
            'item' => ['entry', 'item', 'row'],
            'li' => ['item', 'row'],
            'tr' => ['item', 'row'],
            'p' => ['description', 'paragraph', 'summary', 'text'],
            'time' => ['date', 'published', 'time'],
            'title' => ['headline', 'title'],
            'description' => ['description', 'summary'],
            'summary' => ['description', 'summary'],
            'link' => ['link', 'url'],
            'guid' => ['guid', 'id', 'identifier'],
            'updated' => ['date', 'published', 'time', 'updated'],
            'published' => ['date', 'published', 'time'],
            'pubdate' => ['date', 'published', 'time'],
            'img' => ['image', 'picture'],
            'image' => ['image', 'picture'],
            'main' => ['content', 'main'],
        ];

        return isset($map[$local]) ? $map[$local] : [];
    }

    /**
     * @param DomNodeModel $node
     * @return array|null
     */
    private static function getStructureSignature(DomNodeModel $node)
    {
        $tag = strtolower((string)$node->getName());
        if (in_array($tag, ['html', 'head', 'body', 'script', 'style', 'meta', 'link', 'br', 'hr', '#text'], true)) {
            return null;
        }

        $attributes = $node->getAttributes();
        $classes = self::attributeTokens(isset($attributes['class']) ? $attributes['class'] : null);
        sort($classes);
        $role = isset($attributes['role']) ? trim((string)$attributes['role']) : '';
        $itemprop = isset($attributes['itemprop']) ? trim((string)$attributes['itemprop']) : '';
        $itemtype = isset($attributes['itemtype']) ? trim((string)$attributes['itemtype']) : '';

        $intrinsicRows = ['article', 'li', 'tr', 'item', 'entry'];
        if (!count($classes) && $role === '' && $itemprop === '' && $itemtype === '' && !in_array($tag, $intrinsicRows, true)) {
            return null;
        }

        // Avoid signatures becoming too specific when CSS utility classes are numerous.
        $classes = array_slice($classes, 0, 4);
        $key = implode('|', [$tag, implode('.', $classes), $role, $itemprop, $itemtype]);

        return [
            'key' => $key,
            'tag' => $tag,
            'classes' => $classes,
            'role' => $role,
            'itemprop' => $itemprop,
            'itemtype' => $itemtype,
        ];
    }

    /**
     * @param array $signature
     * @return string
     */
    private static function buildStructureXPath(array $signature)
    {
        $conditions = [
            sprintf('local-name()=%s', self::xpathLiteral($signature['tag'])),
        ];

        foreach ($signature['classes'] as $class) {
            $conditions[] = sprintf(
                "contains(concat(' ', normalize-space(@class), ' '), %s)",
                self::xpathLiteral(' ' . $class . ' ')
            );
        }
        if ($signature['role'] !== '') {
            $conditions[] = '@role=' . self::xpathLiteral($signature['role']);
        }
        if ($signature['itemprop'] !== '') {
            $conditions[] = '@itemprop=' . self::xpathLiteral($signature['itemprop']);
        }
        if ($signature['itemtype'] !== '') {
            $conditions[] = '@itemtype=' . self::xpathLiteral($signature['itemtype']);
        }

        return '//*[' . implode(' and ', $conditions) . ']';
    }

    /**
     * @param DomNodeModel $sample
     * @param int $count
     * @param string[] $semanticNames
     * @return int
     */
    private static function scoreStructure(DomNodeModel $sample, $count, array $semanticNames)
    {
        $score = min(20, (int)$count * 2);
        $tag = strtolower((string)$sample->getName());
        $tagScores = [
            'article' => 10,
            'item' => 10,
            'entry' => 10,
            'li' => 5,
            'tr' => 5,
            'a' => 3,
            'section' => 2,
            'div' => 1,
        ];
        if (isset($tagScores[$tag])) {
            $score += $tagScores[$tag];
        }

        $positive = ['article', 'entry', 'item', 'row', 'result', 'card', 'post', 'news', 'release', 'product', 'search'];
        $negative = ['nav', 'menu', 'footer', 'header', 'sidebar', 'cookie', 'advert', 'advertisement', 'banner', 'pagination'];
        $score += count(array_intersect($positive, $semanticNames)) * 3;
        $score -= count(array_intersect($negative, $semanticNames)) * 8;

        if (self::containsSemanticDescendant($sample, ['title', 'headline', 'heading'])) {
            $score += 4;
        }
        if (self::containsSemanticDescendant($sample, ['link', 'url'])) {
            $score += 4;
        }
        if (self::containsSemanticDescendant($sample, ['description', 'summary', 'text'])) {
            $score += 2;
        }
        if (self::containsSemanticDescendant($sample, ['date', 'published', 'time'])) {
            $score += 1;
        }

        return $score;
    }

    /**
     * @param DomNodeModel $node
     * @param string[] $names
     * @return bool
     */
    private static function containsSemanticDescendant(DomNodeModel $node, array $names)
    {
        $needles = self::normalizeNeedles($names);
        foreach ($node->children() as $child) {
            if (count(array_intersect($needles, self::getSemanticTokens($child)))) {
                return true;
            }
            if (self::containsSemanticDescendant($child, $names)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $target
     * @param string|null $value
     */
    private static function incrementAttributeTokens(array &$target, $value)
    {
        foreach (self::attributeTokens($value) as $token) {
            self::increment($target, $token);
        }
    }

    /**
     * @param string|null $value
     * @return string[]
     */
    private static function attributeTokens($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($tokens)) {
            return [];
        }

        return array_values(array_unique(array_map('strtolower', $tokens)));
    }

    /**
     * @param array $target
     * @param string $value
     */
    private static function increment(array &$target, $value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return;
        }
        if (!isset($target[$value])) {
            $target[$value] = 0;
        }
        $target[$value]++;
    }

    /**
     * @param DomNodeModel $node
     * @param array $parts
     */
    private static function collectTextFallback(DomNodeModel $node, array &$parts)
    {
        $value = trim((string)$node->getValue());
        if ($value !== '') {
            $parts[] = $value;
        }
        foreach ($node->children() as $child) {
            self::collectTextFallback($child, $parts);
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private static function xpathLiteral($value)
    {
        $value = (string)$value;
        if (strpos($value, "'") === false) {
            return "'" . $value . "'";
        }
        if (strpos($value, '"') === false) {
            return '"' . $value . '"';
        }

        $parts = explode("'", $value);
        $quoted = [];
        foreach ($parts as $index => $part) {
            if ($part !== '') {
                $quoted[] = "'" . $part . "'";
            }
            if ($index < count($parts) - 1) {
                $quoted[] = '"\'"';
            }
        }

        return 'concat(' . implode(', ', $quoted) . ')';
    }

    /**
     * @param string $text
     * @param int $length
     * @return string
     */
    private static function preview($text, $length)
    {
        if (function_exists('mb_substr')) {
            return mb_substr((string)$text, 0, (int)$length);
        }

        return substr((string)$text, 0, (int)$length);
    }
}

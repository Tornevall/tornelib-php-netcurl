<?php

namespace TorneLIB\Helpers;

/**
 * Generic semantic discovery helpers for parsed DOM models.
 *
 * The class deliberately knows nothing about RSS database fields. It exposes
 * reusable primitives for semantic element search, document inventory and
 * repeated-structure discovery that applications can rank for their own use.
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
            if (count($matches) >= (int)$limit) {
                return false;
            }
            if (count(array_intersect($needles, self::getSemanticTokens($node)))) {
                $matches[] = $node;
            }
            return true;
        });

        return new DomNodeCollection($matches);
    }

    /**
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
     * @return array
     */
    public static function discoverRepeatedStructures(
        DomDocumentModel $document,
        $minimumOccurrences = 2,
        $limit = 25
    ) {
        $minimumOccurrences = max(2, (int)$minimumOccurrences);
        $groups = [];

        self::walk($document->getRoot(), function (DomNodeModel $node) use (&$groups) {
            foreach (self::getStructureSignatures($node) as $signature) {
                if (!isset($groups[$signature['key']])) {
                    $groups[$signature['key']] = ['signature' => $signature, 'nodes' => []];
                }
                $groups[$signature['key']]['nodes'][] = $node;
            }
            return true;
        });

        // Key by XPath so a stable loose class signature can replace an exact
        // signature that accidentally split the same row family on optional
        // modifier classes (for example "card" vs "card featured").
        $candidatesByXpath = [];
        foreach ($groups as $group) {
            $count = count($group['nodes']);
            if ($count < $minimumOccurrences) {
                continue;
            }

            /** @var DomNodeModel $sample */
            $sample = $group['nodes'][0];
            $signature = $group['signature'];
            $semanticNames = self::getSemanticTokens($sample);
            $xpath = self::buildStructureXPath($signature);
            $candidate = [
                'xpath' => $xpath,
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

            if (!isset($candidatesByXpath[$xpath]) || $count > $candidatesByXpath[$xpath]['count']) {
                $candidatesByXpath[$xpath] = $candidate;
            }
        }

        $candidates = array_values($candidatesByXpath);
        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['count'] <=> $a['count'];
            }
            return $b['score'] <=> $a['score'];
        });

        return array_slice($candidates, 0, max(1, (int)$limit));
    }

    /**
     * Return normalized text/value content.
     *
     * Element nodes use all descendant text in source order. Attribute/text
     * XPath results fall back to their own node value, so `./a/@href` is useful
     * without requiring a separate attribute API.
     *
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
            // Detached/manual models cannot run XPath; use recursive fallback.
        }

        if (!count($parts)) {
            self::collectTextFallback($node, $parts);
        }

        $text = preg_replace('/\s+/u', ' ', trim(implode(' ', $parts)));
        return $text === null ? '' : trim($text);
    }

    /**
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
            if (!in_array($attributeName, self::$semanticAttributes, true) && strpos($attributeName, 'data-') !== 0) {
                continue;
            }
            self::addTokens($tokens, $attributeName);
            self::addTokens($tokens, (string)$attributeValue);
        }

        return array_values(array_unique(array_filter($tokens, function ($token) {
            return $token !== '';
        })));
    }

    /** @return DomNodeModel */
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

    private static function normalizeNeedles(array $names)
    {
        $tokens = [];
        foreach ($names as $name) {
            self::addTokens($tokens, (string)$name);
        }
        return array_values(array_unique($tokens));
    }

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
                if ($part !== '') {
                    $tokens[] = strtolower($part);
                }
            }
        }
    }

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
     * Return both the exact structure signature and stable per-class
     * signatures. The latter are intentionally looser so optional modifier
     * classes do not split one repeated row family into several candidates.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getStructureSignatures(DomNodeModel $node)
    {
        $signature = self::getStructureSignature($node);
        if ($signature === null) {
            return [];
        }

        $signatures = [$signature];
        foreach ($signature['classes'] as $class) {
            $signatures[] = [
                'key' => implode('|', ['class', $signature['tag'], $class]),
                'tag' => $signature['tag'],
                'classes' => [$class],
                'role' => '',
                'itemprop' => '',
                'itemtype' => '',
            ];
        }

        return $signatures;
    }

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

        if (!count($classes) && $role === '' && $itemprop === '' && $itemtype === ''
            && !in_array($tag, ['article', 'li', 'tr', 'item', 'entry'], true)) {
            return null;
        }

        $classes = array_slice($classes, 0, 4);
        return [
            'key' => implode('|', [$tag, implode('.', $classes), $role, $itemprop, $itemtype]),
            'tag' => $tag,
            'classes' => $classes,
            'role' => $role,
            'itemprop' => $itemprop,
            'itemtype' => $itemtype,
        ];
    }

    private static function buildStructureXPath(array $signature)
    {
        $conditions = ['local-name()=' . self::xpathLiteral($signature['tag'])];
        foreach ($signature['classes'] as $class) {
            $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), " . self::xpathLiteral(' ' . $class . ' ') . ')';
        }
        foreach (['role', 'itemprop', 'itemtype'] as $attribute) {
            if ($signature[$attribute] !== '') {
                $conditions[] = '@' . $attribute . '=' . self::xpathLiteral($signature[$attribute]);
            }
        }
        return '//*[' . implode(' and ', $conditions) . ']';
    }

    private static function scoreStructure(DomNodeModel $sample, $count, array $semanticNames)
    {
        $score = min(20, (int)$count * 2);
        $tag = strtolower((string)$sample->getName());
        $tagScores = ['article' => 10, 'item' => 10, 'entry' => 10, 'li' => 5, 'tr' => 5, 'a' => 3, 'section' => 2, 'div' => 1];
        $score += isset($tagScores[$tag]) ? $tagScores[$tag] : 0;
        $score += count(array_intersect(['article', 'entry', 'item', 'row', 'result', 'card', 'post', 'news', 'release', 'product', 'search'], $semanticNames)) * 3;
        $score -= count(array_intersect(['nav', 'menu', 'footer', 'header', 'sidebar', 'cookie', 'advert', 'advertisement', 'banner', 'pagination'], $semanticNames)) * 8;
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

    private static function incrementAttributeTokens(array &$target, $value)
    {
        foreach (self::attributeTokens($value) as $token) {
            self::increment($target, $token);
        }
    }

    private static function attributeTokens($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }
        $tokens = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($tokens) ? array_values(array_unique(array_map('strtolower', $tokens))) : [];
    }

    private static function increment(array &$target, $value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return;
        }
        $target[$value] = isset($target[$value]) ? $target[$value] + 1 : 1;
    }

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

    private static function preview($text, $length)
    {
        return function_exists('mb_substr')
            ? mb_substr((string)$text, 0, (int)$length)
            : substr((string)$text, 0, (int)$length);
    }
}

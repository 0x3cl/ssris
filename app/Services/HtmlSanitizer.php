<?php

namespace App\Services;

use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Strips any markup that is not on an explicit allow-list, so rich text
 * saved from the form template editor can never carry an XSS payload.
 */
class HtmlSanitizer
{
    /**
     * Allowed tags mapped to the attributes each one may keep.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'blockquote' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'target', 'rel'],
    ];

    /**
     * Tags that are removed along with everything nested inside them.
     *
     * @var list<string>
     */
    private const STRIP_ENTIRELY = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'form', 'link', 'meta', 'base', 'noscript', 'template',
    ];

    /** @var list<string> */
    private const ALLOWED_URL_SCHEMES = ['http', 'https', 'mailto'];

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();

        $container = $document->getElementsByTagName('div')->item(0);

        if (! $container instanceof DOMElement) {
            return '';
        }

        $this->cleanChildren($container);

        $result = '';
        foreach (iterator_to_array($container->childNodes) as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        $node = $parent->firstChild;

        while ($node !== null) {
            $next = $node->nextSibling;

            if ($node instanceof DOMText || $node instanceof DOMCdataSection) {
                $node = $next;

                continue;
            }

            if (! $node instanceof DOMElement) {
                $parent->removeChild($node);
                $node = $next;

                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::STRIP_ENTIRELY, true)) {
                $parent->removeChild($node);
                $node = $next;

                continue;
            }

            if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
                $previous = $node->previousSibling;

                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                $node = $previous !== null ? $previous->nextSibling : $parent->firstChild;

                continue;
            }

            $this->cleanAttributes($node, self::ALLOWED_TAGS[$tag]);
            $this->cleanChildren($node);
            $node = $next;
        }
    }

    /** @param list<string> $allowedAttributes */
    private function cleanAttributes(DOMElement $element, array $allowedAttributes): void
    {
        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowedAttributes, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'href' && ! $this->hasAllowedScheme($attribute->value)) {
                $element->removeAttribute($attribute->name);

                continue;
            }
        }

        if ($element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function hasAllowedScheme(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, self::ALLOWED_URL_SCHEMES, true);
    }
}

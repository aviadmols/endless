<?php

namespace App\Services\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allow-list HTML sanitizer for user generated rich text (memories, biography).
 * No external dependency — DOMDocument only.
 */
class HtmlSanitizer
{
    /** @var array<string, string[]> tag => allowed attributes */
    protected array $allowed = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'a' => ['href'], 'span' => [], 'hr' => [],
    ];

    /** Elements removed together with their content. */
    protected array $dropWithContent = ['script', 'style', 'iframe', 'object', 'embed', 'video', 'audio', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'link', 'meta', 'head', 'title'];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return '';
        }

        $this->walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        // Normalise whitespace-only paragraphs and non-breaking spaces produced by editors.
        $out = preg_replace('/<p>(\s|&nbsp;|<br\s*\/?>)*<\/p>/u', '', $out) ?? $out;

        return trim($out);
    }

    public function toPlainText(?string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/li|\/h[1-6]|\/blockquote)\s*\/?>/i', "$0\n", (string) $html) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text) ?? $text;
        $text = preg_replace("/\n\s*\n+/u", "\n", $text) ?? $text;

        return trim($text);
    }

    protected function walk(DOMNode $node): void
    {
        // Iterate over a static copy — we mutate the tree while walking.
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMText) {
                continue;
            }
            if (! $child instanceof DOMElement) {
                $node->removeChild($child); // comments, processing instructions
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, $this->dropWithContent, true)) {
                $node->removeChild($child);
                continue;
            }

            if (! array_key_exists($tag, $this->allowed)) {
                // Unwrap: keep the children, drop the tag.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $this->cleanAttributes($child, $this->allowed[$tag]);
            $this->walk($child);
        }
    }

    protected function cleanAttributes(DOMElement $el, array $allowedAttrs): void
    {
        $remove = [];
        foreach ($el->attributes as $attr) {
            $name = strtolower($attr->name);
            if (! in_array($name, $allowedAttrs, true)) {
                $remove[] = $attr->name;
                continue;
            }
            if ($name === 'href') {
                $href = trim($attr->value);
                if (! preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
                    $remove[] = $attr->name;
                }
            }
        }
        foreach ($remove as $name) {
            $el->removeAttribute($name);
        }
        if (strtolower($el->tagName) === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('rel', 'noopener nofollow');
            $el->setAttribute('target', '_blank');
        }
    }
}

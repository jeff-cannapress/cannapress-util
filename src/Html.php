<?php

declare(strict_types=1);

namespace CannaPress\Util;


use Exception;

class Html
{
    public static function render(array $tree): string
    {
        $parts = self::render_html_from_tree($tree);
        return implode('', $parts);
    }
    private static function render_html_from_tree($tree): array
    {
        $result = [];
        if (is_string($tree)) {
            $result[] = $tree;
        } else if (is_array($tree)) {
            if (count($tree) === 0) {
                return $result;
            }
            $tag = $tree[0];
            $attribs = isset($tree[1]) && $tree[1] !== null ? $tree[1] : [];
            $content = isset($tree[2]) && $tree[2] !== null ? $tree[2] : [];

            $result[] = '<';
            if (!is_string($tag)) {
                throw new Exception('The tag array must be a 3-element array of [string $tag, array|null $attributes, array|string|null $content]');
            }
            $result[] = $tag;
            $result[] = ' ';

            foreach ($attribs as $attr_key => $attr_val) {
                if ($attr_key === 'class') {
                    $attr_val = self::coalesce_classes($attr_val);
                }
                $result[] = $attr_key;
                $result[] = '="';
                $result[] = esc_attr($attr_val);
                $result[] = '" ';
            }

            if (!empty($content)) {
                $result[] = '>';
                if (is_string($content)) {
                    $result[] = $content;
                } else if (is_array($content)) {
                    foreach ($content as $content_part) {
                        $inner_element = self::render_html_from_tree($content_part);
                        $result = array_merge($result, $inner_element);
                    }
                }
                $result[] = ' </';
                $result[] = $tag;
                $result[] = '>';
            } else {
                $result[] = '/>';
            }
        }
        return $result;
    }

    private static function flatten_classes($parts, array &$result)
    {
        foreach ($parts as $part) {
            if (is_array($part)) {
                self::flatten_classes($part, $result);
            } else {
                $result[] = $part;
            }
        }
    }
    public static function coalesce_classes(...$parts): string
    {
        $classes = [];
        self::flatten_classes($parts, $classes);
        return implode(' ', $classes);
    }
}

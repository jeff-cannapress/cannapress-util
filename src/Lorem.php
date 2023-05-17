<?php

declare(strict_types=1);

namespace CannaPress\Util;

class Lorem
{
    public static function get_text($what = 'paras', $amount = 3, $start_with_lorem_ipsum = false)
    {
        $start_with_lorem_ipsum = $start_with_lorem_ipsum ? 'yes' : 'no';

        $response = wp_remote_get(strval("https://www.lipsum.com/feed/json?what=$what&amount=$amount&start=$start_with_lorem_ipsum"));
        $json = json_decode($response['body']);
        return $json->feed->lipsum;
    }
    public static function get_picsum_info()
    {
        $cache_key = self::class . '::picsum_info';
        $infos = wp_cache_get($cache_key);
        if ($infos === false) {
            $infos = [];
            for ($i = 1; $i <= 10; $i++) {
                $response = wp_remote_get("https://picsum.photos/v2/list?page=$i&limit=100");
                $json = json_decode($response['body']);
                $infos = array_merge($infos, $json);
            }
            $result = [];
            foreach ($infos as $item) {
                $result[$item->id] = $item;
            }
            $infos = $result;
            wp_cache_set($cache_key, $infos);
        }
        return $infos;
    }

    public static function create()
    {
        $infos = self::get_picsum_info();
        shuffle($infos);
        return new class ($infos) {
            public function __construct(private array $infos, private $current = -1)
            {
            }
            public function fill($post_id, $text_args = [])
            {
                if (!isset($text_args['what'])) {
                    $text_args['what'] = 'paras';
                }
                if (!isset($text_args['count'])) {
                    $text_args['count'] = 3;
                }
                Lorem::set_post_content($post_id, $text_args['what'], $text_args['count']);
                $this->featured_image($post_id);
            }
            public function title()
            {
                return ucwords(Lorem::get_text('words', 5));
            }
            public function words($count = 5)
            {
                return explode(' ', Lorem::get_text('words', $count));
            }
            public function paras($count = 1)
            {
                return explode("\n", Lorem::get_text('paras', $count));
            }
            public function post_body($count = 3)
            {
                $paras = $this->paras($count);
                $paras = array_map(fn ($x) => '<!-- wp:paragraph --><p>' . esc_html($x) . '</p><!-- /wp:paragraph -->', $paras);
                $text = implode('\n', $paras);
                return $text;
            }
            public function featured_image($post_id)
            {
                $this->current++;
                if ($this->current > count($this->infos)) {
                    $this->current = 0;
                }
                $picsum_id = $this->infos[$this->current]->id;
                Lorem::set_post_featured_image_to($post_id, $picsum_id);
            }
        };
    }
    public static function set_post_featured_image_to($post_id, $picsum_id)
    {
        $metadata = self::download_picsum_attachment($picsum_id);
        $attachment_id = wp_insert_attachment([
            'post_mime_type' => 'image/jpeg',
            'post_title' => sanitize_file_name($metadata['file']),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $metadata['file'], $post_id);
        wp_update_attachment_metadata($attachment_id, $metadata);
        set_post_thumbnail($post_id, $attachment_id);
    }
    public static function set_post_content($post_id, $what = 'paras', $para_count = 3)
    {
        $text = self::get_text($what, $para_count, false);
        if ($what === 'paras') {
            $paras = explode('\n', $text);
            $paras = array_map(fn ($x) => '<!-- wp:paragraph --><p>' . esc_html($x) . '</p><!-- /wp:paragraph -->', $paras);
            $text = implode('\n', $paras);
        } else {
            $text = '<!-- wp:paragraph --><p>' . esc_html($text) . '</p><!-- /wp:paragraph -->';
        }
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $text
        ]);
    }

    public static function download_picsum_attachment($id)
    {
        $picsum_item = self::get_picsum_info()[$id];
        $uploads = wp_upload_dir();
        $picsum_dir = untrailingslashit($uploads['basedir']) . '/picsum/' . $id;

        if (!file_exists($picsum_dir)) {
            mkdir($picsum_dir, 0777, true);
        }
        $picsum_template = [
            'width' => $picsum_item->width,
            'height' => $picsum_item->height,
            'file' => $picsum_dir . '/default.jpg',
            'sizes' => [
                'thumbnail' => [
                    'width' => 150,
                    'height' => 150,
                    'file' => 'thumbnail.jpg',
                    //'file' => $picsum_dir . '/default.jpg',
                ],
                'medium' => [
                    'width' => 300,
                    'height' => 300,
                    'file' => 'medium.jpg',
                    //'file' => $picsum_dir . '/default.jpg',
                ],
                'medium-16-9' => [
                    'width' => 300,
                    'height' => 169,
                    'file' => 'medium.jpg',
                    //'file' => $picsum_dir . '/default.jpg',
                ],
                'large' => [
                    'width' => 1024,
                    'height' => 1024,
                    'file' => 'large.jpg',
                    //'file' => $picsum_dir . '/default.jpg',
                ],
                'post-thumbnail' => [
                    'width' => 150,
                    'height' => 150,
                    'file' => 'post-thumbnail.jpg',
                    //'file' => $picsum_dir . '/default.jpg',
                ],
                'large-feature' => [
                    'width' => 1024,
                    'height' => 576,
                    'file' => 'large-feature.jpg',
                    //'file' => $picsum_dir . '/default.jpg',
                ],
                'small-feature' => [
                    'width' => 600,
                    'height' => 338,
                    'file' => 'small-feature.jpg',
                    //'file' => $picsum_dir . '/default.jpg',
                ],
            ],
            'image_meta' => []
        ];

        if (!file_exists($picsum_template['file'])) {
            $temp_filename = download_url($picsum_item->download_url . '.jpg');
            rename($temp_filename, $picsum_template['file']);
            $can_use_editor = self::can_use_editor($picsum_template['file']);

            $picsum_id = $picsum_item->id;
            foreach ($picsum_template['sizes'] as $name => $size) {
                $output_file = $picsum_dir . '/' . $size['file'];
                if ($can_use_editor) {
                    try {
                        $editor = wp_get_image_editor($picsum_template['file']);
                        $editor->resize($size['width'], $size['height'], str_ends_with($name, '-feature'));
                        $editor->save($output_file);
                    } catch (\Exception $ex) {
                        $can_use_editor = false;
                    }
                }
                //This isnt an else so that we fall-through if something went wrong above;
                if (!$can_use_editor) {
                    $picsum_width = $size['width'];
                    $picsum_height = $size['height'];
                    $child_url = "https://picsum.photos/id/$picsum_id/$picsum_width/$picsum_height.jpg";
                    $temp_filename = download_url($child_url);
                    rename($temp_filename, $output_file);
                }
            }
        }
        return $picsum_template;
    }
    private static function can_use_editor($file)
    {
        $editor =  wp_get_image_editor($file);
        if (!is_wp_error($editor)) {
            return true;
        }
        return false;
    }
}

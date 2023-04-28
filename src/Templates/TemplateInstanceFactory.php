<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

use Exception;
use WP_Block_Template;

class TemplateInstanceFactory
{
    public function __construct(public $file_name, public string $filters_identifier)
    {
    }
    protected function filter_instance_props($instance_props)
    {
        if (empty($instance_props)) {
            $instance_props = [];
        }
        if (is_object($instance_props)) {
            $new_instance_props = [];
            foreach (get_object_vars($instance_props) as $k => $v) {
                $new_instance_props[$k] = $v;
            }
            $instance_props = $new_instance_props;
        }

        $instance_props = TemplateManagerHooks::get_instance_props($instance_props, $this->filters_identifier, $this->file_name, $this);
        return $instance_props;
    }

    public function create($instance_props = [])
    {
        $instance_props =  $this->filter_instance_props($instance_props);
        return new class($this->file_name, $this->filters_identifier, $instance_props)
        {
            public const CANNAPRESS_IS_TEMPLATE_INSTANCE = true;
            public array|null $block_template_metainfo = null;
            public function __construct(
                private string $abs_path_d3176d960d2749458b58b24f2813d7f2,
                private string $filters_d3176d960d2749458b58b24f2813d7f2,
                $instance_props
            ) {
                foreach ($instance_props as $k => $v) {
                    $this->{$k} = $v;
                }
            }
            public function check_emit(): bool
            {
                return TemplateManagerHooks::should_do_emit(true, $this->filters_d3176d960d2749458b58b24f2813d7f2, $this->abs_path_d3176d960d2749458b58b24f2813d7f2, $this);
            }
            public function emit()
            {
                if ($this->check_emit()) {
                    $html = $this->render();
                    echo ($html);
                }
            }
            public function render()
            {
                $html = TemplateManagerHooks::before_template_instance_rendered("", $this->filters_d3176d960d2749458b58b24f2813d7f2, $this->abs_path_d3176d960d2749458b58b24f2813d7f2, $this);
                $html = $this->render_raw();
                $html = TemplateManagerHooks::template_instance_rendered($html, $this->filters_d3176d960d2749458b58b24f2813d7f2, $this->abs_path_d3176d960d2749458b58b24f2813d7f2, $this);
                return $html;
            }
            private function render_raw()
            {
                ob_start();
                TemplateManagerHooks::before_template_file_included($this->filters_d3176d960d2749458b58b24f2813d7f2, $this->abs_path_d3176d960d2749458b58b24f2813d7f2, $this);
                include($this->abs_path_d3176d960d2749458b58b24f2813d7f2);
                TemplateManagerHooks::after_template_file_included($this->filters_d3176d960d2749458b58b24f2813d7f2, $this->abs_path_d3176d960d2749458b58b24f2813d7f2, $this);
                $result_d3176d960d2749458b58b24f2813d7f2 = ob_get_contents();
                ob_end_clean();
                return $result_d3176d960d2749458b58b24f2813d7f2;
            }
            /** @return WP_Block_Template  */
            public function create_block_template(string $template_type = 'wp_template'): WP_Block_Template
            {
                $template_content       = $this->render_raw();
                if ($this->block_template_metainfo === null) {
                    throw new Exception("Tried to create a WP_Block_Template instance from {$this->abs_path_d3176d960d2749458b58b24f2813d7f2} but no property `block_template_metainfo` was set during render");
                }
                $theme = isset($this->block_template_metainfo['theme']) ? $this->block_template_metainfo['theme'] : get_stylesheet();
                $slug = !empty($this->block_template_metainfo['slug']) ? $this->block_template_metainfo['slug'] : $this->filters_d3176d960d2749458b58b24f2813d7f2;

                $template                 = new WP_Block_Template();
                $template->id             = $theme . '//' . $slug;
                $template->theme          = $theme;
                $template->content        =  _inject_theme_attribute_in_block_template_content( $template_content );
                $template->slug           = $slug;
                $template->source         = 'theme';
                $template->type           = $template_type;
                $template->title          = !empty($this->block_template_metainfo['title']) ? $this->block_template_metainfo['title'] : $slug;
                $template->status         = 'publish';
                $template->has_theme_file = true;
                $template->is_custom      = false;
                if ('wp_template' === $template_type) {
                    $template->description = !empty($this->block_template_metainfo['description']) ? $this->block_template_metainfo['description'] : $template->title;
                    if (!empty($this->block_template_metainfo['postTypes'])) {
                        $template->description = $this->block_template_metainfo['postTypes'];
                    }
                }
                if ('wp_template_part' === $template_type && isset($this->block_template_metainfo['area'])) {
                    $template->area = $this->block_template_metainfo['area'];
                }

                return $template;
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

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
        return new class ($this->file_name, $this->filters_identifier, $instance_props) {
            public const CANNAPRESS_IS_TEMPLATE_INSTANCE = true;
            public function __construct(
                private string $abs_path_d3176d960d2749458b58b24f2813d7f2,
                private string $filters_d3176d960d2749458b58b24f2813d7f2,
                $instance_props
            ) {
                foreach ($instance_props as $k => $v) {
                    $this->{$k} = $v;
                }
            }
            public function emit()
            {
                $should_do_emit = TemplateManagerHooks::should_do_emit(true, $this->filters_d3176d960d2749458b58b24f2813d7f2, $this->abs_path_d3176d960d2749458b58b24f2813d7f2, $this);
                
                if ($should_do_emit) {
                    $html = $this->render();
                    echo($html);
                }
            }
            public function render()
            {
                $html = TemplateManagerHooks::before_template_instance_rendered("", $this->filters_d3176d960d2749458b58b24f2813d7f2, $this->abs_path_d3176d960d2749458b58b24f2813d7f2, $this);
                $html = $this->render_raw();
                $html = do_blocks($html);
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
        };
    }
}

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
        $instance_props = TemplateManager::apply_filters([$this->filters_identifier, __FUNCTION__], $instance_props, $this);
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
                $should_do_emit = TemplateManager::apply_filters([$this->filters_d3176d960d2749458b58b24f2813d7f2, ('before_' . (__FUNCTION__))], true, $this->abs_path_d3176d960d2749458b58b24f2813d7f2);
                if ($should_do_emit) {
                    $html = $this->render();
                    echo($html);
                }
            }
            public function render()
            {
                $html = $this->render_raw();
                $html = do_blocks($html);
                $html = TemplateManager::apply_filters([$this->filters_d3176d960d2749458b58b24f2813d7f2, __FUNCTION__], $html, $this->abs_path_d3176d960d2749458b58b24f2813d7f2);
                return $html;
            }
            private function render_raw()
            {
                ob_start();
                include($this->abs_path_d3176d960d2749458b58b24f2813d7f2);
                $result_d3176d960d2749458b58b24f2813d7f2 = ob_get_contents();
                ob_end_clean();
                return $result_d3176d960d2749458b58b24f2813d7f2;
            }
        };
    }
}

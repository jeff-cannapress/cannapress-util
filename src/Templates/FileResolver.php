<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

class FileResolver
{

    protected function apply_filter($name, $item, ...$rest)
    {
        return TemplateManager::apply_filters($name, ...[$item, ...$rest]);
    }

    public function get_possible_file_names($name, array $extensions)
    {
        $name = untrailingslashit($name);
        $templates = [];
        //make sure none of the extensions start with a dot
        $extensions = array_map(fn ($x) => str_starts_with($x, '.') ? substr($x, 1) : $x, $extensions);
        if (!empty($extensions)) {
            foreach ($extensions as $ext) {
                $templates[] = $name . '.' . $ext;
            }
            foreach ($extensions as $ext) {
                $templates[] = trailingslashit($name) . 'index.' . $ext;
            }
        }
        else{
            $templates[] = $name;
        }

        return $this->apply_filter(__FUNCTION__, $templates, $name);
    }
}

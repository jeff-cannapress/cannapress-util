<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

class FileResolver
{

    public function get_possible_file_names(string $part_name, array $extensions)
    {
        $part_name = untrailingslashit($part_name);

        $possible_file_names = TemplateManagerHooks::before_get_possible_file_names([], $part_name, $extensions);
        if (!empty($possible_file_names)) {
            return $possible_file_names;
        }
        //make sure none of the extensions start with a dot
        $extensions = array_map(fn ($x) => str_starts_with($x, '.') ? substr($x, 1) : $x, $extensions);
        if (!empty($extensions)) {
            foreach ($extensions as $ext) {
                $possible_file_names[] = $part_name . '.' . $ext;
            }
            foreach ($extensions as $ext) {
                $possible_file_names[] = trailingslashit($part_name) . 'index.' . $ext;
            }
        } else {
            $possible_file_names[] = $part_name;
        }
        return TemplateManagerHooks::get_possible_file_names($possible_file_names, $part_name, $extensions);
    }
}

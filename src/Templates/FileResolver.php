<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

class FileResolver
{
    public function get_possible_file_names(string $part_name, array $extensions):array
    {
        $part_name = untrailingslashit($part_name);


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
        return $possible_file_names;
    }
}

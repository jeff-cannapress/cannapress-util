<?php
declare(strict_types=1);




use PHPUnit\Framework\TestCase;
use CannaPress\Util\Templates\PathResolver;

function apply_filters($name, $value, ...$args){
    return $value;
}
function untrailingslashit( $string ) {
	return rtrim( $string, '/\\' );
}
function trailingslashit($string){
    return untrailingslashit($string).'/';
}



final class PathResolutionTest extends TestCase
{
    public function testCanResolvePluginPath(): void
    {
        $resolver = new PathResolver( 'prefix',  'plugin_path' );
        $value = $resolver->enumerate_possible_files(__FUNCTION__,'abcd1234','abcd1234');
        $this->assertNull($value);
    }

    static $template_directory = 'parent_theme_dir';
    static $stylesheet_directory = 'child_theme_dir';
}

function get_template_directory(){
    return PathResolutionTest::$template_directory;
}
function get_stylesheet_directory(){
    return PathResolutionTest::$stylesheet_directory;
}
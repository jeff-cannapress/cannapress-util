<?php
declare(strict_types=1);

use CannaPress\Util\Templates\DirectoryResolver;
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
    public function testCanGeneratePathsWithChildTheme(): void
    {

        $dirs = new DirectoryResolver('prefix',  'plugin_path');
        self::$template_directory = 'parent_theme_dir';
        self::$stylesheet_directory = 'child_theme_dir';
        $actual = $dirs->get_possible_template_folders();
        $expected = [
            1 => 'child_theme_dir/prefix/',
            10 => 'parent_theme_dir/prefix/',
            15 => 'parent_theme_dir/',
            100000 => 'plugin_path/',
        ];
        foreach($expected as $key=>$expectedValue){
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expectedValue, $actual[$key]);
        }
    }

    public function testCanGeneratePathsWithoutChildTheme(): void
    {

        $dirs = new DirectoryResolver('prefix',  'plugin_path');
        self::$template_directory = 'parent_theme_dir';
        self::$stylesheet_directory = 'parent_theme_dir';
        $actual = $dirs->get_possible_template_folders();
        $expected = [
            10 => 'parent_theme_dir/prefix/',
            15 => 'parent_theme_dir/',
            100000 => 'plugin_path/',
        ];
        foreach($expected as $key=>$expectedValue){
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expectedValue, $actual[$key]);
        }
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
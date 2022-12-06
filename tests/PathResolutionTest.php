<?php

declare(strict_types=1);

use CannaPress\Util\Templates\DirectoryResolver;
use CannaPress\Util\Templates\FileResolver;
use PHPUnit\Framework\TestCase;
use CannaPress\Util\Templates\PathResolver;

function apply_filters($name, $value, ...$args)
{
    return $value;
}
function untrailingslashit($string)
{
    return rtrim($string, '/\\');
}
function trailingslashit($string)
{
    return untrailingslashit($string) . '/';
}



final class PathResolutionTest extends TestCase
{

    public function testCanGeneratePathsInScope(): void
    {

        $dirs = (new DirectoryResolver('cannapress',  'templates'))->child_resolver('public');
        self::$template_directory = 'parent_theme_dir';
        self::$stylesheet_directory = 'child_theme_dir';
        $actual = $dirs->get_possible_template_folders();
        $expected = [
            1 => 'child_theme_dir/cannapress/public/',
            10 => 'parent_theme_dir/cannapress/public/',
            15 => 'parent_theme_dir/public/',
            100000 => 'templates/public/',
        ];
        foreach ($expected as $key => $expectedValue) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expectedValue, $actual[$key]);
        }
    }
    
    
    public function testCanGeneratePathsWithChildTheme(): void
    {

        $dirs = new DirectoryResolver('cannapress',  'templates');
        self::$template_directory = 'parent_theme_dir';
        self::$stylesheet_directory = 'child_theme_dir';
        $actual = $dirs->get_possible_template_folders();
        $expected = [
            1 => 'child_theme_dir/cannapress/',
            10 => 'parent_theme_dir/cannapress/',
            15 => 'parent_theme_dir/',
            100000 => 'templates/',
        ];
        foreach ($expected as $key => $expectedValue) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expectedValue, $actual[$key]);
        }
    }

    public function testCanGeneratePathsWithoutChildTheme(): void
    {

        $dirs = new DirectoryResolver('cannapress',  'templates');
        self::$template_directory = 'parent_theme_dir';
        self::$stylesheet_directory = 'parent_theme_dir';
        $actual = $dirs->get_possible_template_folders();
        $expected = [
            10 => 'parent_theme_dir/cannapress/',
            15 => 'parent_theme_dir/',
            100000 => 'templates/',
        ];
        foreach ($expected as $key => $expectedValue) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expectedValue, $actual[$key]);
        }
    }

    static $template_directory = 'parent_theme_dir';
    static $stylesheet_directory = 'child_theme_dir';

    public function testCanResolveAbsolutePaths()
    {
        self::$template_directory = 'parent_theme_dir';
        self::$stylesheet_directory = 'child_theme_dir';
        $resolver = new PathResolver(new DirectoryResolver('cannapress', 'templates'));
        $actual = $resolver->get_all_possible_paths('expected', ['php', 'html']);
        $expected = [
            'child_theme_dir/cannapress/expected.php',
            'child_theme_dir/cannapress/expected.html',
            'child_theme_dir/cannapress/expected/index.php',
            'child_theme_dir/cannapress/expected/index.html',
            'parent_theme_dir/cannapress/expected.php',
            'parent_theme_dir/cannapress/expected.html',
            'parent_theme_dir/cannapress/expected/index.php',
            'parent_theme_dir/cannapress/expected/index.html',
            'parent_theme_dir/expected.php',
            'parent_theme_dir/expected.html',
            'parent_theme_dir/expected/index.php',
            'parent_theme_dir/expected/index.html',
            'templates/expected.php',
            'templates/expected.html',
            'templates/expected/index.php',
            'templates/expected/index.html',
        ];
        $this->assertEquals(count($expected), count($actual));
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertTrue(isset($actual[$i]));
            $this->assertEquals($expected[$i], $actual[$i]);
        }
    }
    public function testCanResolveDirectFileName()
    {
        $resolver =  new FileResolver();
        $expected = 'foo/bar/baz.js';
        $actual = $resolver->get_possible_file_names($expected, []);
        $this->assertEquals(1, count($actual));
        $this->assertEquals($expected, $actual[0]);
    }
}

function get_template_directory()
{
    return PathResolutionTest::$template_directory;
}
function get_stylesheet_directory()
{
    return PathResolutionTest::$stylesheet_directory;
}

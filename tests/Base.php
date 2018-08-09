<?php


namespace AngryCreative\WPLanguageUpdater;


class Base extends \PHPUnit_Framework_TestCase
{
    protected $wp_content = '';

    public function setUp(  )
    {
        $this->wp_content = __DIR__.'/wp-content';

        if(file_exists($this->wp_content)) {
            $this->removeDirectory($this->wp_content);
        }
        mkdir($this->wp_content);
    }

    protected function removeDirectory($path)
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
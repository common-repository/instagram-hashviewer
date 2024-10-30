<?php

class HashViewerTest extends PHPUnit_Framework_TestCase
{
	private $viewer;	

	public function testFilterHashtags()
	{
		echo 'lol';
		require_once('hash-viewer.php');
		echo 'lol';
		require_once('classes/HashViewer.class.php');
		echo 'lol';

		$this->viewer = HashViewer::get_instance(); 
		var_dump($this->viewer);
		$this->assertEquals($this->viewer->filter_hashtags("mittsteinkjer"), '"mittsteinkjer"');
		$this->assertEquals($this->viewer->filter_hashtags("	 mittsteinkjer	 "), '"mittsteinkjer"');
		$this->assertEquals($this->viewer->filter_hashtags("#mittsteinkjer"), '"mittsteinkjer"');
		$this->assertEquals($this->viewer->filter_hashtags("mittsteinkjer ,ukm"), '"mittsteinkjer"');
		$this->assertEquals($this->viewer->filter_hashtags("mittsteinkjer, ukm"), '"mittsteinkjer"');
	}

	public function testEmpty()
    {
        $stack = array();
        $this->assertEmpty($stack);

        return $stack;
    }

    /**
     * @depends testEmpty
     */
    public function testPush(array $stack)
    {
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertNotEmpty($stack);

        return $stack;
    }
}
?>
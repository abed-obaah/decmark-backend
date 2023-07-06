<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Intervention\Image\Image;
use Tests\TestCase;

class UtilsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Random numeric
     *
     * @return void
     */
    public function testRandomNumberString()
    {
        $string = Utils::random(10, false, true, false);
        $string2 = Utils::random(10, false, true, false);

        $this->assertNotSame($string, $string2);
        $this->assertSame(strlen($string), strlen($string2));
        $this->assertTrue(strlen($string) + strlen($string2) === 20);
        $this->assertTrue(ctype_digit($string));
    }

    /**
     * Test Random lower string
     *
     * @return void
     */
    public function testRandomLowerString()
    {
        $string = Utils::random(10, true, false, false);
        $string2 = Utils::random(10, true, false, false);

        $this->assertNotSame($string, $string2);
        $this->assertSame(strlen($string), strlen($string2));
        $this->assertTrue(strlen($string) + strlen($string2) === 20);
        $this->assertTrue(ctype_lower($string));
    }

    /**
     * Test Random upper string
     *
     * @return void
     */
    public function testRandomUpperString()
    {
        $string = Utils::random(10, false, false, true);
        $string2 = Utils::random(10, false, false, true);

        $this->assertNotSame($string, $string2);
        $this->assertSame(strlen($string), strlen($string2));
        $this->assertTrue(strlen($string) + strlen($string2) === 20);
        $this->assertTrue(ctype_upper($string));
    }

    /**
     * Test Random number lower string
     *
     * @return void
     */
    public function testRandomNumberLowerString()
    {
        $string = Utils::random(20, true, true, false);
        $string2 = Utils::random(20, true, true, false);

        $this->assertTrue(ctype_alnum($string));
        $this->assertTrue(strtoupper($string) <> $string);
    }

    /**
     * Test Random number upper string
     *
     * @return void
     */
    public function testRandomNumberUpperString()
    {
        $string = Utils::random(20, false, true, true);

        $this->assertTrue(ctype_alnum($string));
        $this->assertTrue(strtolower($string) <> $string);
    }

    /**
     * Test Random lower Upper string
     *
     * @return void
     */
    public function testRandomLowerUpperString()
    {
        $string = Utils::random(20, true, false, true);

        $this->assertTrue(ctype_alpha($string));
        $this->assertTrue(strtolower($string) <> $string || strtoupper($string) <> $string);
    }
}

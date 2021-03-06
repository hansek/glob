<?php

/*
 * This file is part of the webmozart/glob package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Glob\Tests;

use PHPUnit_Framework_TestCase;
use Webmozart\Glob\Glob;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobTest extends PHPUnit_Framework_TestCase
{
    public function testGlob()
    {
        $fixturesDir = __DIR__.'/Iterator/Fixtures';

        $this->assertSame(array(
            $fixturesDir.'/base.css',
            $fixturesDir.'/css/reset.css',
            $fixturesDir.'/css/style.css',
        ), Glob::glob($fixturesDir.'/*.css'));

        $this->assertSame(array(
            $fixturesDir.'/base.css',
            $fixturesDir.'/css',
            $fixturesDir.'/css/reset.css',
            $fixturesDir.'/css/style.css',
        ), Glob::glob($fixturesDir.'/*css*'));

        $this->assertSame(array(), Glob::glob($fixturesDir.'/*foo*'));
    }
    /**
     * @dataProvider provideMatches
     */
    public function testToRegEx($path, $isMatch)
    {
        $regExp = Glob::toRegEx('/foo/*.js~');

        $this->assertSame($isMatch, preg_match($regExp, $path));
    }

    public function provideMatches()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/bar/baz.js~', 0),
            array('/foo/baz.js~', 1),
            array('/foo/../bar/baz.js~', 1),
            array('/foo/../foo/baz.js~', 1),
            array('/bar/baz.js', 0),
            array('/foo/bar/baz.js~', 1),
            array('foo/baz.js~', 0),
            array('/bar/foo/baz.js~', 0),
            array('/bar/.js~', 0),
        );
    }

    // From the PHP manual: To specify a literal single quote, escape it with a
    // backslash (\). To specify a literal backslash, double it (\\).
    // All other instances of backslash will be treated as a literal backslash

    public function testEscapedWildcard()
    {
        // evaluates to "\*"
        $regExp = Glob::toRegEx('/foo/\\*.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testEscapedWildcard2()
    {
        // evaluates to "\*"
        $regExp = Glob::toRegEx('/foo/\*.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcard()
    {
        // evaluates to "\*"
        $regExp = Glob::toRegEx('/foo/\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/*.js~'));
    }

    public function testMatchWildcardWithLeadingBackslash()
    {
        // evaluates to "\\*"
        $regExp = Glob::toRegEx('/foo/\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchWildcardWithLeadingBackslash2()
    {
        // evaluates to "\\*"
        $regExp = Glob::toRegEx('/foo/\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcardWithLeadingBackslash()
    {
        // evaluates to "\\\*"
        $regExp = Glob::toRegEx('/foo/\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\*.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
    }

    public function testMatchWildcardWithTwoLeadingBackslashes()
    {
        // evaluates to "\\\\*"
        $regExp = Glob::toRegEx('/foo/\\\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcardWithTwoLeadingBackslashes()
    {
        // evaluates to "\\\\*"
        $regExp = Glob::toRegEx('/foo/\\\\\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\\\*.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\\baz.js~'));
    }

    /**
     * @dataProvider provideStaticPrefixes
     */
    public function testGetStaticPrefix($glob, $prefix)
    {
        $this->assertSame($prefix, Glob::getStaticPrefix($glob));
    }

    public function provideStaticPrefixes()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/foo/baz/../*/bar/*', '/foo/baz/../'),
            array('/foo/baz/bar*', '/foo/baz/bar'),
        );
    }

    /**
     * @dataProvider provideBasePaths
     */
    public function testGetBasePath($glob, $basePath)
    {
        $this->assertSame($basePath, Glob::getBasePath($glob));
    }

    public function provideBasePaths()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/foo/baz/../*/bar/*', '/foo/baz/..'),
            array('/foo/baz/bar*', '/foo/baz'),
            array('/foo/baz/bar', '/foo/baz'),
            array('/foo/baz*', '/foo'),
            array('/foo*', '/'),
            array('/*', '/'),
            array('foo*/baz/bar', ''),
            array('foo*', ''),
            array('*', ''),
        );
    }

    /**
     * @dataProvider provideMatches
     */
    public function testMatch($path, $isMatch)
    {
        $this->assertSame((bool) $isMatch, Glob::match($path, '/foo/*.js~'));
    }

    public function testMatchPathWithoutWildcard()
    {
        $this->assertTrue(Glob::match('/foo/bar.js~', '/foo/bar.js~'));
        $this->assertFalse(Glob::match('/foo/bar.js', '/foo/bar.js~'));
    }

    public function testFilter()
    {
        $paths = array();
        $filtered = array();

        // The keys remain the same in the filtered array
        $i = 0;

        foreach ($this->provideMatches() as $input) {
            $paths[$i] = $input[0];

            if ($input[1]) {
                $filtered[$i] = $input[0];
            }

            ++$i;
        }

        $this->assertSame($filtered, Glob::filter($paths, '/foo/*.js~'));
    }

    public function testFilterWithoutWildcard()
    {
        $paths = array(
            '/foo',
            '/foo/bar.js',
        );

        $this->assertSame(array('/foo/bar.js'), Glob::filter($paths, '/foo/bar.js'));
        $this->assertSame(array(), Glob::filter($paths, '/foo/bar.js~'));
    }
}

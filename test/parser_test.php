<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'parser.php');
    Mock::generate("HtmlPage");
    Mock::generate("TokenHandler");

    class TestOfParallelRegex extends UnitTestCase {
        function TestOfParallelRegex() {
            $this->UnitTestCase();
        }
        function testNoPatterns() {
            $regex = &new ParallelRegex();
            $this->assertFalse($regex->match("Hello", $match));
            $this->assertEqual($match, "");
        }
        function testNoSubject() {
            $regex = &new ParallelRegex();
            $regex->addPattern(".*");
            $this->assertTrue($regex->match("", $match));
            $this->assertEqual($match, "");
        }
        function testMatchAll() {
            $regex = &new ParallelRegex();
            $regex->addPattern(".*");
            $this->assertTrue($regex->match("Hello", $match));
            $this->assertEqual($match, "Hello");
        }
        function testMatchMultiple() {
            $regex = &new ParallelRegex();
            $regex->addPattern("abc");
            $regex->addPattern("ABC");
            $this->assertTrue($regex->match("abcdef", $match));
            $this->assertEqual($match, "abc");
            $this->assertTrue($regex->match("AAABCabcdef", $match));
            $this->assertEqual($match, "ABC");
            $this->assertFalse($regex->match("Hello", $match));
        }
        function testPatternLabels() {
            $regex = &new ParallelRegex();
            $regex->addPattern("abc", "letter");
            $regex->addPattern("123", "number");
            $this->assertIdentical($regex->match("abcdef", $match), "letter");
            $this->assertEqual($match, "abc");
            $this->assertIdentical($regex->match("0123456789", $match), "number");
            $this->assertEqual($match, "123");
        }
    }
    
    class TestOfStateStack extends UnitTestCase {
        function TestOfStateStack() {
            $this->UnitTestCase();
        }
        function testStartState() {
            $stack = &new StateStack("one");
            $this->assertEqual($stack->getCurrent(), "one");
        }
        function testExhaustion() {
            $stack = &new StateStack("one");
            $this->assertFalse($stack->leave());
        }
        function testStateMoves() {
            $stack = &new StateStack("one");
            $stack->enter("two");
            $this->assertEqual($stack->getCurrent(), "two");
            $stack->enter("three");
            $this->assertEqual($stack->getCurrent(), "three");
            $this->assertTrue($stack->leave());
            $this->assertEqual($stack->getCurrent(), "two");
            $stack->enter("third");
            $this->assertEqual($stack->getCurrent(), "third");
            $this->assertTrue($stack->leave());
            $this->assertTrue($stack->leave());
            $this->assertEqual($stack->getCurrent(), "one");
        }
    }
    
    class TestParser {
        function TestParser() {
        }
        function accept() {
        }
    }
    Mock::generate('TestParser');

    class TestOfLexer extends UnitTestCase {
        function TestOfLexer() {
            $this->UnitTestCase();
        }
        function testNoPatterns() {
            $handler = &new MockTestParser($this);
            $handler->expectMaximumCallCount("accept", 0);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $this->assertFalse($lexer->parse("abcdef"));
        }
        function testEmptyPage() {
            $handler = &new MockTestParser($this);
            $handler->expectMaximumCallCount("accept", 0);
            $handler->setReturnValue("accept", true);
            $handler->expectMaximumCallCount("accept", 0);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $this->assertTrue($lexer->parse(""));
        }
        function testSinglePattern() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsSequence(0, "accept", array("aaa", true));
            $handler->expectArgumentsSequence(1, "accept", array("x", false));
            $handler->expectArgumentsSequence(2, "accept", array("a", true));
            $handler->expectArgumentsSequence(3, "accept", array("yyy", false));
            $handler->expectArgumentsSequence(4, "accept", array("a", true));
            $handler->expectArgumentsSequence(5, "accept", array("x", false));
            $handler->expectArgumentsSequence(6, "accept", array("aaa", true));
            $handler->expectArgumentsSequence(7, "accept", array("z", false));
            $handler->expectCallCount("accept", 8);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $this->assertTrue($lexer->parse("aaaxayyyaxaaaz"));
            $handler->tally();
        }
        function testMultiplePattern() {
            $handler = &new MockTestParser($this);
            $target = array("a", "b", "a", "bb", "x", "b", "a", "xxxxxx", "a", "x");
            for ($i = 0; $i < count($target); $i++) {
                $handler->expectArgumentsSequence($i, "accept", array($target[$i], '*'));
            }
            $handler->expectCallCount("accept", count($target));
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $lexer->addPattern("b+");
            $this->assertTrue($lexer->parse("ababbxbaxxxxxxax"));
            $handler->tally();
        }
    }

    class TestOfLexerModes extends UnitTestCase {
        function TestOfLexerModes() {
            $this->UnitTestCase();
        }
        function testIsolatedPattern() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsSequence(0, "accept", array("a", true));
            $handler->expectArgumentsSequence(1, "accept", array("b", false));
            $handler->expectArgumentsSequence(2, "accept", array("aa", true));
            $handler->expectArgumentsSequence(3, "accept", array("bxb", false));
            $handler->expectArgumentsSequence(4, "accept", array("aaa", true));
            $handler->expectArgumentsSequence(5, "accept", array("x", false));
            $handler->expectArgumentsSequence(6, "accept", array("aaaa", true));
            $handler->expectArgumentsSequence(7, "accept", array("x", false));
            $handler->expectCallCount("accept", 8);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler, "used");
            $lexer->addPattern("a+", "used");
            $lexer->addPattern("b+", "unused");
            $this->assertTrue($lexer->parse("abaabxbaaaxaaaax"));
            $handler->tally();
        }
        function testModeChange() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsSequence(0, "accept", array("a", true));
            $handler->expectArgumentsSequence(1, "accept", array("b", false));
            $handler->expectArgumentsSequence(2, "accept", array("aa", true));
            $handler->expectArgumentsSequence(3, "accept", array("b", false));
            $handler->expectArgumentsSequence(4, "accept", array("aaa", true));
            $handler->expectArgumentsSequence(5, "accept", array(":", true));
            $handler->expectArgumentsSequence(6, "accept", array("a", false));
            $handler->expectArgumentsSequence(7, "accept", array("b", true));
            $handler->expectArgumentsSequence(8, "accept", array("a", false));
            $handler->expectArgumentsSequence(9, "accept", array("bb", true));
            $handler->expectArgumentsSequence(10, "accept", array("a", false));
            $handler->expectArgumentsSequence(11, "accept", array("bbb", true));
            $handler->expectArgumentsSequence(12, "accept", array("a", false));
            $handler->expectCallCount("accept", 13);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern(":", "a", "b");
            $lexer->addPattern("b+", "b");
            $this->assertTrue($lexer->parse("abaabaaa:ababbabbba"));
            $handler->tally();
        }
        function testNesting() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("accept", true);
            $handler->expectArgumentsSequence(0, "accept", array("aa", true));
            $handler->expectArgumentsSequence(1, "accept", array("b", false));
            $handler->expectArgumentsSequence(2, "accept", array("aa", true));
            $handler->expectArgumentsSequence(3, "accept", array("b", false));
            $handler->expectArgumentsSequence(4, "accept", array("(", true));
            $handler->expectArgumentsSequence(5, "accept", array("bb", true));
            $handler->expectArgumentsSequence(6, "accept", array("a", false));
            $handler->expectArgumentsSequence(7, "accept", array("bb", true));
            $handler->expectArgumentsSequence(8, "accept", array(")", true));
            $handler->expectArgumentsSequence(9, "accept", array("aa", true));
            $handler->expectArgumentsSequence(10, "accept", array("b", false));
            $handler->expectCallCount("accept", 11);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern("(", "a", "b");
            $lexer->addPattern("b+", "b");
            $lexer->addExitPattern(")", "b");
            $this->assertTrue($lexer->parse("aabaab(bbabb)aab"));
            $handler->tally();
        }
        function testUnwindTooFar() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("accept", true);
            $handler->expectArgumentsSequence(0, "accept", array("aa", true));
            $handler->expectCallCount("accept", 1);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addExitPattern(")", "a");
            $this->assertFalse($lexer->parse("aa)aa"));
            $handler->tally();
        }
    }
    
    class TestOfHtmlLexer extends UnitTestCase {
        function TestOfHtmlLexer() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleHtmlLexer($handler, "html");
            $this->assertTrue($lexer->parse("<html><head></head><body></body></html>"));
            $handler->tally();
        }
    }
    
    class TestOfParser extends UnitTestCase {
        function TestOfParser() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $page = &new MockHtmlPage($this);
            $page->expectCallCount("addLink", 0);
            $page->expectCallCount("addFormElement", 0);
            $parser = &new HtmlParser();
            $this->assertTrue($parser->parse("", $page));
            $page->tally();
        }
    }
?>
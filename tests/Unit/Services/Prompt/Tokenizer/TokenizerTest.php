<?php

namespace Tests\Unit\Services\Prompt\Learning;

use PHPUnit\Framework\TestCase;
use App\Services\Ai\Prompt\Tokenizer\PromptTokenizer;

class PromptTokenizerTest extends TestCase {

    const TEST_PROMPT = "Create a function and loop through an array of numbers as long as and return the total. Also, make sure to handle special characters. as if you had even did something till the end of the line.";
    

    public function test_detokenize_string(): void
    {
        $tokenizer = new PromptTokenizer();
        $tokenizer->addWords(self::TEST_PROMPT);
        $tokens = $tokenizer->tokenize(self::TEST_PROMPT);
        $detokenized = $tokenizer->toString($tokens);
        $this->assertEquals(self::TEST_PROMPT, $detokenized);
    }
/*
    public function test_tokenizer_2_performance(): void
    {
        // get the start time
        $start = microtime(true);
        $tokenizer = new PromptTokenizer();
        $tokens = $tokenizer->tokenize2(self::TEST_PROMPT);
        var_dump($tokens);
        // get the end time
        $end = microtime(true);
        // calculate the difference
        $diff = $end - $start;
        // print the difference
        echo "tokenize() took $diff seconds\n";
    }
*/
}
<?php
/**
 *  Class converts text into numeric token hash map for processing 
 *  by the GPT model in order to reduce the total number of tokens
 *  sent per request/response to the GPT model. Also provides method 
 *  to convert the numeric token hash map back into text.
 */

 namespace App\Services\Ai\Prompt\Tokenizer;

 class PromptTokenizer {

    private $words = [];

    const TOKENS = [
        '!' => '1',
        '@' => '2',
        '#' => '3',
        '$' => '4',
        '%' => '5',
        '^' => '6',
        '&' => '7',
        '*' => '8',
        '(' => '9',
        ')' => '10',
        '0' => 'I',
        '1' => 'II',
        '2' => 'III',
        '3' => 'IV',
        '4' => 'V',
        '5' => 'VI',
        '6' => 'VII',
        '7' => 'VIII',
        '8' => 'IX',
        '9' => 'X',
        'and' => '11',
        'or' => '12',
        'but' => '13',
        'nor' => '14',
        'so' => '15',
        'for' => '16',
        'yet' => '17',
        'after' => '18',
        'although' => '19',
        'because' => '20',
        ' ' => ' '
    ];
    

    public function addWords(string $words): void {
        $words = explode(' ', $words);
        $totalWords = count($this->words) + count(self::TOKENS);
        // get last element of array
        foreach ($words as $index => $word) {
            $index = $totalWords + $index + 1;
            if (!isset($this->words[$word]) && !isset(self::TOKENS[$word])) {
                $this->words[$word] = $index;
            }
        }
    }

    /**
     *  Converts text into numeric token hash map for processing 
     *  by the GPT model in order to reduce the total number of tokens
     *  sent per request/response to the GPT model.
     * 
     *  @param string $text
     *  @return array
     */
    public function tokenize(string $text): string {
        $tokens = [];
        $textLength = strlen($text);
    

        // sort by token length (from longest to shortest)
        $combinedTokens = array_merge(
            self::TOKENS,
            $this->words
        );

        uksort($combinedTokens, function($a, $b) {
            return strlen($b) - strlen($a);
        });
    
        // Create a hash map of first characters to optimize matching
        $firstCharMap = [];
        foreach ($combinedTokens as $string => $value) {
            $firstChar = $string[0];
            if (!isset($firstCharMap[$firstChar])) {
                $firstCharMap[$firstChar] = [];
            }
            $firstCharMap[$firstChar][] = $string;
        }
    
        // Iterate through the input text
        for ($i = 0; $i < $textLength; $i++) {
            $char = $text[$i];
    
            // Check if the character exists in the firstCharMap
            if (isset($firstCharMap[$char])) {
                // Attempt to match the longest token
                foreach ($firstCharMap[$char] as $candidateToken) {
                    if (substr($text, $i, strlen($candidateToken)) === $candidateToken) {
                        // Add the corresponding numeric token
                        $tokens[] = $combinedTokens[$candidateToken];

                        // Skip the characters that were matched
                        $i += strlen($candidateToken) - 1;
                        // Move to the next character in the input text
                        continue 2;
                    }
                }
            }
    
            // If no match found, add the character as is
            $tokens[] = $char;
        }
    
        return implode('', $tokens);
    }

    /**
     *  Converts the numeric token hash map back into text.
     * 
     *  @param string $tokenizedString
     *  @return string
     */
    public function toString(string $tokenizedString): string {
        // Merge the special characters and parts of speech into a combined tokens array
        $combinedTokens = array_merge(self::TOKENS, $this->words);

        // Create a reverse mapping for the combined tokens
        $reverseTokens = array_flip($combinedTokens);

        // Split the tokenized string into an array of tokens
        $tokens = explode(' ', $tokenizedString);

        // Iterate through the tokens and replace them with their corresponding strings
        $text = '';
        foreach ($tokens as $token) {
            // Check if the token exists in the reverseTokens array
            if (isset($reverseTokens[$token])) {
                // Add the corresponding string
                $text .= ($text ? ' ' : '') .$reverseTokens[$token];
            } else {
                // If no match found, add the token as is (could be a character not in the mapping)
                $text .= $token;
            }
        }

        return $text;
    }


    public function tokenize2(string $text): array {
        $tokens = [];
        
        // Combine both arrays
        $combinedTokens = array_merge($this->SPECIAL_CHARACTERS_TOKENS, $this->PARTS_OF_SPEECH);
    
        // Create a pattern that matches any of the tokens
        $pattern = '/\b(?:' . implode('|', array_map('preg_quote', array_keys($combinedTokens))) . ')\b/';
        
        // Replace each match with the corresponding token
        $text = preg_replace_callback($pattern, function($matches) use ($combinedTokens) {
            return $combinedTokens[$matches[0]];
        }, $text);
    
        // Convert to array of characters (or tokens)
        $tokens = str_split($text);
    
        return $tokens;
    }
 }
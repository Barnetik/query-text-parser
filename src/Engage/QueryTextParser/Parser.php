<?php namespace Engage\QueryTextParser;

use Engage\QueryTextParser\Data\Group;
use Engage\QueryTextParser\Data\GroupComparison;
use Engage\QueryTextParser\Data\Partial;

use dbeurive\Shuntingyard\ShuntingYard;

class Parser
{
    const TYPE_OPERATOR = 'OPERATOR';
    const TYPE_QUOTED_STRING = 'QUOTED_STRING';
    const TYPE_STRING = 'STRING';
    const TYPE_NEGATED_STRING = 'NEGATED_STRING';
    const TYPE_NEGATED_QUOTED_STRING = 'NEGATED_QUOTED_STRING';
    const TYPE_PARAM_SEPARATOR = ','; //Not used
    const TYPE_OPEN_BRACKET = 'OPEN_BRACKET';
    const TYPE_CLOSE_BRACKET = 'CLOSE_BRACKET';
    const TYPE_SPACE = 'SPACE';

    private $precedences = [
        'OR' => 1,
        'AND' => 2,
        'ADJ' => 3,
        'NEAR' => 3,
    ];

    private $associativities = [
        'OR' => ShuntingYard::ASSOC_LEFT,
        'AND' => ShuntingYard::ASSOC_LEFT,
        'ADJ' => ShuntingYard::ASSOC_LEFT,
        'NEAR' => ShuntingYard::ASSOC_LEFT,
    ];

    private $tokens;

    private $parser;

    public $error = '';

    public function __construct()
    {
        $this->tokens = [
            ["/(OR|AND|ADJ|NEAR)/", self::TYPE_OPERATOR],
            ["/-\"(.+?)\"/u", self::TYPE_NEGATED_QUOTED_STRING, function(array $m) {
                return $m[1];
            }],
            ["/-([]\w\*]+)/u", self::TYPE_NEGATED_STRING, function(array $m) {
                return $m[1];
            }],
            ["/\"(.+?)\"/u", self::TYPE_QUOTED_STRING, function(array $m) {
                return $m[1];
            }],
            ["/[\w\*]+/u", self::TYPE_STRING],
            ["/,/", self::TYPE_PARAM_SEPARATOR], //Not used
            ["/\(/", self::TYPE_OPEN_BRACKET],
            ["/\)/", self::TYPE_CLOSE_BRACKET],
            ["/\s+/", self::TYPE_STRING, function(array $m) {
                return null;
            }],
        ];

        $this->parser = new ShuntingYard(
            $this->tokens,
            $this->precedences,
            $this->associativities,
            [self::TYPE_NEGATED_QUOTED_STRING, self::TYPE_NEGATED_STRING,self::TYPE_QUOTED_STRING, self::TYPE_STRING],
            [],
            [self::TYPE_OPERATOR],
            self::TYPE_PARAM_SEPARATOR,
            self::TYPE_OPEN_BRACKET,
            self::TYPE_CLOSE_BRACKET
        );
    }

    public function parse($query) {
       $lexer = new \dbeurive\Lexer\Lexer($this->tokens);
       $queryTokens = $this->fixTokens($lexer->lex($query));
       $error = '';
       $parsedQuery = $this->parser->convertFromTokens($queryTokens, $query, $this->error);
       return $this->buildTree($parsedQuery);
    }

    /**
    * Adds AND operators as default between strings
    */
    private function fixTokens($queryTokens)
    {

        $last = null;
        $finalTokens = [];
        foreach($queryTokens as $token) {
            if ($last && ($this->isString($last) || $last->type === self::TYPE_CLOSE_BRACKET)) {
                if ($this->isString($token) || $token->type === self::TYPE_OPEN_BRACKET) {
                    $andToken = new \dbeurive\Lexer\Token('AND', self::TYPE_OPERATOR);
                    $finalTokens[] = $andToken;
                }
            }
            $finalTokens[] = $token;
            $last = $token;
        }
        return $finalTokens;
    }

    private function isString($token)
    {
        return $token->type === self::TYPE_STRING || $token->type === self::TYPE_QUOTED_STRING || $this->isNegatedString($token);
    }

    private function isNegatedString($token)
    {
        return $token->type === self::TYPE_NEGATED_STRING || $token->type === self::TYPE_NEGATED_QUOTED_STRING;
    }

    private function isOperator($token)
    {
        return $token->type === self::TYPE_OPERATOR;
    }

    private function buildTree($parsedQuery)
    {
        $tree = [];
        foreach ($parsedQuery as $token) {
            if ($this->isString($token)) {
                $partial = new Data\Partial();
                $partial->text = $token->value;
                $partial->negate = (bool)$this->isNegatedString($token);
                array_push($tree, $partial);
            } else if ($this->isOperator($token)) {
                $group = new Data\Group();
                $group->type = $token->value;
                $secondChild = array_pop($tree);
                $firstChild = array_pop($tree);
                $group->children = [$firstChild, $secondChild];
                array_push($tree, $group);
            }
        }
        $finalTree = array_pop($tree);
        if ($finalTree instanceof Data\Partial)  {
            $group = new Data\Group();
            $group->children[] = $finalTree;
            return $group;
        }
        return $finalTree;
    }
}

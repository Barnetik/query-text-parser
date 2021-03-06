<?php namespace Engage\QueryTextParser\Test;

use Engage\QueryTextParser\Parser;
use Engage\QueryTextParser\Exceptions\ParserException;
use Engage\QueryTextParser\Data\GroupComparison;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
	private $parser;

	public function setUp(): void {
		$this->parser = new Parser();
	}

	public function testSimpleQuery() {
		try {
			$result = $this->parser->parse('Chicago');
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);

			$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);
			$this->assertCount(1, $result->children);

			$this->assertEquals($result->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->negate, false);
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

	public function testSimpleAnd() {
		try {
			$result = $this->parser->parse('Chicago AND Houston');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->negate, false);

			$this->assertEquals($result->children[1]->text, 'Houston');
			$this->assertEquals($result->children[1]->negate, false);
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

	public function testUnexpectedWordCharactersThrowException()
	{
		$this->expectException(ParserException::class);

		// Colon is not an expected word character
		$this->parser->parse('id:Chicago AND Houston');
	}

	public function testConfigurableWordCharactersWork()
	{
		$parser = new Parser("\w\*@#\.,\|#~%$&\/\\{\}\*\?\¿_\+\[\]<>:");
		// Colon is not an expected word character
		$result = $parser->parse('id:Chicago AND Houston');

		// Verify consistency of group
		$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
		$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);

		$this->assertCount(2, $result->children);

		// Verify consistency of children
		$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
		$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

		$this->assertEquals($result->children[0]->text, 'id:Chicago');
		$this->assertEquals($result->children[0]->negate, false);

		$this->assertEquals($result->children[1]->text, 'Houston');
		$this->assertEquals($result->children[1]->negate, false);
	}

	public function testHyphensAreAllowedInsideWords()
	{
		$parser = new Parser();
		// Colon is not an expected word character
		$result = $parser->parse('Markina-Xemein AND Houston');

		// Verify consistency of group
		$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
		$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);

		$this->assertCount(2, $result->children);

		// Verify consistency of children
		$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
		$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

		$this->assertEquals($result->children[0]->text, 'Markina-Xemein');
		$this->assertEquals($result->children[0]->negate, false);

		$this->assertEquals($result->children[1]->text, 'Houston');
		$this->assertEquals($result->children[1]->negate, false);
	}

	public function testNegatedPartials() {
		try {
			$result = $this->parser->parse('Chicago AND -Houston');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->negate, false);

			$this->assertEquals($result->children[1]->text, 'Houston');
			$this->assertEquals($result->children[1]->negate, true);
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

    public function testMultipleAndsGenerateDeeperTree() {
		try {
			$result = $this->parser->parse('Chicago AND -Houston AND Alabama');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->type, 'AND');

			$this->assertEquals($result->children[0]->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->children[0]->negate, false);
			$this->assertEquals($result->children[0]->children[1]->text, 'Houston');
			$this->assertEquals($result->children[0]->children[1]->negate, true);

			$this->assertEquals($result->children[1]->text, 'Alabama');
			$this->assertEquals($result->children[1]->negate, false);

		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

	public function testNoOperandEqualsAnd() {
		try {
			$result = $this->parser->parse('Chicago Houston Alabama');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->type, 'AND');

			$this->assertEquals($result->children[0]->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->children[1]->text, 'Houston');

			$this->assertEquals($result->children[1]->text, 'Alabama');
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

    public function testSimpleOr() {
		try {
			$result = $this->parser->parse('Chicago OR Houston');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_OR);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->negate, false);

			$this->assertEquals($result->children[1]->text, 'Houston');
			$this->assertEquals($result->children[1]->negate, false);
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

    public function testGroups() {
		try {
			$result = $this->parser->parse('(Chicago AND -Houston) OR Phoenix');

			// Verify consistency of outer group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_OR);
			$this->assertCount(2, $result->children);

			// Verify consistency of left group (foo AND bar)
			$leftSide = $result->children[0];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $leftSide);
			$this->assertEquals($leftSide->type, GroupComparison::OPERATOR_AND);
			$this->assertCount(2, $leftSide->children);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $leftSide->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $leftSide->children[1]);

			$this->assertEquals($leftSide->children[0]->text, 'Chicago');
			$this->assertEquals($leftSide->children[0]->negate, false);

			$this->assertEquals($leftSide->children[1]->text, 'Houston');
			$this->assertEquals($leftSide->children[1]->negate, true);

			// Verify consistency of right group (abc)
			$rightSide = $result->children[1];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $rightSide);

			$this->assertEquals($rightSide->text, 'Phoenix');
			$this->assertEquals($rightSide->negate, false);

		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

    public function testComplex() {
		try {
			$result = $this->parser->parse('(-Chicago AND Houston OR (Dallas AND -"Las Vegas" AND Columbus)) OR ((Phoenix OR Detroit) AND Charlotte ADJ Michigan)');

			// Verify consistency of outer group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals(GroupComparison::OPERATOR_OR, $result->type);
			$this->assertCount(2, $result->children);

			// Left side -- "Chicago AND Houston OR (Dallas AND Austin AND Columbus)"
			$leftSide = $result->children[0];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $leftSide);
			$this->assertEquals(GroupComparison::OPERATOR_OR, $leftSide->type);

			// Chicago AND Houston
			$leftLeftSide = $leftSide->children[0];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $leftLeftSide);
			$this->assertEquals(GroupComparison::OPERATOR_AND, $leftLeftSide->type);
			$this->assertEquals($leftLeftSide->children[0]->text, 'Chicago');
			$this->assertEquals($leftLeftSide->children[0]->negate, true);
			$this->assertEquals($leftLeftSide->children[1]->text, 'Houston');
			$this->assertEquals($leftLeftSide->children[1]->negate, false);

			// Dallas AND Austin AND Columbus
			$rightLeftSide = $leftSide->children[1];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $rightLeftSide);
			$this->assertEquals(GroupComparison::OPERATOR_AND, $rightLeftSide->type);
			$this->assertEquals($rightLeftSide->children[0]->type, GroupComparison::OPERATOR_AND);
			$this->assertEquals($rightLeftSide->children[1]->text, 'Columbus');
			$this->assertEquals($rightLeftSide->children[1]->negate, false);
			$this->assertEquals($rightLeftSide->children[0]->children[0]->text, 'Dallas');
			$this->assertEquals($rightLeftSide->children[0]->children[0]->negate, false);
			$this->assertEquals($rightLeftSide->children[0]->children[1]->text, 'Las Vegas');
			$this->assertEquals($rightLeftSide->children[0]->children[1]->negate, true);

			// Right side -- "(Phoenix OR Detroit) AND Charlotte"
			$rightSide = $result->children[1];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $rightSide);
			$this->assertEquals(GroupComparison::OPERATOR_AND, $rightSide->type);

			// Phoeneix OR Detroit
			$leftRightSide = $rightSide->children[0];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $leftRightSide);
			$this->assertEquals(GroupComparison::OPERATOR_OR, $leftRightSide->type);
			$this->assertEquals($leftRightSide->children[0]->text, 'Phoenix');
			$this->assertEquals($leftRightSide->children[1]->text, 'Detroit');

			// Charlotte
			$rightRightSide = $rightSide->children[1];
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $rightRightSide);
			// $this->assertEquals($rightRightSide->text, 'Charlotte');
			$this->assertEquals(GroupComparison::OPERATOR_ADJ, $rightRightSide->type);
			$this->assertEquals($rightRightSide->children[0]->text, 'Charlotte');
			$this->assertEquals($rightRightSide->children[1]->text, 'Michigan');


		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

    public function testQuotes() {
		try {
			$result = $this->parser->parse('"New York" AND "San Francisco"');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_AND);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->text, 'New York');
			$this->assertEquals($result->children[0]->negate, false);

			$this->assertEquals($result->children[1]->text, 'San Francisco');
			$this->assertEquals($result->children[1]->negate, false);
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

	public function testSimpleNear() {
		try {
			$result = $this->parser->parse('Chicago NEAR Houston');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_NEAR);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->negate, false);

			$this->assertEquals($result->children[1]->text, 'Houston');
			$this->assertEquals($result->children[1]->negate, false);
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }


	public function testSimpleAdj() {
		try {
			$result = $this->parser->parse('Chicago ADJ Houston');

			// Verify consistency of group
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Group', $result);
			$this->assertEquals($result->type, GroupComparison::OPERATOR_ADJ);

			$this->assertCount(2, $result->children);

			// Verify consistency of children
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[0]);
			$this->assertInstanceOf('Engage\QueryTextParser\Data\Partial', $result->children[1]);

			$this->assertEquals($result->children[0]->text, 'Chicago');
			$this->assertEquals($result->children[0]->negate, false);

			$this->assertEquals($result->children[1]->text, 'Houston');
			$this->assertEquals($result->children[1]->negate, false);
		} catch (ParserException $e) {
			echo 'Parse Error: ' . $e->getMessage();
		}
    }

}

<?php
namespace Phpc;

/**
 * OMeta Base class provides basic functionality.
 *
 * This is the OMeta Base class providing implementations of the fundamental OMeta operations.
 * Built-in rules are defined here.
 */
class OMetaBase
{
    /*public $dictionary = array();

    /**
     * @param $string The string to be parsed.
     * @param $dictionary A dictionary of names to objects, for use in evaluating embedded PHP expressions.
     */
   /*public function __construct($string, $dictionary)
    {
        $this->input = (new InputStream())->fromIterable($string);
        $this->dictionary = $dictionary;
    }*/

  public function _apply($rule)
  {
    $memoRec = $this->input->memo[$rule];
    if ($memoRec == null) {
      $origInput = $this->input;
      $leftRecursion = new LeftRecursion();
      if ($this[$rule] === null) {
        throw new Exception(sprintf('No rule named %s.', $rule));
      }
      $this->input->memo[$rule] = $leftRecursion;
      $this->input->memo[$rule] = $memoRec = array('ans' => $this[$rule](), "nextInput" => $this->input);
      if ($leftRecursion->used) {
        $sentinel = $this->input;
        while (true) {
          try {
            $this->input = $origInput;
            $ans = $this[$rule]();
            if ($this->input == $sentinel) {
              throw new ParseError('ParseError: ');
            }
            $memoRec->ans = $ans;
            $memoRec->nextInput = $this->input;
          } catch (Exception $e) {
            if ($e != LeftRecursion)
              throw $e;
            break;
          }
        }
      }
    } else {
      if ($memoRec instanceof LeftRecursion) {
        $memoRec->used = true;
        throw new fail;
      }
    }
    $this->input = $memoRec->nextInput;

    return $memoRec->ans;
  }

  /**
   * Note: _applyWithArgs and _superApplyWithArgs are not memoized,
   *       so they can't be left-recursive!
   */
  public function _applyWithArgs($rule, $arguments)
  {
    $ruleFn = $this[$rule];
    $ruleFnArity = strlen($ruleFn);
    for ($idx = func_num_args() - 1; $idx >= $ruleFnArity + 1; $idx--) {
      // prepend "extra" arguments in reverse order
      $this->_prependInput($arguments[$idx]);
    }

    return ($ruleFnArity == 0) ?
             $ruleFn() :
             $ruleFn->apply(
                 $this,
                 $todo /*Array.prototype.slice.call($arguments, 1, $ruleFnArity + 1)*/
             );
  }

  public function _superApplyWithArgs($recv, $rule)
  {
    $ruleFn = $this[$rule];
    $ruleFnArity = strlen($ruleFn);
    for ($idx = func_num_args() - 1; $idx > $ruleFnArity + 2; $idx--) {
      // prepend "extra" arguments in reverse order
      $recv->_prependInput($arguments[$idx]);
    }

    return ($ruleFnArity == 0) ?
             $ruleFn->call($recv) :
             $ruleFn->apply(
                 $recv,
                 $todo /*Array.prototype.slice.call($arguments, 2, $ruleFnArity + 2)*/
             );
  }

  public function _prependInput($v)
  {
    $this->input = new OMInputStream($v, $this->input);
  }

  // if you want your grammar (and its subgrammars) to memoize parameterized rules, invoke this method on it:
  public function memoizeParameterizedRules()
  {
    /*$this->_prependInput = function($v) {
      $newInput = '';
      if (isImmutable($v)) {
        $newInput = $this->input[getTag($v)];
        if (!$newInput) {
          $newInput = new OMInputStream($v, $this->input);
          $this->input[getTag($v)] = $newInput;
        }
      } else {
          $newInput = new OMInputStream($v, $this->input);
      }
      $this->input = $newInput;
    }*/
  }

  public function _pred($b)
  {
    if ($b) {
      return true;
    }
    throw new FailException('Fail');
  }

  public function _not($x)
  {
    $origInput = $this->input;
    try {
        $x();
    } catch (Exception $e) {
        if ($e != Failure) {
          throw new $e;
        }
      }
    $this->input = $origInput;
    //return true;
    //throw new ParseError('ParseError: ');
  }

  public function _lookahead($x)
  {
    $origInput = $this->input;
    $rule = $x();
    $this->input = $origInput;

    return $rule;
  }

  public function _or()
  {
    $arguments = func_get_args();
    $origInput = $this->input;
    for ($idx = 0; $idx < func_num_args(); $idx++)
      try {
        $this->input = $origInput;

        return $arguments[$idx]();
      } catch (Exception $e) {
        if ($e != Failure) {
          throw $e;
        }
      }
    throw new ParseError('ParseError: ');
  }

  public function _xor($ruleName)
  {
    $origInput = $this->input;
    $idx = 1;
    $newInput = '';
    $ans = '';

    while ($idx < func_num_args()) {
      try {
        $this->input = $origInput;
        $arg = func_get_arg($idx);
        $ans = $arg();
        if ($newInput) {
          throw new Exception('More than one choice matched by "exclusive-OR" in ' . $ruleName);
        }
        $newInput = $this->input;
      } catch (Exception $e) {
        if ($e != Failure) {
          throw $e;
        }
      }
      $idx++;
    }
    if ($newInput) {
      $this->input = $newInput;

      return $ans;
    } else {
      throw new ParseError('ParseError: ');
    }
  }

  public function disableXORs()
  {
    $this->_xor = $this->_or;
  }

  public function _opt($x)
  {
    $origInput = $this->input;
    $ans = '';
    try {
        $ans = $x();
    } catch (Exception $e) {
      if ($e != Failure) {
         throw $e;
      }
      $this->input = $origInput;
    }

    return $ans;
  }

  public function _many($x)
  {
    $ans = (func_get_arg(1) != undefined) ? [func_get_arg(1)] : [];
    while (true) {
      $origInput = $this->input;
      try {
          $ans->push( $x() );
      } catch (Exception $e) {
        if ($e != Failure) {
          throw $e;
        }
        $this->input = $origInput;
        break;
      }
    }

    return $ans;
  }

  public function _many1($x)
  {
    return $this->_many($x, $x());
  }

  public function _form($x)
  {
    $v = $this->_apply("rule_anything");
    if (!isSequenceable($v)) {
      throw new ParseError('ParseError: ');
    }
    $origInput = $this->input;
    $this->input = $v->toOMInputStream();
    $rule = $x();
    $this->_apply("rule_end");
    $this->input = $origInput;

    return $v;
  }

  public function _consumedBy($x)
  {
    $origInput = $this->input;
    $x();

    return $origInput->upTo($this->input);
  }

  public function _idxConsumedBy($x)
  {
    $origInput = $this->input;
    $x();

    return array('fromIdx' => $origInput->idx, 'toIdx' => $this->input->idx);
  }

  public function _interleave($mode1, $part1, $mode2, $part2) /* ..., moden, partn */
  {
    $arguments = func_get_args();
    $currInput = $this->input;
    $ans = [];
    for ($idx = 0; $idx < func_num_args(); $idx += 2) {
      $ans[$idx / 2] = ($arguments[$idx] == "*" || $arguments[$idx] == "+") ? [] : null;
    }
    while (true) {
      $idx = 0; $allDone = true;
      while ($idx < func_num_args()) {
        if ($arguments[$idx] != "0")
          try {
            $this->input = $currInput;
            switch ($arguments[$idx]) {
              case "*": $ans[$idx / 2]->push($arguments[$idx + 1]()); break;
              case "+": $ans[$idx / 2]->push($arguments[$idx + 1]()); $arguments[$idx] = "*"; break;
              case "?": $ans[$idx / 2] = $arguments[$idx + 1](); $arguments[$idx] = "0"; break;
              case "1": $ans[$idx / 2] = $arguments[$idx + 1](); $arguments[$idx] = "0"; break;
              default: throw new Exception('Invalid mode ' . $arguments[$idx] . ' in OMeta->_interleave');
            }
            $currInput = $this->input;
            break;
          } catch (Exception $e) {
            if ($e != Failure) {
              throw $e;
            }
          }
            // if this (failed) part's mode is "1" or "+", we're not done yet
            $allDone = $allDone && ($arguments[$idx] == "*" || $arguments[$idx] == "?");
          }
        $idx += 2;
      }
      if ($idx == func_num_args()) {
        if ($allDone) {
          return $ans;
        } else {
          throw new ParseError('ParseError: ');
      }
    }
  }

  public function _currIdx()
  {
      return $this->input->idx;
  }

  /**
   * ================
   * some basic rules
   * ================
   */

  /**
   * Match a single item from the input of any kind.
   */
  public function rule_anything()
  {
    $rule = $this->input->head();
    $this->input = $this->input->tail();

    return $rule;
  }

  public function rule_end()
  {
    return $this->_not(function() {
      return $this->_apply("rule_anything"); }
    );
  }

  public function pos()
  {
    return $this->input->idx;
  }

  public function rule_empty()
  {
    return true;
  }

  public function apply($rule)
  {
    return $this->_apply($rule);
  }

  public function rule_foreign($grammar, $rule)
  {
    $grammar_instace = objectThatDelegatesTo($grammar, array( 'input' => makeOMInputStreamProxy($this->input)));
    $ans = $grammar_instance->_apply($rule);
    $this->input = $grammar_instance->input->target;

    return $ans;
  }

  /**
   * ===========================
   * some useful "derived" rules
   * ===========================
   */

  /**
   * Match a single item from the input equal to the given specimen.
   * @param $wanted What to match.
   */
  public function rule_exactly($wanted)
  {
    if ($wanted === $this->_apply("rule_anything")) {
      return $wanted;
    }
    throw new ParseError('ParseError: ');
  }

  public function rule_true()
  {
    $rule = $this->_apply("rule_anything");
    $this->_pred($rule === true);

    return $rule;
  }

  public function rule_false()
  {
    $rule = $this->_apply("rule_anything");
    $this->_pred($rule === false);

    return $rule;
  }

  public function rule_undefined()
  {
    $rule = $this->_apply("rule_anything");
    $this->_pred($rule === null);

    return $rule;
  }

  public function rule_number()
  {
    $rule = $this->_apply("rule_anything");
    $this->_pred(gettype($rule) === "number");

    return $rule;
  }

  public function rule_string()
  {
    $rule = $this->_apply("rule_anything");
    $this->_pred(gettype($rule) === "string");

    return $rule;
  }

  public function rule_char()
  {
    $rule = $this->_apply("rule_anything");
    $this->_pred(gettype($rule) === "string" && strlen($rule) == 1);

    return $rule;
  }

  public function rule_space()
  {
    $rule = $this->_apply("rule_char");
    $this->_pred($rule->charCodeAt(0) <= 32);

    return $rule;
  }

  public function rule_spaces()
  {
    return $this->_many(function() {
      return $this->_apply("rule_space"); }
    );
  }

  public function rule_digit()
  {
    $rule = $this->_apply("rule_char");
    $this->_pred($rule >= "0" && $rule <= "9");

    return $rule;
  }

  public function rule_lower()
  {
    $rule = $this->_apply("rule_char");
    $this->_pred($rule >= "a" && r <= "z");

    return $rule;
  }

  public function rule_upper()
  {
    $rule = $this->_apply("rule_char");
    $this->_pred($rule >= "A" && r <= "Z");

    return $rule;
  }

  public function rule_letter()
  {
    return $this->_or($this->_apply("rule_lower"), $this->_apply("rule_upper"));
  }

  public function rule_letterOrDigit()
  {
    return $this->_or($this->_apply("rule_letter"), $this->_apply("rule_digit"));
  }

  public function rule_firstAndRest($first, $rest)
  {
     return $this->_many(function() {
            return $this->_apply($rest);
        },
        $this->_apply($first)
     );
  }

  public function rule_seq($xs)
  {
    for ($idx = 0; $idx < strlen($xs); $idx++) {
      $this->_applyWithArgs("rule_exactly", $xs[$idx]);
    }

    return $xs;
  }

  public function rule_notLast($rule)
  {
    $rule = $this->_apply($rule);
    $this->_lookahead( function() {
        return $this->_apply($rule);
    });

    return $rule;
  }

  public function rule_listOf($rule, $delim)
  {
    return $this->_or(
        function() {
            $rule = $this->_apply($rule);

            return $this->_many(
                function() {
                    $this->_applyWithArgs("rule_token", $delim);

                    return $this->_apply($rule);
                },
                $rule);
        },
        function() {
            return array();
        }
    );
  }

  public function rule_token($cs)
  {
    $this->_apply("rule_spaces");

    return $this->_applyWithArgs("rule_seq", $cs);
  }

  public function fromTo($x, $y)
  {
    return $this->_consumedBy(
        function() {
            $this->_applyWithArgs("rule_seq", x);
            $this->_many(function() {
              $this->_not(function() { $this->_applyWithArgs("rule_seq", y); });
              $this->_apply("rule_char");
            });
            $this->_applyWithArgs("rule_seq", y);
        });
  }

  // match() and matchAll() are a grammar's "public interface"
  public function _genericMatch($input, $rule, $args, $matchFailed)
  {
    if ($args == null) {
      $args = array();
    }
    $realArgs = [$rule];
    for ($idx = 0; $idx < strlen($args); $idx++) {
      $realArgs->push($args[$idx]);
    }
    $m = objectThatDelegatesTo($this, array('input' => $input));
    $m->initialize();
    try {
        return strlen($realArgs) == 1 ? $m->_apply($m, $realArgs[0]) : $m->_applyWithArgs($m, $realArgs);
    } catch (Exception $e) {
      if ($e == Failure && $matchFailed != null) {
        $input = $m->input;
        if ($input->idx != null) {
          while ($input->tl != undefined && $input->tl->idx != null)
            $input = $input->tl;
          $input->idx--;
        }

        return matchFailed($m, $input->index);
      }
      throw $e;
    }
  }

  public function match($obj, $rule, $args, $matchFailed)
  {
      return $this->_genericMatch([$obj].toOMInputStream(), $rule, $args, $matchFailed);
  }

  public function matchAll($listyObj, $rule, $args, $matchFailed)
  {
      return $this->_genericMatch($listyObj.toOMInputStream(), $rule, $args, $matchFailed);
  }

  /*public function createInstance()
  {
      $m = objectThatDelegatesTo($this);
      $m->initialize();
      $m->matchAll = function($listyObj, $aRule) {
        $this->input = $listyObj->toOMInputStream();

        return $this->_apply($aRule);
      }

      return $m;
  }*/
}

if (false === function_exists('objectThatDelegatesTo')) {
    function objectThatDelegatesTo($x, $props)
    {
      $rule = new $x();
      foreach ($props as $p) {
        if (property_exists($props, $p)) {
          $rule[$p] = $props[$p];
        }
      }

      return $rule;
    }
}

if (false === function_exists('isImmutable')) {
    function isImmutable($x)
    {
       return $x === null
        || gettype($x) === "undefined"
        || gettype($x) === "boolean"
        || gettype($x) === "number"
        || gettype($x) === "string";
    }
}

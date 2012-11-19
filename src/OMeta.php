<?php
namespace Phpc;

/**
 * OMeta Base class provides basic functionality.
 *
 * This is the OMeta Base class providing implementations of the fundamental OMeta operations.
 * Built-in rules are defined here.
 */
class OMeta
{
  public function _apply() {

    $memoRec = $this->input->memo[$rule];
    if ($memoRec == null) {
      $origInput = $this->input;
      $LeftRecursion = new LeftRecursion();
      if ($this[$rule] === null) {
        throw new Exception('Tried to apply undefined rule "' . $rule . '"');
      }
      $this->input->memo[$rule] = $LeftRecursion;
      $this->input->memo[$rule] = $memoRec = array('ans' => $this[$rule](), "nextInput" => $this->input);
      if ($LeftRecursion->used) {
        $sentinel = $this->input;
        while (true) {
          try {
            $this->input = $origInput;
            $ans = $this[$rule]();
            if ($this->input == sentinel) {
              throw new ParseError('ParseError: ');
            }
            $memoRec->ans = $ans;
            $memoRec->nextInput = $this->input;
          }
          catch (f) {
            if (f != fail)
              throw f
            break
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
  public function _applyWithArgs($rule) {
    $ruleFn = $this[$rule];
    $ruleFnArity = strlen($ruleFn);
    for ($idx = strlen($arguments) - 1; $idx >= $ruleFnArity + 1; $idx--) {
      // prepend "extra" arguments in reverse order
      $this->_prependInput($arguments[$idx]);
    }
    return ($ruleFnArity == 0) ?
             $ruleFn() :
             $ruleFn.apply(this, Array.prototype.slice.call($arguments, 1, $ruleFnArity + 1));
  }

  public function _superApplyWithArgs($recv, $rule) {
    $ruleFn = $this[$rule];
    $ruleFnArity = strlen($ruleFn);
    for ($idx = strlen($arguments) - 1; $idx > $ruleFnArity + 2; $idx--) // prepend "extra" arguments in reverse order
      $recv._prependInput($arguments[$idx]);
    return ($ruleFnArity == 0) ?
             $ruleFn.call($recv) :
             $ruleFn.apply($recv, Array.prototype.slice.call($arguments, 2, $ruleFnArity + 2))
  }

  public function _prependInput($v) {
    $this->input = new OMInputStream($v, $this->input);
  }

  // if you want your grammar (and its subgrammars) to memoize parameterized rules, invoke this method on it:
  public function memoizeParameterizedRules() {
    $this->_prependInput = function($v) {
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
    }
    /*
    $this->_applyWithArgs = function($rule) {
      $ruleFnArity = strlen($this[$rule]);
      for ($idx = strlen($arguments) - 1; $idx >= $ruleFnArity + 1; $idx--) // prepend "extra" arguments in reverse order
        $this->_prependInput($arguments[$idx]);
      return (($ruleFnArity == 0)) ?
               $this->_apply($rule) :
               this[$rule].apply(this, Array.prototype.slice.call($arguments, 1, $ruleFnArity + 1));
    }*/
  }

  public function _pred($b) {
    if ($b) {
      return true
    }
    throw new FailException('Fail');
  }

  public function _not($x) {
    $origInput = $this->input;
    try { $x(); }
    catch ($f) {
      if ($f != fail)
        throw f
      $this->input = $origInput;
      return true;
    }
    throw new ParseError('ParseError: ');
  }

  public function _lookahead($x) {
    $origInput = $this->input;
    $r = $x();
    $this->input = $origInput;
    return $r;
  }

  public function _or() {
    $origInput = $this->input;
    for ($idx = 0; $idx < strlen($arguments); $idx++)
      try {
        $this->input = $origInput;
        return $arguments[$idx]();
      } catch(f) {
        if (f != fail)
          throw f
      }
    throw new ParseError('ParseError: ');
  }

  public function _xor($ruleName) {
    $origInput = $this->input, $idx = 1, $newInput, $ans;
    while ($idx < strlen($arguments)) {
      try {
        $this->input = $origInput;
        $ans = $arguments[$idx]()
        if ($newInput) {
          throw new Exception('more than one choice matched by "exclusive-OR" in ' . $ruleName);
        }
        $newInput = $this->input;
      } catch (f) {
        if (f != fail) {
          throw f
        }
      }
      $idx++;
    }
    if($newInput) {
      $this->input = $newInput;
      return $ans;
    } else {
      throw new ParseError('ParseError: ');
    }
  }

  public function disableXORs() {
    $this->_xor = $this->_or;
  }

  public function _opt($x) {
    $origInput = $this->input;
    $ans = '';
    try {
        $ans = x();
    } catch (f) {
      if (f != fail) {
        throw f;
      }
      $this->input = $origInput;
    }
    return $ans;
  }

  public function _many($x) {
    $ans = $arguments[1] != undefined ? [$arguments[1]] : [];
    while (true) {
      $origInput = $this->input;
      try { ans.push($x()) }
      catch (f) {
        if (f != fail)
          throw f
        $this->input = $origInput;
        break
      }
    }
    return ans;
  }
  public function _many1($x) {
    return $this->_many($x, $x());
  }

  public function _form($x) {
    $v = $this->_apply("anything");
    if (!isSequenceable($v))
      throw new ParseError('ParseError: ');
    $origInput = $this->input;
    $this->input = $v.toOMInputStream();
    $r = $x();
    $this->_apply("end");
    $this->input = $origInput;
    return $v;
  }

  public function _consumedBy($x) {
    $origInput = $this->input;
    $x();
    return $origInput.upTo($this->input);
  }

  public function _idxConsumedBy($x) {
    $origInput = $this->input;
    $x();
    return {$fromIdx: $origInput.idx, $toIdx: $this->input.idx}
  }

  public function _interleave($mode1, $part1, $mode2, $part2 /* ..., moden, partn */) {
    $currInput = $this->input;
    $ans = [];
    for ($idx = 0; $idx < strlen($arguments); $idx += 2)
      ans[idx / 2] = ($arguments[$idx] == "*" || $arguments[$idx] == "+") ? [] : undefined
    while (true) {
      $idx = 0; $allDone = true;
      while ($idx < strlen($arguments)) {
        if ($arguments[$idx] != "0")
          try {
            $this->input = currInput
            switch ($arguments[$idx]) {
              case "*": ans[$idx / 2].push($arguments[$idx + 1]()); break;
              case "+": ans[$idx / 2].push($arguments[$idx + 1]()); $arguments[$idx] = "*"; break;
              case "?": ans[$idx / 2] = $arguments[$idx + 1](); $arguments[$idx] = "0"; break;
              case "1": ans[$idx / 2] = $arguments[$idx + 1](); $arguments[$idx] = "0"; break;
              default: throw "invalid mode '" . $arguments[$idx] . "' in OMeta._interleave"
            }
            $currInput = $this->input;
            break;
          }
          catch (f) {
            if (f != fail)
              throw f
            // if this (failed) part's mode is "1" or "+", we're not done yet
            $allDone = $allDone && ($arguments[$idx] == "*" || $arguments[$idx] == "?")
          }
        $idx += 2
      }
      if ($idx == strlen($arguments)) {
        if ($allDone)
          return $ans;
        else
          throw new ParseError('ParseError: ');
      }
    }
  }

  public function _currIdx() {
      return $this->input.idx;
  }

  /**
   * ================
   * some basic rules
   * ================
   */

  /**
   * Match a single item from the input of any kind.
   */
  public function anything() {
    $r = $this->input.head();
    $this->input = $this->input.tail();
    return r;
  }

  public function end() {
    return $this->_not(function() {
      return $this->_apply("anything"); }
    );
  }

  public function pos() {
    return $this->input.idx;
  }

  public function empty() {
    return true;
  }

  public function apply($r) {
    return $this->_apply($r);
  }

  public function foreign($g, $r) {
    $gi = objectThatDelegatesTo($g, {input: makeOMInputStreamProxy($this->input)}),
        $ans = $gi._apply($r)
    $this->input = $gi.input.target;
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
  public function exactly($wanted) {
    if ($wanted === $this->_apply("anything")) {
      return $wanted;
    }
    throw new ParseError('ParseError: ');
  }

  public function "true"() {
    $r = $this->_apply("anything");
    $this->_pred($r === true);
    return r;
  }

  public function "false"() {
    $r = $this->_apply("anything");
    $this->_pred($r === false)
    return r;
  }

  public function "undefined"() {
    $r = $this->_apply("anything");
    $this->_pred($r === null)
    return r;
  }
  public function number() {
    $r = $this->_apply("anything");
    $this->_pred(typeof $r === "number");
    return r;
  }

  public function string() {
    $r = $this->_apply("anything");
    $this->_pred(typeof $r === "string");
    return r;
  }

  public function "char"() {
    $r = $this->_apply("anything");
    $this->_pred(typeof $r === "string" && strlen($r) == 1);
    return r;
  }

  public function space() {
    $r = $this->_apply("char");
    $this->_pred($r.charCodeAt(0) <= 32);
    return r;
  }

  public function spaces() {
    return $this->_many(function() {
      return $this->_apply("space"); }
    );
  }

  public function digit() {
    $r = $this->_apply("char");
    $this->_pred($r >= "0" && $r <= "9");
    return $r;
  }

  public function lower() {
    $r = $this->_apply("char");
    $this->_pred($r >= "a" && r <= "z");
    return $r;
  }

  public function upper() {
    $r = $this->_apply("char");
    $this->_pred($r >= "A" && r <= "Z");
    return $r;
  }

  public function letter() {
    return $this->_or(
        function() { return $this->_apply("lower"); },
        function() { return $this->_apply("upper"); }
    );
  }

  public function letterOrDigit()) {
    return $this->_or(
        function() { return $this->_apply("letter"); },
        function() { return $this->_apply("digit"); }
    );
  }

  public function firstAndRest($first, $rest) {
     return $this->_many(function() {
            return $this->_apply($rest);
        },
        $this->_apply($first)
     );
  }

  public function seq($xs) {
    for ($idx = 0; $idx < strlen($xs); $idx++) {
      $this->_applyWithArgs("exactly", $xs[$idx]);
    }
    return $xs;
  }

  public function notLast($rule) {
    $r = $this->_apply($rule);
    $this->_lookahead( function() {
        return $this->_apply($rule);
    });
    return $r;
  }

  public function listOf($rule, $delim) {
    return $this->_or(function() {
        $r = $this->_apply($rule);
        return $this->_many(function() {
          $this->_applyWithArgs("token", $delim);
          return $this->_apply($rule);
        },
        $r)
      },
      function() { return [] })
  }

  public function token($cs) {
    $this->_apply("spaces");
    return $this->_applyWithArgs("seq", $cs);
  }

  public function fromTo(x, y) {
    return $this->_consumedBy(function() {
                              $this->_applyWithArgs("seq", x);
                              $this->_many(function() {
                                $this->_not(function() { $this->_applyWithArgs("seq", y); });
                                $this->_apply("char");
                              });
                              $this->_applyWithArgs("seq", y);
                            });
  }

  // match and matchAll are a grammar's "public interface"
  public function _genericMatch($input, $rule, $args, $matchFailed) {
    if ($args == null)
      $args = []
    $realArgs = [$rule]
    for ($idx = 0; $idx < strlen($args); $idx++)
      $realArgs.push($args[$idx])
    $m = objectThatDelegatesTo(this, {input: input})
    $m.initialize()
    try { return strlen($realArgs) == 1 ? $m._apply.call($m, realArgs[0]) : m._applyWithArgs.apply(m, realArgs) }
    catch (f) {
      if (f == fail && matchFailed != null) {
        $input = m.input
        if (input.idx != null) {
          while (input.tl != undefined && input.tl.idx != null)
            input = input.tl
          input.idx--
        }
        return matchFailed(m, input.idx)
      }
      throw f
    }
  }

  public function match($obj, $rule, $args, $matchFailed) {
      return $this->_genericMatch([$obj].toOMInputStream(), $rule, $args, $matchFailed);
  }

  public function matchAll($listyObj, $rule, $args, $matchFailed) {
      return $this->_genericMatch($listyObj.toOMInputStream(), $rule, $args, $matchFailed);
  }

  public function createInstance() {
      $m = objectThatDelegatesTo($this);
      $m->initialize();
      $m->matchAll = function($listyObj, $aRule) {
        $this->input = $listyObj->toOMInputStream();
        return $this->_apply($aRule);
      }
      return $m;
  }
}

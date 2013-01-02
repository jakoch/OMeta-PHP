OMeta-PHP
=========

Brief Overview
--------------

OMeta-PHP is an implementation of OMeta with PHP as the hosting language.

OMeta is an object-oriented language for pattern-matching developed by Alessandro Warth and Ian Piumarta.
It is based on a variant of Parsing Expression Grammars (PEGs) which were extended to support matching on
arbitrary data types. OMeta's general-purpose pattern matching facilities provide a natural and convenient
way for programmers to implement tokenizers, parsers, visitors, and tree transformers, all of which can be
extended in interesting ways using familiar object-oriented mechanisms.
This makes OMeta particularly well-suited as a medium for experimenting with new designs
for programming languages and extensions to existing languages.

PEG
---

Parsing expression grammars (PEGs) are an alternative to context free grammars (CFGs) for formally
specifying syntax. PEGs are backtracking (unlimited lookahead) parsers with speculative parsing support.
Speculative Parsing is controled by syntactic predicates (grammar fragments),
specifying the lookahead and predicting an alternative.
The operator in the grammar is called "prioritized choice"-operator (|) - this rule "OR" that rule.
To avoid unneccessay reparsing partial parsing results are cached - this is called Memoizing.
Bryan Ford coined the term "Packrat Parser" for a "memoizing recursive-descent parser" in
Packrat parsing: simple, powerful, lazy, linear time, functional pearl (http://bford.info/packrat/).

A PEG is something like a Regular Expressions with recursion.
PEGs operate on streams of characters.
The grammars are like templates with a parser combination description.
Basically a PEG is a set of combinators.
Each combinator is a function, which generates an atomic unit of a parser (called a rule).
By combining these parts (uhm, with combinators), you can generate complex parsers that
can handle a superset of Context Free Grammars.
The created parsers are data-structure agnostic. They can parse any input sequence.
So you might call the generated PHP parser a meta-language hosted in PHP.

Summary
------
- OMeta-PHP is metalanguage.
- You can create new language constructs, and create DSLs.
- You can subclass existing parsers to extend a language.
- You can invoke foreign rules, so you can subclass and "derive" from more than one parser class (composition of multiple grammars).

Links & Papers
--------------
1. Official OMeta/JS
   - http://www.tinlizzie.org/~awarth/ometa/
2. OMeta Mailinglist
   - http://vpri.org/mailman/listinfo/ometa
3. [WP07] Alessandro Warth and Ian Piumarta. OMeta: an Object-Oriented Language for Pattern Matching.
   In DLS ’07: Proceedings of the 2007 symposium on Dynamic languages, pages 11–19, New York, NY, USA, 2007. ACM.
   - (Available at http://www.cs.ucla.edu/~awarth/papers/dls07.pdf).
4. Alessandro Warth, Experimenting with Programming Languages, PhD dissertation, 2009,
   - (Available at http://www.vpri.org/pdf/tr2008003_experimenting.pdf).
5. [For04] Bryan Ford. Parsing expression grammars: a recognition-based syntactic foundation.
   In POPL ’04: Proceedings of the 31st ACM SIGPLAN-SIGACT symposium on Principles of programming languages,
   pages 111–122, New York, NY, USA, 2004. ACM.

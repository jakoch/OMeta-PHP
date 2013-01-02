A Collection of PHP Optimizations
---------------------------------

The purpose of this document is to store some code recipes for PHP code optimization.

A. Control-Structure Optimizations

1. "For" - Optimization No. 1

a) - for ($i = 0; $i < count($k); $i++) {
b) + for ($i = 0, $count = count($k); $i < $count; $i++) {

The code shown in a) can be slow, because the array size is fetched on every iteration.
Since the size never changes, the loop be easily optimized by using an intermediate variable to store the size instead of repeatedly calling count().
The intermediate variable is defined inside the first section of the for loop.

2. For - Optimization No. 2

a) - for ($i = 0; $i < count($k); $i++) {
b) + $count = count($k);
   + for ($i = 0; $i < $count; $i++) {

The code shown in a) can be slow, because the array size is fetched on every iteration.
Since the size never changes, the loop be easily optimized by using an intermediate variable to store the size instead of repeatedly calling count().
This time the intermediate variable is defined before the for statement.
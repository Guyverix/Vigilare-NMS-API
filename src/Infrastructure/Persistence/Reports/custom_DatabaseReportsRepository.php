<?php
/*
  This is an example of how an end user can add new functions
  and different reports without taking a chance of breaking what
  comes with the default application.

  This will only work if the src/Application/Action/Reports supports
  adhoc functions being added.

  Any functions here MUST be added into src/Domain/Reports so
  that the main receiver can tell that they exist.

  This is more of a test of an idea than a fully fleshed out
  solution to allow this kind of functionality.

  This must be called from WITHIN the main page as an include
  so that the functions can be used by the parent object
*/



?>

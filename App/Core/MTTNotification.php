<?php

declare(strict_types=1);

namespace App\Core;

// Enum
abstract class MTTNotification
{
    const DIDFINISHREQUEST = 'didFinishRequest';
    const DIDCREATETASK = 'didCreateTask';
    const DIDEDITTASK = 'didEditTask';
    const DIDDELETETASK = 'didDeleteTask';
    const DIDCOMPLETETASK = 'didCompleteTask';
    const DIDCREATELIST = 'didCreateList';
    const DIDDELETELIST = 'didDeleteList';
    const DIDDELETECOMPLETEDINLIST = 'didDeleteCompletedInList';
}

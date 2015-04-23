<?php

return array(
    'PNVAR1' => 'Hello',
    'PNVAR2' => 'World!',
    'PNVAR3' => '{$PNVAR1} {$PNVAR2}',
    'PNVAR4' => '${PNVAR1} ${PNVAR2}',
    'PNVAR5' => '$PNVAR1 {PNVAR2}',
    'PNVAR6' => '${NONEXIST}variable',
    'PNVAR7' => '\${PNVAR1} \${PNVAR2}',
);

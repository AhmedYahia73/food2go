<?php

return [
    'size' => 300,
    'format' => 'png', // critical: avoids imagick
    'margin' => 10,
    'error_correction' => 'L',
    'renderer' => null, // null = default GD renderer (no imagick)
];

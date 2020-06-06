<?php


$data = imageToUnitData('./static/origin.jpg');

$image = createImageForUnitData($data);

switch ($image['mime']) {
  case 'image/gif':
    imagegif($image['data'], $this->dstPath);
    break;
  case 'image/png':
    imagepng($image['data'], $this->dstPath);
    break;
  default:
    imagejpeg($image['data'], './static/2.jpg', 100);
    break;
}

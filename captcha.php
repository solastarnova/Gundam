<?php
session_start();
Header("Content-Type: image/png");  

$im = imagecreate(138, 38);
$back = imagecolorallocate($im, 245, 245, 245);
imagefill($im,0,0, $back);

$yzm_code = "";
for($i = 0; $i < 4; $i++) {
    $font = imagecolorallocate($im, rand(100, 255), rand(0, 100),rand(100, 255));
    $authnum = rand(0,9);
    $yzm_code .= $authnum; 
    imagestring($im, 5, 50 + $i * 10, 20, $authnum, $font);
}

$_SESSION['yzm']= $yzm_code;

for($i = 0; $i < 200 ; $i++){ 
    $randcolor = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
    imagesetpixel($im, rand()%150, rand()%150, $randcolor);
}

imagepng($im);
imagedestroy($im);
?>

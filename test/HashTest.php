<?php
// make this a test/benchmark
// reference: http://stackoverflow.com/questions/3665247/fastest-hash-for-non-cryptographic-uses
set_time_limit(720);

$tries = 1000;
$begin = startTime();
$scores = array();


foreach(hash_algos() as $algo) {
    $scores[$algo] = 0;
}

for($i=0;$i<$tries;$i++) {
    $number = rand()*100000000000000;
    $string = randomString(500);

    foreach(hash_algos() as $algo) {
        $start = startTime();

        hash($algo, $number); //Number
        hash($algo, $string); //String

        $end = endTime($start);

        $scores[$algo] += $end;
    }   
}


asort($scores);

$i=1;
$base=0;
foreach($scores as $alg => $time) {
    $hash = hash($alg,$string);
    if($base===0) {
        $base = $time;
    }
    $delta = number_format($time*100/$base, 2).'%';
    print "{$i}\t{$alg}\t{$time}s\t{$delta}\t{$hash}\n";
    $i++;
}

echo "Entire page took ".endTime($begin)." seconds\n";

function startTime() {
   $mtime = microtime(); 
   $mtime = explode(" ",$mtime); 
   $mtime = $mtime[1] + $mtime[0]; 
   return $mtime;   
}

function endTime($starttime) {
   $mtime = microtime(); 
   $mtime = explode(" ",$mtime); 
   $mtime = $mtime[1] + $mtime[0]; 
   $endtime = $mtime; 
   return $totaltime = ($endtime - $starttime); 
}

function randomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = '';    
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters)-1)];
    }
    return $string;
}

?>
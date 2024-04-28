<?php
if ($argc > 1) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

if(strtolower($_GET['preset']) == 'telegram') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'webm';
    $_GET['resolution'] = 512;
    $_GET['filesize'] = 256 * 1024;
} elseif(strtolower($_GET['preset']) == 'imessage') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'gif';
    $_GET['resolution'] = 618;
    $_GET['filesize'] = 500 * 1024;
} elseif(strtolower($_GET['preset']) == 'imessage-medium') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'gif';
    $_GET['resolution'] = 408;
    $_GET['filesize'] = 500 * 1024;
} elseif(strtolower($_GET['preset']) == 'imessage-small') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'gif';
    $_GET['resolution'] = 300;
    $_GET['filesize'] = 500 * 1024;
} elseif(strtolower($_GET['preset']) == 'giphy') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'gif';
    $_GET['resolution'] = 512;
    $_GET['filesize'] = 1024 * 1024;
} elseif(strtolower($_GET['preset']) == 'tenor') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'gif';
    $_GET['resolution'] = 512;
    $_GET['filesize'] = 512 * 1024;
} elseif(strtolower($_GET['preset']) == 'discord') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'apng';
    $_GET['resolution'] = 320;
    $_GET['filesize'] = 512 * 1024;
} elseif(strtolower($_GET['preset']) == 'discord-emoji') {
    echo PHP_EOL . 'Using preset: ' . strtolower($_GET['preset']);
    $_GET['format'] = 'gif';
    $_GET['resolution'] = 128;
    $_GET['filesize'] = 256 * 1024;
}

$format = $_GET['format'] ?? 'apng';
$tinypngApiKey = $_GET['tinypngapikey'] ?? '';
$resolution = $_GET['resolution'] ?? 512;
$minFps = $_GET['fps'] ?? 5;
$filesize = $_GET['filesize'] ?? (512 * 1024);
$aggressive = isset($_GET['aggressive']);

$src = __DIR__ . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR;
$dest = __DIR__ . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . $format . DIRECTORY_SEPARATOR;

exec('rm -rf ' . sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-*');

$maxDepth = 150;
$currentDepth = 0;
$lastFileSize = null;
function brute(string $filePath, int $filesize, bool $aggressive = false, string $source = null)
{
    global $currentDepth;
    global $maxDepth;
    global $resolution;
    global $dest;
    global $lastFileSize;
    global $format;
    global $minFps;
    if(is_null($source)) {
        $source = $filePath;
    }

    $currentDepth++;
    if($currentDepth > $maxDepth) {
        $currentDepth = 0;
        $lastFileSize = null;
        echo PHP_EOL . 'MAX_DEPTH REACHED!!! Failed to compress file: ' . $filePath;
        return;
    }

    if(((!is_null($lastFileSize) && $lastFileSize <= info($filePath)))) {
        $currentDepth = 0;
        $lastFileSize = null;
        echo PHP_EOL . 'IMPOSSIBLE TASK!!! Failed to compress file: ' . $filePath;
        return;
    }
    $lastFileSize = info($filePath);

    if($format === 'webm') {
        $out = convertToWebM($filePath, $filesize, $resolution);
        if(info($out) > $filesize) {
            brute($filePath, $filesize - 1024, $aggressive, $source);
            return;
        } else {
            $base = pathinfo($source, PATHINFO_FILENAME);
            $compressions = $dest . $base . DIRECTORY_SEPARATOR;

            if(!is_dir($compressions)) {
                mkdir($compressions, 0777, true);
            }

            rename($out, $compressions . implode('-', [$base]) . '.' . ($format === 'apng' ? 'png': $format));

            exec('rm -rf ' . escapeshellarg(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-*'));
            $completed = rtrim(dirname($source), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Done' . DIRECTORY_SEPARATOR;

            if(!is_dir($completed)) {
                mkdir($completed, 0777, true);
            }
            rename($source, $completed . basename($source));
            return;
        }
    }

    $fps = [5, 10, 60];
    $colors = [8, 10, 64];
    $quality = [15, 15, 100];

    if($aggressive) {
        $fps[1] = $minFps;
        $fps[0] = 1;
        $colors[1] = 8;
        $colors[0] = 1;
        $quality[1] = 1;
    }

    $currentFps = $fps[1];
    $currentFpsStep = $fps[0];

    $previousOuts = [[], [], []];
    $safeOutputs = [];
    $lastCriteria = [[], [], []];
    while(($currentFps <= $fps[2])) {

        $out = movToGif($filePath, $currentFps);
        $out = scaleGif($out, $resolution);

        $lastCriteria[0][] = $currentFps;

        $currentColors = $colors[1];
        $currentColorsStep = $colors[0];
        while(($currentColors <= $colors[2])) {

            if($aggressive) {
                $outColors = posterizeGif($out, $currentColors);
            } else {
                $outColors = posterizeGif($out, $currentColors);
            }

            $lastCriteria[1][] = $currentColors;

            $currentQuality = $quality[1];
            $currentQualityStep = $quality[0];
            while(($currentQuality <= $quality[2])) {

                $lastCriteria[2][] = $currentQuality;

                echo PHP_EOL . 'Trying FPS: ' . $currentFps . ', Colors: ' . $currentColors . ', Quality: ' . $currentQuality;

                $outQuality = compressGif($outColors, $currentQuality);
                if($format === 'apng') {
                    $outQuality = gifToApng($outQuality);
                    if($aggressive) {
                        $outQuality = compressApng($outQuality, $currentFps, $currentQuality, 2);
                        $outQuality = tinify($outQuality);
                    } else {
                        $outQuality = compressApng($outQuality, $currentFps, $currentQuality, 1);
                        $outQuality = tinify($outQuality);
                    }
                } elseif($format === 'webp') {
                    if($aggressive) {
                        $outQuality = gifToWebp($outQuality, $currentQuality, 6);
                    } else {
                        $outQuality = gifToWebp($outQuality, $currentQuality, 3);
                    }
                }

                $currentFileSize = info($outQuality);
                if($currentFileSize > $filesize) {
                    $trialQuality = max($quality[1], $currentQuality - max(1, floor($currentQualityStep / 2)));
                    if((!($currentQualityStep === 1 && (($currentQuality - $quality[1]) % $quality[0] === 0)) || $currentQualityStep > 1) && $trialQuality >= $quality[1] && $trialQuality <= $quality[2] && (array_search($trialQuality, $lastCriteria[2]) === false)) {
                        $currentQualityStep = $trialQuality === $quality[1] ? $quality[0] :  max(1, floor($currentQualityStep / 2));
                        $currentQuality = $trialQuality;
                        echo '  ☠️';
                        continue 1;
                    } else {
                        $currentQualityStep = $quality[0];
                        $currentQuality = $quality[1];
                        if(isset($previousOuts[2]['path']) && !in_array($previousOuts[2]['path'] ?? null, array_column($safeOutputs, 'path'))) {
                            echo '  ❤️';
                            $safeOutputs[] = $previousOuts[2];
                        } else {
                            echo '  ☠️☠️';
                            if($aggressive && count($safeOutputs) === 0) {
                                echo PHP_EOL . "WARNING!!!! Attempting recursive brute with depth of: {$currentDepth} and current filesize of {$currentFileSize} Bytes";
                                brute($outQuality, $filesize, $aggressive, $source);

                                $currentDepth = 0;
                                $lastFileSize = null;
                                return;
                            }
                        }
                        break 1;
                    }
                } elseif($currentDepth > 1) {
                    echo '  ❤️❤️';
                    $currentDepth = 1;
                    $lastFileSize = null;
                    $previousOuts[2] = [
                        'path' => $outQuality,
                        'quality' => min($quality[2], $currentQuality),
                        'colors' => min($colors[2], $currentColors),
                        'fps' => min($fps[2], $currentFps),
                    ];
                    $safeOutputs[] = $previousOuts[2];
                    break 3;
                } else {
                    $previousOuts[2] = [
                        'path' => $outQuality,
                        'quality' => min($quality[2], $currentQuality),
                        'colors' => min($colors[2], $currentColors),
                        'fps' => min($fps[2], $currentFps),
                    ];
                    if(($currentQuality + $currentQualityStep) >= $quality[2] && ($currentColors + $currentColorsStep) >= $colors[2] && ($currentFps + $currentFpsStep) >= $fps[2]) {
                        $currentQualityStep = $quality[0];
                        $currentQuality = $quality[1];
                        echo '  ❤️';
                        $safeOutputs[] = $previousOuts[2];
                        break 1;
                    } else {
                        echo '  🔥';
                    }
                }

                $currentQuality += $currentQualityStep;
            }

            if(info($outQuality) > $filesize) {
                $trialColors = max($colors[1], $currentColors - max(1, floor($currentColorsStep / 2)));
                if((!($currentColorsStep === 1 && (($currentColors - $colors[1]) % $colors[0] === 0)) || $currentColorsStep > 1) && $trialColors >= $colors[1] && $trialColors <= $colors[2] && array_search($trialColors, $lastCriteria[1]) === false) {
                    $currentColorsStep = $trialColors === $colors[1] ? $colors[0] :  max(1, floor($currentColorsStep / 2));
                    $currentColors = $trialColors;
                    continue 1;
                } else {
                    $currentColorsStep = $colors[0];
                    $currentColors = $colors[1];
                    //    if(isset($previousOuts[1]["path"]) && !in_array($previousOuts[1]["path"] ?? null, array_column($safeOutputs, "path"))) {
                    //     $safeOutputs[] = $previousOuts[1];
                    //    }
                    break 1;
                }
            } else {
                // $previousOuts[1] = [
                // 	"path" => $outQuality,
                //     "quality" => min($quality[2], $currentQuality),
                //     "colors" => min($colors[2], $currentColors),
                //     "fps" => min($fps[2], $currentFps),
                // ];
            }

            $currentColors += $currentColorsStep;
        }
        if(info($outQuality) > $filesize) {
            $trialFps = max($fps[1], $currentFps - max(1, floor($currentFpsStep / 2)));
            if((!($currentFpsStep === 1 && (($currentFps - $fps[1]) % $fps[0] === 0)) || $currentFpsStep > 1) && $trialFps >= $fps[1] && $trialFps <= $fps[2] && array_search($trialFps, $lastCriteria[0]) === false) {
                $currentFpsStep = $trialFps === $fps[1] ? $fps[0] : max(1, floor($currentFpsStep / 2));
                $currentFps = $trialFps;
                continue 1;
            } else {
                $currentFpsStep = $fps[0];
                $currentFps = $fps[1];
                //    if(isset($previousOuts[0]["path"]) && !in_array($previousOuts[0]["path"] ?? null, array_column($safeOutputs, "path"))) {
                //     $safeOutputs[] = $previousOuts[0];
                //    }
                break 1;
            }
        } else {
            // $previousOuts[0] = [
            // 	"path" => $outQuality,
            // 	"quality" => min($quality[2], $currentQuality),
            // 	"colors" => min($colors[2], $currentColors),
            // 	"fps" => min($fps[2], $currentFps),
            // ];
        }

        $currentFps += $currentFpsStep;
    }

    foreach(array_filter($safeOutputs) as $output) {
        $base = pathinfo($source, PATHINFO_FILENAME);
        $compressions = $dest . $base . DIRECTORY_SEPARATOR;

        if(!is_dir($compressions)) {
            mkdir($compressions, 0777, true);
        }

        rename($output['path'], $compressions . implode('-', [$base, $output['quality'], $output['colors'], $output['fps']]) . '.' . ($format === 'apng' ? 'png': $format));
    }

    exec('rm -rf ' . escapeshellarg(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-*'));
    if($currentDepth === 1) {
        if(count(array_filter($safeOutputs)) === 0) {
            echo PHP_EOL . 'Failed to compress file: ' . $filePath;
        } else {
            $completed = rtrim(dirname($source), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Done' . DIRECTORY_SEPARATOR;

            if(!is_dir($completed)) {
                mkdir($completed, 0777, true);
            }
            rename($source, $completed . basename($source));
        }
    }

    $currentDepth = 0;
    $lastFileSize = null;

}

function movToGif(string $filePath, int $fps = 60): string
{
    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.gif');
    $tempFile = $tempFile . '.gif';
    unlink($tempFile);
    exec("/usr/local/bin/ffmpeg -y -i \"{$filePath}\" -f gif -r {$fps} -lavfi split[v],palettegen,[v]paletteuse \"{$tempFile}\" > /dev/null 2>&1");
    return $tempFile;
}

function scaleGif(string $filePath, int $maxSize = 512): string
{
    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.gif');
    $tempFile = $tempFile . '.gif';
    unlink($tempFile);
    exec("/usr/local/bin/gifsicle --careful --conserve-memory --no-ignore-errors --no-warnings --crop-transparency --no-comments --no-extensions --no-names --resize-touch {$maxSize}x{$maxSize} \"{$filePath}\" -o \"{$tempFile}\"");
    return $tempFile;
}

function posterizeGif(string $filePath, int $colors = 32, int $colorDepth = 8, int $fuzz = 7)
{
    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.gif');
    $tempFile = $tempFile . '.gif';
    unlink($tempFile);
    exec("/usr/local/bin/convert \"{$filePath}\" -depth {$colorDepth} -fuzz {$fuzz}% +dither -posterize {$colors} \"{$tempFile}\"");
    return $tempFile;
}

function compressGif(string $filePath, int $quality = 50, int $colors = 32): string
{
    $colorMethod = 'blend-diversity';
    $colorMethod = 'diversity';
    $lvl = 3;
    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.gif');
    $tempFile = $tempFile . '.gif';
    unlink($tempFile);
    exec("/usr/local/bin/gifsicle -O{$lvl} --careful --conserve-memory --lossy={$quality} --color-method={$colorMethod} --no-ignore-errors --no-warnings --crop-transparency --no-comments --no-extensions --no-names \"{$filePath}\" -o \"{$tempFile}\"");
    return $tempFile;
}
function gifToApng(string $filePath)
{
    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.png');
    $tempFile = $tempFile . '.png';
    unlink($tempFile);
    exec("/usr/local/bin/ffmpeg -y -i {$filePath} -f apng -plays 0 \"{$tempFile}\" > /dev/null 2>&1");
    return $tempFile;
}
function convertToWebm(string $filePath, int $filesize, int $maxSize = 512, int $compression = 35)
{
    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.webm');
    $tempFile = $tempFile . '.webm';
    unlink($tempFile);
    $bitrate = floor((($filesize * 8) / 3) * 0.6);
    exec("/usr/local/bin/ffmpeg -y -i \"{$filePath}\" -b:v {$bitrate} -c:v libvpx-vp9 -an -vf \"scale='min({$maxSize},iw+mod(iw,2))':'min({$maxSize},ih+mod(ih,2)):flags=neighbor'\" -pix_fmt yuva420p -t 00:00:03 -auto-alt-ref 0 \"{$tempFile}\" > /dev/null 2>&1");//-b:v 0 -crf {$compression}
    return $tempFile;
}
function gifToWebp(string $filePath, int $quality, int $method)
{
    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.webp');
    $tempFile = $tempFile . '.webp';
    unlink($tempFile);
    exec("/usr/local/bin/gif2webp -lossy -q {$quality} -m {$method} -min_size -mt -quiet \"{$filePath}\" -o \"{$tempFile}\" > /dev/null 2>&1");
    return $tempFile;
}

function compressApng(string $filePath, int $fps = 10, int $quality = 50, int $compression = 2): string
{
    $tempFolder = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-') . DIRECTORY_SEPARATOR;
    unlink(rtrim($tempFolder, DIRECTORY_SEPARATOR));
    if(!is_dir($tempFolder)) {
        mkdir($tempFolder, 0777, true);
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.png');
    $tempFile = $tempFile . '.png';
    unlink($tempFile);
    exec("/usr/local/bin/apngasm -F -o \"${tempFolder}\" -D \"{$filePath}\"");
    exec("/usr/local/bin/pngquant --skip-if-larger --force --ext .png --strip --quality=${quality}-{$quality} -- ${tempFolder}*.png");
    exec(__DIR__ . "/apngasm \"${tempFile}\" \"${tempFolder}*.png\" 1 {$fps} -z{$compression}");
    exec('rm -rf ' . escapeshellarg($tempFolder));
    return $tempFile;
}

function tinify(string $filePath)
{
    global $tinypngApiKey;
    if(empty($tinypngApiKey)) {
        return $filePath;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'cnvro' . ($_GET['preset'] ?? basename(__DIR__)) . '-');
    rename($tempFile, $tempFile . '.png');
    $tempFile = $tempFile . '.png';
    unlink($tempFile);
    $json = shell_exec("curl -sb -H \"Accept: application/json\" https://api.tinify.com/shrink \
	--user api:{$tinypngApiKey} \
	--data-binary @{$filePath} \
	--dump-header /dev/null");
    $response = json_decode($json, true);
    file_put_contents($tempFile, file_get_contents($response['output']['url']));
    if(is_file($tempFile) && filesize($tempFile) > 0 && strpos(mime_content_type($tempFile), 'image') !== false) {
        return $tempFile;
    }

    var_dump($json);
    return $filePath;
}

function info(string $filename)
{
    clearstatcache(true);
    $filesize = filesize($filename);
    $stat = intval(shell_exec("stat --format=\"%s\" \"$filename\""));
    return max($stat, $filesize);
}

foreach(glob($src . '*') as $file) {
    if(is_file($file)) {
        echo PHP_EOL . 'Compressing: ' . $file;
        brute($file, $filesize, $aggressive);
    }
}

<?php
// Live deploy-status badge, same output as upstream /api/badge
require __DIR__ . '/lib.php';

const BADGE_CACHE = 'public, max-age=60, s-maxage=300, stale-while-revalidate=3600';
const LEFT_WIDTH = 110;
const CHAR_WIDTH = 6.5;
const PADDING = 20;
const MAX_REASON_CHARS = 37;
const VALID_STYLES = ['flat', 'flat-square', 'plastic', 'for-the-badge', 'social'];

function xml_escape($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// mbstring isn't installed on the server, so count UTF-8 characters with PCRE
function utf8_length($text) {
    return preg_match_all('/./us', $text);
}

function truncate_reason($text, $max = MAX_REASON_CHARS) {
    if (utf8_length($text) <= $max) {
        return $text;
    }
    preg_match('/^.{' . $max . '}/us', $text, $m);
    return $m[0] . '…';
}

function build_segments($verdict, $reason, $canDeploy, $style = 'flat') {
    $isFtB = $style === 'for-the-badge';
    $rightText = ($isFtB ? strtoupper($verdict) : $verdict) . ' · ' . truncate_reason($reason);
    $rightWidth = round(utf8_length($rightText) * CHAR_WIDTH + PADDING);
    $leftText = $isFtB ? 'SHOULDIDEPLOY' : 'shouldideploy';

    return [
        ['label' => $leftText, 'color' => '#555', 'x' => 0, 'width' => LEFT_WIDTH],
        ['label' => $rightText, 'color' => $canDeploy ? '#4c1' : '#ff4136', 'x' => LEFT_WIDTH, 'width' => $rightWidth]
    ];
}

function total_width($segments) {
    return round(array_sum(array_column($segments, 'width')));
}

function render_rects($segments, $height, $glue) {
    $rects = [];
    foreach ($segments as $s) {
        $rects[] = '<rect x="' . round($s['x']) . '" width="' . round($s['width']) . '" height="' . $height . '" fill="' . xml_escape($s['color']) . '"/>';
    }
    return implode($glue, $rects);
}

function render_texts($segments, $y, $textColor, $shadowY = null, $bold = false) {
    $boldAttr = $bold ? ' font-weight="bold"' : '';
    $lines = [];
    foreach ($segments as $s) {
        $cx = $s['x'] + $s['width'] / 2;
        $label = xml_escape($s['label']);
        if ($shadowY !== null) {
            $lines[] = "    <text x=\"{$cx}\" y=\"{$shadowY}\" fill=\"#010101\" fill-opacity=\".3\" text-anchor=\"middle\">{$label}</text>";
        }
        $lines[] = "    <text x=\"{$cx}\" y=\"{$y}\" fill=\"{$textColor}\" text-anchor=\"middle\"{$boldAttr}>{$label}</text>";
    }
    return implode("\n", $lines);
}

function render_flat($segments) {
    $w = total_width($segments);
    $rects = render_rects($segments, 20, "\n    ");
    $texts = render_texts($segments, 14, '#fff', 15);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="20">
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <clipPath id="r">
    <rect width="{$w}" height="20" rx="3" fill="#fff"/>
  </clipPath>
  <g clip-path="url(#r)">
    <rect width="{$w}" height="20" fill="#555"/>
    {$rects}
    <rect width="{$w}" height="20" fill="url(#s)"/>
  </g>
  <g font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">
{$texts}
  </g>
</svg>
SVG;
}

function render_flat_square($segments) {
    $w = total_width($segments);
    $rects = render_rects($segments, 20, "\n  ");
    $texts = render_texts($segments, 14, '#fff');

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="20">
  {$rects}
  <g font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11" shape-rendering="crispEdges">
{$texts}
  </g>
</svg>
SVG;
}

function render_plastic($segments) {
    $w = total_width($segments);
    $rects = render_rects($segments, 18, "\n    ");
    $texts = render_texts($segments, 12, '#fff', 13);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="18">
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#fff" stop-opacity=".7"/>
    <stop offset=".1" stop-color="#aaa" stop-opacity=".1"/>
    <stop offset=".9" stop-color="#000" stop-opacity=".3"/>
    <stop offset="1" stop-color="#000" stop-opacity=".5"/>
  </linearGradient>
  <clipPath id="r">
    <rect width="{$w}" height="18" rx="4" fill="#fff"/>
  </clipPath>
  <g clip-path="url(#r)">
    <rect width="{$w}" height="18" fill="#555"/>
    {$rects}
    <rect width="{$w}" height="18" fill="url(#s)"/>
  </g>
  <g font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">
{$texts}
  </g>
</svg>
SVG;
}

function render_for_the_badge($segments) {
    $w = total_width($segments);
    $rects = render_rects($segments, 28, "\n  ");
    $texts = render_texts($segments, 19, '#fff', null, true);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="28">
  {$rects}
  <g font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11" shape-rendering="crispEdges" font-weight="bold">
{$texts}
  </g>
</svg>
SVG;
}

function render_social($segments) {
    $gutter = 6;
    $left = $segments[0];
    $right = $segments[1];
    $w = round($left['width'] + $gutter + $right['width']);
    $leftW = round($left['width']);
    $rightX = round($left['width'] + $gutter + 0.5);
    $rightW = round($right['width']);
    $leftCx = round($left['width'] / 2);
    $rightCx = round($left['width'] + $gutter + $right['width'] / 2);
    $leftLabel = xml_escape($left['label']);
    $rightLabel = xml_escape($right['label']);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="20">
  <style>
    a:hover #llink{fill:url(#b);stroke:#ccc}
  </style>
  <linearGradient id="a" x2="0" y2="100%">
    <stop offset="0" stop-color="#fcfcfc" stop-opacity="0"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <linearGradient id="b" x2="0" y2="100%">
    <stop offset="0" stop-color="#ccc" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <rect id="llink" x="0.5" y="0.5" width="{$leftW}" height="19" rx="2" fill="url(#a)" stroke="#d5d5d5"/>
  <rect x="{$rightX}" y="0.5" width="{$rightW}" height="19" rx="2" fill="#fafafa" stroke="#d5d5d5"/>
  <g font-family="Helvetica Neue,Helvetica,Arial,sans-serif" font-size="11" font-weight="bold">
    <text x="{$leftCx}" y="15" fill="#fff" text-anchor="middle">{$leftLabel}</text>
    <text x="{$leftCx}" y="14" fill="#333" text-anchor="middle">{$leftLabel}</text>
    <text x="{$rightCx}" y="15" fill="#fff" text-anchor="middle">{$rightLabel}</text>
    <text x="{$rightCx}" y="14" fill="#333" text-anchor="middle">{$rightLabel}</text>
  </g>
</svg>
SVG;
}

function render_svg($segments, $style) {
    switch ($style) {
        case 'flat-square':
            return render_flat_square($segments);
        case 'plastic':
            return render_plastic($segments);
        case 'for-the-badge':
            return render_for_the_badge($segments);
        case 'social':
            return render_social($segments);
        default:
            return render_flat($segments);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    header('Cache-Control: no-store');
    echo 'Method Not Allowed';
    exit();
}

$ctx = ShouldIDeploy::resolveContext($_GET);

if ($ctx === null) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('Access-Control-Allow-Origin: *');
    echo 'Timezone does not exist';
    exit();
}

$style = in_array($_GET['style'] ?? '', VALID_STYLES, true) ? $_GET['style'] : 'flat';
$segments = build_segments($ctx['verdict'], $ctx['reason'], $ctx['canDeploy'], $style);

header('Content-Type: image/svg+xml');
header('Cache-Control: ' . BADGE_CACHE);
header('Access-Control-Allow-Origin: *');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
echo render_svg($segments, $style);

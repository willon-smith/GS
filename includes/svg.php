<?php
/**
 * Inline SVG artwork. Everything visual on the site is drawn here so the
 * project has no image dependencies: the logo mark, the coffee bag used as the
 * product image, loose beans, steam and the icon set.
 */

declare(strict_types=1);

/** Brand mark: a bean whose crease grows into a leaf, inside a circle. */
function svg_mark(int $size = 40, string $bg = 'currentColor', string $fg = '#F4ECE1', string $class = ''): string
{
    return <<<SVG
<svg class="mark {$class}" width="{$size}" height="{$size}" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
  <circle cx="24" cy="24" r="24" fill="{$bg}"/>
  <g transform="rotate(-32 24 24)">
    <ellipse cx="24" cy="24" rx="9.5" ry="13.5" fill="{$fg}"/>
    <path d="M24 11.5c-4 4-4.5 8.5-1.5 12.5s2.5 8-1.5 12.5" fill="none" stroke="{$bg}" stroke-width="2.2" stroke-linecap="round"/>
  </g>
  <path d="M31.5 9.5c2.8-.9 5.4-.4 6.6 1.4-1.6 1.4-4.2 2-6.7 1.2-.5-.9-.4-1.9.1-2.6z" fill="{$fg}"/>
</svg>
SVG;
}

/** Product bag. $p is a product row; $id must be unique per instance on a page. */
function svg_bag(array $p, string $id = 'bag', string $class = ''): string
{
    $accent  = e($p['accent'] ?: '#C98B55');
    $size    = e($p['size_label'] ?? '');
    $name    = e($p['name'] ?? '');
    $origin  = e(trim(($p['origin'] ?? '') . ($p['region'] !== '' ? ' · ' . preg_replace('/,.*$/', '', $p['region']) : '')));
    $origin  = strtoupper($origin);
    $notes   = notes_list($p['tasting_notes'] ?? '');
    $notes   = e(implode('  ·  ', array_slice($notes, 0, 3)));
    $roast   = max(1, min(5, (int) ($p['roast_level'] ?? 3)));
    $dots = '';
    for ($i = 0; $i < 5; $i++) {
        $fill = $i < $roast ? '#2A1B14' : 'none';
        $cx = 130 + $i * 15;
        $dots .= "<circle cx=\"{$cx}\" cy=\"288\" r=\"4\" fill=\"{$fill}\" stroke=\"#2A1B14\" stroke-width=\"1.2\"/>";
    }
    $nameSize = mb_strlen($p['name'] ?? '') > 18 ? 14 : 17;

    return <<<SVG
<svg class="bag {$class}" viewBox="0 0 320 430" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{$name} {$size} bag">
  <defs>
    <linearGradient id="{$id}-body" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#1B3459"/>
      <stop offset=".22" stop-color="#132845"/>
      <stop offset=".6" stop-color="#0F1E33"/>
      <stop offset="1" stop-color="#0A1527"/>
    </linearGradient>
    <linearGradient id="{$id}-sheen" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#fff" stop-opacity=".35"/>
      <stop offset=".5" stop-color="#fff" stop-opacity=".06"/>
      <stop offset="1" stop-color="#fff" stop-opacity=".22"/>
    </linearGradient>
    <linearGradient id="{$id}-top" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#0B1729"/>
      <stop offset=".5" stop-color="#16294A"/>
      <stop offset="1" stop-color="#0B1729"/>
    </linearGradient>
    <radialGradient id="{$id}-glow" cx=".5" cy=".55" r=".6">
      <stop offset="0" stop-color="{$accent}" stop-opacity=".55"/>
      <stop offset="1" stop-color="{$accent}" stop-opacity="0"/>
    </radialGradient>
    <filter id="{$id}-shadow" x="-20%" y="-10%" width="140%" height="130%">
      <feGaussianBlur stdDeviation="9"/>
    </filter>
  </defs>

  <ellipse cx="160" cy="412" rx="118" ry="11" fill="#0B1729" opacity=".35" filter="url(#{$id}-shadow)"/>

  <path d="M62 386 Q160 404 258 386 L250 402 Q160 418 70 402 Z" fill="#070F1C"/>
  <path d="M56 58 Q160 44 264 58 L270 388 Q160 406 50 388 Z" fill="url(#{$id}-body)"/>
  <path d="M56 58 Q160 44 264 58 L264 84 Q160 70 56 84 Z" fill="url(#{$id}-top)"/>
  <path d="M64 96 Q160 84 256 96" fill="none" stroke="#fff" stroke-opacity=".16" stroke-width="1.5" stroke-dasharray="3 4"/>
  <path d="M58 60 Q160 46 262 60" fill="none" stroke="#fff" stroke-opacity=".22" stroke-width="1"/>
  <path d="M60 64 L54 386" fill="none" stroke="url(#{$id}-sheen)" stroke-width="7" stroke-linecap="round"/>
  <path d="M263 66 L266 384" fill="none" stroke="#fff" stroke-opacity=".05" stroke-width="10" stroke-linecap="round"/>

  <circle cx="236" cy="128" r="8" fill="#070F1C" stroke="#fff" stroke-opacity=".14"/>
  <circle cx="236" cy="128" r="3" fill="#0F1E33" stroke="#fff" stroke-opacity=".1"/>

  <rect x="82" y="146" width="156" height="206" rx="12" fill="#F4ECE1"/>
  <rect x="82" y="146" width="156" height="206" rx="12" fill="url(#{$id}-glow)" opacity=".5"/>
  <rect x="88" y="152" width="144" height="194" rx="9" fill="none" stroke="#2A1B14" stroke-opacity=".28" stroke-width="1"/>

  <g transform="translate(146 166) scale(.58)" opacity=".92">
    <circle cx="24" cy="24" r="24" fill="#0F1E33"/>
    <g transform="rotate(-32 24 24)">
      <ellipse cx="24" cy="24" rx="9.5" ry="13.5" fill="#F4ECE1"/>
      <path d="M24 11.5c-4 4-4.5 8.5-1.5 12.5s2.5 8-1.5 12.5" fill="none" stroke="#0F1E33" stroke-width="2.2" stroke-linecap="round"/>
    </g>
    <path d="M31.5 9.5c2.8-.9 5.4-.4 6.6 1.4-1.6 1.4-4.2 2-6.7 1.2-.5-.9-.4-1.9.1-2.6z" fill="#F4ECE1"/>
  </g>

  <text x="160" y="208" text-anchor="middle" font-family="'DM Sans', system-ui, sans-serif" font-size="7.5" letter-spacing="2.2" fill="#0F1E33" font-weight="600">COMMODITIES</text>
  <text x="160" y="228" text-anchor="middle" font-family="'Fraunces', Georgia, serif" font-style="italic" font-size="16" fill="#0F1E33">Good Steward</text>
  <line x1="112" y1="238" x2="208" y2="238" stroke="#0F1E33" stroke-opacity=".35" stroke-width="1"/>

  <text x="160" y="258" text-anchor="middle" font-family="'Fraunces', Georgia, serif" font-size="{$nameSize}" font-weight="600" fill="#2A1B14">{$name}</text>
  <text x="160" y="273" text-anchor="middle" font-family="'DM Sans', system-ui, sans-serif" font-size="7" letter-spacing="1.6" fill="#6E4A33">{$origin}</text>
  {$dots}
  <text x="160" y="308" text-anchor="middle" font-family="'DM Sans', system-ui, sans-serif" font-size="7.2" fill="#6E4A33">{$notes}</text>

  <path d="M82 322 h156 v18 a12 12 0 0 1 -12 12 h-132 a12 12 0 0 1 -12 -12 z" fill="{$accent}"/>
  <text x="160" y="342" text-anchor="middle" font-family="'DM Sans', system-ui, sans-serif" font-size="12" font-weight="700" letter-spacing="1" fill="#0F1E33">{$size}</text>
</svg>
SVG;
}

/** A single loose bean, used for decoration. */
function svg_bean(string $class = '', string $fill = '#4A2E1E', string $style = ''): string
{
    $styleAttr = $style !== '' ? ' style="' . e($style) . '"' : '';
    return <<<SVG
<svg class="bean {$class}"{$styleAttr} viewBox="0 0 40 56" aria-hidden="true" focusable="false">
  <ellipse cx="20" cy="28" rx="18" ry="26" fill="{$fill}"/>
  <path d="M20 4c-8 7-9 16-3 24s5 15-3 24" fill="none" stroke="#0B1729" stroke-opacity=".55" stroke-width="3.5" stroke-linecap="round"/>
</svg>
SVG;
}

/** Rising steam, three animated paths. */
function svg_steam(string $class = ''): string
{
    return <<<SVG
<svg class="steam {$class}" viewBox="0 0 80 80" aria-hidden="true" focusable="false">
  <path class="steam__p" d="M24 70 C14 58 34 52 24 40 C16 30 30 24 24 12" />
  <path class="steam__p" d="M40 74 C30 62 50 56 40 44 C32 34 46 28 40 14" />
  <path class="steam__p" d="M56 70 C46 58 66 52 56 40 C48 30 62 24 56 12" />
</svg>
SVG;
}

/** Icon set. Stroke icons, 24 grid. */
function icon(string $name, int $size = 22, string $class = ''): string
{
    $paths = [
        'bag'      => '<path d="M6 8h12l1 12H5L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'chevron'  => '<path d="M6 9l6 6 6-6"/>',
        'check'    => '<path d="M5 12l5 5L20 7"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'truck'    => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
        'leaf'     => '<path d="M5 19c0-9 6-14 14-14 0 9-5 14-14 14z"/><path d="M5 19c4-5 7-8 10-10"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'cup'      => '<path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M17 10h2a2 2 0 0 1 0 4h-2"/><path d="M8 3c0 1.5 1 1.5 1 3M12 3c0 1.5 1 1.5 1 3"/>',
        'star'     => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'pin'      => '<path d="M12 21s6-6.5 6-11a6 6 0 1 0-12 0c0 4.5 6 11 6 11z"/><circle cx="12" cy="10" r="2"/>',
        'mail'     => '<path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/>',
        'phone'    => '<path d="M6 3h4l2 5-2.5 1.5a11 11 0 0 0 5 5L16 12l5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 4 5a2 2 0 0 1 2-2z"/>',
        'clock'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
        'scale'    => '<path d="M12 4v16M6 8l6-4 6 4M4 14l2-6 2 6a2 2 0 0 1-4 0zM16 14l2-6 2 6a2 2 0 0 1-4 0z"/>',
        'fire'     => '<path d="M12 21c-4 0-7-3-7-7 0-3 2-5 3-7 0 2 1 3 2 3 0-4 2-6 4-8 0 4 5 6 5 12 0 4-3 7-7 7z"/>',
        'box'      => '<path d="M4 8l8-4 8 4v8l-8 4-8-4z"/><path d="M4 8l8 4 8-4M12 12v8"/>',
        'bean'     => '<ellipse cx="12" cy="12" rx="6.5" ry="9" transform="rotate(-30 12 12)"/><path d="M9.5 5.5c-2 3-2 6 0 8.5s2 5 0 7" transform="rotate(-30 12 12)"/>',
        'press'    => '<path d="M6 8h12v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2z"/><path d="M6 12h12M12 3v5M9 3h6"/>',
        'pourover' => '<path d="M5 6h14l-5 8v5h-4v-5z"/><path d="M8 6c0-2 2-3 4-3s4 1 4 3"/>',
        'moka'     => '<path d="M8 3h8l1 8H7zM7 11h10l1 10H6z"/><path d="M17 13h3v3h-3"/>',
        'espresso' => '<path d="M4 6h12v3a6 6 0 0 1-12 0z"/><path d="M16 7h2a2 2 0 0 1 0 4h-2"/><path d="M5 17h10M6 20h8"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
        'ship'     => '<path d="M3 15l2 4h14l2-4M5 15V9h14v6M9 9V5h6v4"/><path d="M2 15h20"/>',
        'home'     => '<path d="M4 11l8-7 8 7v9h-5v-6H9v6H4z"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'search'   => '<circle cx="11" cy="11" r="6"/><path d="M20 20l-4.5-4.5"/>',
        'grid'     => '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
        'list'     => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14.5 3h-5l-.3 2.6a7 7 0 0 0-1.7 1l-2.4-1-2 3.4L5.1 11a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.3 2.6h5l.3-2.6a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6c.1-.3.1-.7.1-1z"/>',
        'logout'   => '<path d="M10 4H5v16h5M14 8l4 4-4 4M8 12h10"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4z"/>',
        'tag'      => '<path d="M3 12l9-9h9v9l-9 9z"/><circle cx="16" cy="8" r="1.5"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
        'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 8 19z"/><path d="M9 9c0 3 3 6 6 6l1-2-2-1-1 1c-1 0-2-1-2-2l1-1-1-2z"/>',
        'instagram'=> '<rect x="4" y="4" width="16" height="16" rx="4"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r=".8" fill="currentColor"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
        'sparkle'  => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/>',
        'eye'      => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'upload'   => '<path d="M12 16V4M6 10l6-6 6 6M4 20h16"/>',
    ];
    $d = $paths[$name] ?? $paths['sparkle'];
    return '<svg class="icon icon-' . e($name) . ($class ? ' ' . e($class) : '') . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** Grain texture as a data URI, used for backgrounds. */
function grain_uri(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><filter id="n"><feTurbulence type="fractalNoise" baseFrequency=".9" numOctaves="3" stitchTiles="stitch"/><feColorMatrix type="saturate" values="0"/></filter><rect width="100%" height="100%" filter="url(#n)" opacity=".5"/></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

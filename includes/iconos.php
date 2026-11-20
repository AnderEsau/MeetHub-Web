<?php
/** Íconos de línea (SVG en línea, sin depender de internet). Uso: <?= icono('clock') ?> */
function icono(string $n, int $t = 18): string
{
    static $p = [
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin'      => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'wifi'     => '<path d="M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'door'     => '<path d="M6 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17M4 21h16"/><circle cx="14" cy="12" r=".8"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'check'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
        'doc'      => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4z"/>',
        'lock'     => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'image'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M21 16l-5-5-8 8"/>',
        'people'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0M16 5a3.5 3.5 0 0 1 0 7M18 20a6 6 0 0 0-3-5"/>',
        'building' => '<path d="M5 21V4h10v17M15 9h4v12M3 21h18M8 8h4M8 12h4M8 16h4"/>',
        'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>',
        'pdf'      => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 15h6"/>',
        'gear'     => '<circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/>',
        'eye'      => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'logout'   => '<path d="M10 4H5v16h5M15 8l4 4-4 4M19 12H9"/>',
    ];
    $d = $p[$n] ?? $p['info'];
    return '<svg class="mh-ico" xmlns="http://www.w3.org/2000/svg" width="' . $t . '" height="' . $t . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

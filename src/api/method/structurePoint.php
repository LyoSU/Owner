<?

$zoom = floatval($_POST['zoom']);
$lat = floatval($_POST['lat']);
$lng = floatval($_POST['lng']);

$range = 0.1*$zoom;

$structure = R::findCollection(
    'owner',
    '(`lat` < ? AND `lng` < ? AND `lat` > ? AND `lng` > ?) OR `type` > 2 ORDER BY `time` ASC ',
    [
        $lat+$range,
        $lng+$range,
        $lat-$range,
        $lng-$range,
    ]
);

$addressPoints = [];
$countPoints = 0;

while ( $row = $structure->next() ) {
    $countPoints++;
    $data = json_decode(base64_decode($row['data']),true);
    if(($row['action'] == 1 OR $row['action'] == 2) && $row['type'] == 1) $icon = 0;
    elseif($row['build'] == 0) $icon = $row['type'];
    else $icon = $row['type']*100+$row['build'];
    $addressPoints[] = [
        'place_id' => $row['place_id'],
        'lat' => floatval($row['lat']),
        'lng' =>  floatval($row['lng']),
        'type' => intval($row['type']),
        'icon' => intval($icon)
    ];
}

$user['last_zoom'] = $zoom;
$user['last_lat'] = $lat;
$user['last_lng'] = $lng;
$user['time'] = time();
R::store($user);

$return = [
    'ok' => true,
    'result' => [
        'money' => number_format($user['money'], 2, ',', '\''),
        'points' => $addressPoints
    ]
];
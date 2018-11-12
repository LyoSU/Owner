<?

$html = '';

$financeTypeName = [0 => 'question', 1 => 'shopping cart', 2 => 'building', 3 => 'arrow circle up', 4 => 'calendar check', 5 => 'gavel', 6 => 'briefcase'];

$finance = R::findCollection('finance', '`user` = ? ORDER BY `time` DESC LIMIT 100',[ $user['id'] ]);
while ( $row = $finance->next() ) {
    $owner = R::findOne('owner', 'id = ?', [ $row['owner'] ]);
    

    if($row['sum'] > 0) $sum = '+'.number_format($row['sum'], 2, ',', '\'');
    else $sum = number_format($row['sum'], 2, ',', '\'');
    
    $sum = "<span style='float: right;'>$sum<i class='dollar icon'></i></span>";

    $html .= "<span style='margin: 5px;'>" . date('d.m H:i', $row['time']) . "</span>".$sum."<br><div class='owner-name'><i class='" . $financeTypeName[$row['type']] ." icon'></i>" . Game::ownerName($owner['id']) . "</div><hr>";
}

$return = [
    'ok' => true,
    'result' => [
        'html' => $html
    ]
];
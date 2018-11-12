<?

$typeName = [1 => 'Дом', 2 => 'Улица', 3 => 'Нас. пункт', 4 => 'Район', 5 => 'Область', 6 => 'Страна'];

foreach ($typeName as $key => $value) {
    $values[] = ['name' => $value, 'value' => $key, 'selected' => true];
}

$html = '
<select name="type" multiple="" class="ui fluid dropdown">
    <option value="">Фильтр</option>
</select>
<script>
    $(".ui.dropdown").dropdown({
        values: '.json_encode($values).'
    });
</script>
<br>';

$ownerCollection = R::findCollection('owner', '`user` = ? ORDER BY `time` DESC',[ $user['id'] ]);
while ( $owner = $ownerCollection->next() ) {

    if(($owner['action'] == 1 OR $owner['action'] == 2) && $owner['type'] == 1) $icon = 0;
    elseif($owner['build'] == 0) $icon = $owner['type'];
    else $icon = $owner['type']*100+$owner['build'];

    $html .= "<img src='style/images/icon/$icon.png' width='15px' height='15px'> <b>" . Game::ownerName($owner['id']) . "</b><span style='float: right;'>15<i class='dollar icon'></i></span><br>";
    if($owner['type'] == 1){
        if($owner['build'] == 1){
            $linkNum = R::count('link', 'build = ?', [ $owner['id'] ]);
            $html .= "<b>Домов обслуживает:</b> ".$linkNum."/".$owner['level'];
        }else $html .= "<b>Уровень:</b> ".$owner['level'];
        $html .= "<br>";
        
        if($owner['build'] > 0) $html .= "<span class='structure-button' onclick='serviceInfo(\"".$owner['id']."\")'>Список домов</span>";

        if( $owner['action'] == 0 ){
            if($owner['build'] == 0) $html .= "<span class='structure-button' onclick='ownerBuildInfo(\"".$owner['id']."\")'>Строить</span>";
            $html .= "<span class='structure-button' onclick='ownerUpgradeInfo(\"".$owner['id']."\")'>Улучшить</span>";
            if($user['id'] == $user['id']){
                $link = R::findOne('link', 'type = ? AND house = ?', [ 1, $owner['id'] ]);
                if(isset($link)) $html .= "<span class='structure-button' onclick='ownerRentInfo(\"".$owner['id']."\")'>Сдать в аренду</span>";
            }
        }else{
            $actionType = ['1' => 'Строительство', '2' => 'Улучшение', '3' => 'Аренда'];
            if( $owner['action_end']-time() < 0 ){
                $html .= "<b>" . $actionType[$owner['action']] . " находится в стадии завершения</b>";
            }else{
                $lTime = hTime($owner['action_end']-time());
                $html .= "<b>" . $actionType[$owner['action']] . " завершится через:</b>" . $lTime;
            }
        }
    }else{
        $auction = R::findOne('auction', 'owner = ? AND type = 1', [ $owner['id'] ]);
        $leaderInfo = R::load('user', $auction['user']);
        $html .= "<b>Окончание аукциона:</b> " . date("H:i d.m", $owner['action_end']+60) . "<br>";
        $html .= "Ставка: " . $auction['bid'] . "<i class='dollar icon'></i>(" . $leaderInfo['login'] . ")";
        $html .= "<br><span class='structure-button' onclick='auctionInfo(\"".$owner['id']."\")'>Сделать ставку</span>";
    }
    $html .= "<hr>";
}

$return = [
    'ok' => true,
    'result' => [
        'html' => $html
    ]
];
<?
$types = ['owner' => 0, 'street_number' => 1, 'route' => 2, 'locality' => 3, 'administrative_area_level_2' => 4, 'administrative_area_level_1' => 5, 'country' => 6];
$typeName = [1 => 'Дом', 2 => 'Улица', 3 => 'Нас. пункт', 4 => 'Район', 5 => 'Область', 6 => 'Страна'];
$typeCost = [1 => 15, 2 => 800, 3 => 3000, 4 => 20000, 5 => 50000, 250000 ];

if( isset($_POST['structure']) ){

    $html = '';
    $icons = [];

    $structure = $_POST['structure'];

    foreach ($types as $type => $typeNum) {
        if( isset($structure[$type]) ){
            $owner = R::findOne('owner', 'place_id = ?', [ $structure[$type]['place_id'] ]);
            if(isset($owner)){
                $data = json_decode(base64_decode($owner['data']),true);

                if(($owner['action'] == 1 OR $owner['action'] == 2) && $owner['type'] == 1) $icon = 0;
                elseif($owner['build'] == 0) $icon = $owner['type'];
                else $icon = $owner['type']*100+$owner['build'];

                $typeNum = $owner['type'];
                $name = $owner['name'];
                $ownerUser = R::load('user', $owner['user']);
                $ownerName = $ownerUser['login'];
            }else{
                $icon = $typeNum;
                if( isset($structure[$type]['name']) ) $name = $structure[$type]['name'];
                else $name = 'Неопределенно';
                $ownerName = 'Нет';
            }

            if($type == 'owner') $display = 'visibility';
            else $display = 'none';

            $html .= "<div class='structure-title' id='$type' onclick='infoSpoiler(this)' style='cursor: pointer;'><img src='style/images/icon/$icon.png' width='15px' height='15px'> ".$name."</div><div class='structure-text' id='structure_text_$type' style='display: $display;'><b>Тип:</b> ".$typeName[$typeNum]."<br><b>Владелец:</b> $ownerName<br>";
            // if($typeNum > 1) $html .= "<span class='structure-button' onclick='drawBounds(\"".$structure[$type]['place_id']."\")'>Отобразить радиус</span>";
            if(!isset($owner)){
                $html .= "<b>Цена:</b> ".$typeCost[$typeNum]."<i class='dollar sign icon'></i><br><div style='display: block;' class='structure-button' onclick='structureBuy(\"".$structure[$type]['place_id']."\")'>Купить</div>";
            }else{

                if( $ownerUser['id'] == $user['id'] && isset($data['cash']) && $data['cash'] > 0 ) $html .= "<b>Кэш:</b> " . money($data['cash']) . "<span class='structure-button' onclick='cashGet(\"".$owner['id']."\")'>Забрать</span><br>";

                if($owner['type'] == 1){

                    $html .= "<hr>";
                    if($owner['build'] > 0) $html .= "<b>Постройка:</b> ".Game::$buildings[$owner['build']]['name']."<br>";
                    if($owner['build'] > 0){
                        $linkNum = R::count('link', 'build = ?', [ $owner['id'] ]);
                        $html .= "<b>Домов обслуживает:</b> ".$linkNum."/".$owner['level']*Game::$buildings[$owner['build']]['house']."<br>";
                    }
                    if( $owner['build'] !== 1 ) $html .= "<b>Уровень:</b> ".$owner['level'];
                    if($owner['build'] == 0){
                        $rent = money(pow($owner['level'], 0.75)*1.2);
                        $html .= "<br><b>Арендная плата:</b> " . $rent . "/час";
                    }
                    if($ownerUser['id'] == $user['id']){
                        $html .= "<br>";
                        if( $owner['action'] == 0 ){
                            if($owner['build'] > 0) $html .= "<span class='structure-button' onclick='serviceInfo(\"".$owner['id']."\")'>Список домов</span>";
                            if($owner['build'] == 0) $html .= "<span class='structure-button' onclick='ownerBuildInfo(\"".$owner['id']."\")'>Строить</span>";
                            $html .= "<span class='structure-button' onclick='ownerUpgradeInfo(\"".$owner['id']."\")'>Улучшить</span>";
                            if($owner['build'] == 0){
                                $link = R::findOne('link', 'type = ? AND house = ?', [ 1, $owner['id'] ]);
                                if(isset($link)) $html .= "<span class='structure-button' onclick='ownerRentInfo(\"".$owner['id']."\")'>Сдать в аренду</span>";
                                else $html .= "<br><i class='exclamation triangle icon'></i><b>Дом не обслуживается</b><br>Для сдачи дома в аренду необходим офис";
                            }
                        }else{
                            $actionType = ['1' => 'Строительство', '2' => 'Улучшение', '3' => 'Аренда'];
                            if( $owner['action_end']-time() < 0 ){
                                $html .= "<hr><b>" . $actionType[$owner['action']] . " находится в стадии завершения</b>";
                            }else{
                                $lTime = hTime($owner['action_end']-time());
                                $html .= "<hr><b>" . $actionType[$owner['action']] . " завершится через:</b><br>" . $lTime;
                            }
                        }
                    }
                }else{
                    $auction = R::findOne('auction', 'owner = ? AND type = 1', [ $owner['id'] ]);
                    $leaderInfo = R::load('user', $auction['user']);

                    $html .= "<hr><b>Окончание аукциона:</b> " . date("H:i d.m", $owner['action_end']+60) . "<br>";
                    $html .= "Ставка: " . $auction['bid'] . "<i class='dollar icon'></i>(" . $leaderInfo['login'] . ")";
                    $html .= "<br><span class='structure-button' onclick='auctionInfo(\"".$owner['id']."\")'>Сделать ставку</span>";
                }
                if($owner['build'] > 0) $html .= "<br><span class='structure-button' onclick='drawSquare(".$owner['lat'].", ".$owner['lng'].", ".Game::$buildings[$owner['build']]['distance'].")'>Отобразить радиус</span>";
            }
            $html .= "</div><hr>";
            $icons[] = intval($icon);
        }
    }

    $return = [
        'ok' => true,
        'result' => [
            'html' => $html,
            'icons' => $icons
        ]
    ];

}else{
    $return = [
        'ok' => false,
        'description' => 'Не удалось получить необходимые данные'
    ];
}
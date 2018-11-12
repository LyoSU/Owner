<?

$user['telegram_id'] = -1;
$user['vk_id'] = -1;
R::store($user);

$html = 'Аккаунт успешно сброшен. Необходимо перезапустить игру, для продолжения.';

$return = [
    'ok' => true,
    'result' => [
        'html' => $html
    ]
];
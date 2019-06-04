<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

lambda(function (array $event) {
    $ignoreRepositoriesJson = 'https://raw.githubusercontent.com/dfatt/php-digest/gh-pages/ignore-repositories.json';
    $phpDigestJson = 'https://raw.githubusercontent.com/pronskiy/php-digest/gh-pages/links.json';
    $endpointApi = 'https://github-trending-api.now.sh/repositories?language=php&since=daily';

    $ignoreRepositories = json_decode(file_get_contents($ignoreRepositoriesJson), true);
    $phpDigest = json_decode(file_get_contents($phpDigestJson), true);
    $phpDigest = array_merge(extractLinksDigest($phpDigest), $ignoreRepositories);

    $trends = json_decode(file_get_contents($endpointApi), true);

    $message = "<b>Новая подборка репозиториев (".(new DateTime('now'))->format('d.m.Y').")</b> \n\n";
    foreach ($trends as $repository) {
        if (!repositoryExistInDigest($phpDigest, $repository['url'])) {
            $message .= "🌎 Url: {$repository['url']}  \n🚀 CurrentStars: {$repository['currentPeriodStars']} ⭐️ Stars: {$repository['stars']} \n\n";
        };
    }

    sendToTelegram($message);
});

function extractLinksDigest($phpDigest)
{
    $rawArray = array_column($phpDigest, 'link');
    $phpDigest = [];
    foreach ($rawArray as $rawLink) {
        preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', stripslashes($rawLink), $matches);
        $matches = $matches[2];

        if (!empty($matches)) {
            foreach ($matches as $link) {
                if ($link !== '') {
                    $phpDigest[] = $link;
                }
            }
        }
    }

    return $phpDigest;
}

function repositoryExistInDigest($phpDigest, $link)
{
    foreach ($phpDigest as $value) {
        if ($value === $link) {
            return true;
        }
    }

    return false;
}

function sendToTelegram($message)
{
    $chatId = 0000000000;
    $params = [
        'chat_id' => $chatId,
        'parse_mode' => 'html',
        'disable_web_page_preview' => 1,
        'text' => $message,
    ];
    $ch = curl_init('https://api.telegram.org/bot00000000:0000000000000/sendMessage');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}
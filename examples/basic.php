<?php

require_once __DIR__ . '/../src/AssetLoader.php';

$loader = new AssetLoader();

#$loader
#    ->add('bootstrap')
#    ->add('jquery')
#    ->addInlineVar('debug', true);

echo $loader->renderHead('gwBootStrapper Demo');
?>

<div class="container mt-5">
    <h1 class="text-success">gwBootStrapper läuft 🚀</h1>
</div>

<?php
echo $loader->renderFoot();
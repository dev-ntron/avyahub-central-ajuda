<?php
// ... arquivo existente acima
// No lugar de header('Location: /install/?step=3')
header('Location: ' . url('install/?step=3'));
exit;
// ... demais redirecionamentos e assets devem usar BASE_PATH/url()

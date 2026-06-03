<?php

    //================================================
    // Rotas da API
    //================================================
    include __DIR__.'/api/v1/default.php';

    #=======================================
    # Aplicativos
    #=======================================
    include __DIR__.'/api/v1/apps/auth/authentication.php';
    include __DIR__.'/api/v1/apps/approutes/contacts.php';
    include __DIR__.'/api/v1/apps/approutes/conversations.php';
    include __DIR__.'/api/v1/apps/approutes/messages.php';
    include __DIR__.'/api/v1/apps/approutes/keys.php';
    include __DIR__.'/api/v1/apps/approutes/signal.php';
    include __DIR__.'/api/v1/apps/approutes/group_sender_keys.php';
    include __DIR__.'/api/v1/apps/approutes/message_actions.php';




?>
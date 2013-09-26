<?php

class AppError extends ErrorHandler {
        function notAllowed($params) {
                $this->controller->set('account', $params['account']);
                $this->_outputMessage('not_allowed');
        }
}
?>

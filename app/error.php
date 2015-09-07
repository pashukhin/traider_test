<?php

/**
 * @param Exception | null $e
 *
 * @return array | null
 */
function exceptionToArray(\Exception $e = null) {
    if (null !== $e) {
        return [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTrace(),
            'previous' => exceptionToArray($e->getPrevious()),
        ];
    } else {
        return null;
    }
}

register_shutdown_function(function (){
    if ($error = error_get_last() ) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(
            ['fatal' => $error],
            JSON_PRETTY_PRINT
        );
    }
});

set_exception_handler(function (\Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(
        [
            'exception' => exceptionToArray($e),
        ],
        JSON_PRETTY_PRINT
    );
});
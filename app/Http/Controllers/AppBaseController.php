<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use InfyOm\Generator\Utils\ResponseUtil;
use Response;

/**
 * Classe base de retorno de dados da API
 */
class AppBaseController extends Controller
{
    /**
     * @param mixed $result
     * @param string $message
     * @return JsonResponse
     */
    public function sendResponse(mixed $result, string $message): JsonResponse
    {
        unset($result['first_page_url']);
        unset($result['next_page_url']);
        unset($result['prev_page_url']);
        unset($result['last_page_url']);
        unset($result['path']);
        unset($result['links']);
        return Response::json(ResponseUtil::makeResponse($message, $result));
    }

    /**
     * @param string $error
     * @param int $code
     * @return JsonResponse
     */
    public function sendError(string $error, int $code = 404): JsonResponse
    {
        return Response::json(ResponseUtil::makeError($error), $code);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    public function sendSuccess(string $message): JsonResponse
    {
        return Response::json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * @param array $message
     * @return JsonResponse
     */
    public function response(array $message)
    {
        if ($message['code'] == 200) {
            return Response::json(ResponseUtil::makeResponse($message['message'], []));
        }

        return Response::json(ResponseUtil::makeError($message['message']), $message['code']);
    }
}

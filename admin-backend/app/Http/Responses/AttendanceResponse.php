<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;

class AttendanceResponse implements Responsable
{
    /**
     * The data to be included in the response.
     *
     * @var mixed
     */
    protected $data;

    /**
     * The HTTP status code.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * The response message.
     *
     * @var string
     */
    protected $message;

    /**
     * Whether the response indicates an error.
     *
     * @var bool
     */
    protected $errorFlag;

    /**
     * Create a new response instance.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param bool $errorFlag
     */
    public function __construct($data = null, string $message = '', int $statusCode = 200, bool $errorFlag = false)
    {
        $this->data = $data;
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->errorFlag = $errorFlag;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return response()->json([
            'status' => $this->errorFlag ? 'error' : 'success',
            'message' => $this->message,
            'error_flag' => $this->errorFlag,
            'data' => $this->data,
        ], $this->statusCode);
    }

    /**
     * Static factory method for a success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return self
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): self
    {
        return new self($data, $message, $statusCode, false);
    }

    /**
     * Static factory method for an error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return self
     */
    public static function error(string $message = 'An error occurred', int $statusCode = 500, $errors = null): self
    {
        return new self($errors, $message, $statusCode, true);
    }
}

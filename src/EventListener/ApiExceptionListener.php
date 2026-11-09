<?php

namespace App\EventListener;

use Nebkam\SymfonyTraits\Exception\ValidationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Every error of the API has the same JSON shape (the one Lexik already uses for 401):
 *
 *     {"code": 404, "message": "Pet not found."}
 *     {"code": 422, "message": "Validation failed.", "errors": [{"field": "email", "message": "..."}]}
 *
 * Runs after the security listener (priority 1), which has already turned access errors
 * into 401 (no/invalid token, answered by Lexik) or 403 (AccessDeniedHttpException).
 */
#[AsEventListener(KernelEvents::EXCEPTION, priority: -10)]
final readonly class ApiExceptionListener
{
    public function __construct(
        #[Autowire(param: 'kernel.debug')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ValidationException) {
            $event->setResponse($this->response(Response::HTTP_UNPROCESSABLE_ENTITY, 'Validation failed.', ['errors' => $exception->getErrors()]));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
            // our own messages are meant for the client; framework ones (e.g. "No route found for ...") are replaced
            $message = $exception->getMessage();
            if ($message === '' || str_starts_with($message, 'No route found') || str_contains($message, 'object not found by')) {
                $message = Response::$statusTexts[$code] ?? 'Error';
            }
            $response = $this->response($code, $message);
            $response->headers->add($exception->getHeaders());
            $event->setResponse($response);

            return;
        }

        $extra = $this->debug ? ['debug' => sprintf('%s: %s', $exception::class, $exception->getMessage())] : [];
        $event->setResponse($this->response(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal server error.', $extra));
    }

    /** @param array<string, mixed> $extra */
    private function response(int $code, string $message, array $extra = []): JsonResponse
    {
        return new JsonResponse(['code' => $code, 'message' => $message] + $extra, $code);
    }
}

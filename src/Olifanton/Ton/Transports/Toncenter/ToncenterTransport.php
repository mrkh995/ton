<?php declare(strict_types=1);

namespace Olifanton\Ton\Transports\Toncenter;

use Olifanton\Interop\Boc\Cell;
use Olifanton\Ton\Contract;
use Olifanton\Ton\Contracts\Exceptions\ContractException;
use Olifanton\Ton\Contracts\Messages\ResponseStack;
use Olifanton\Ton\Exceptions\TransportException;
use Olifanton\Ton\Transport;
use Olifanton\TypedArrays\Uint8Array;
use Olifanton\Ton\Transports\Toncenter\Exceptions as TncEx;

class ToncenterTransport implements Transport
{
    public function __construct(
        private readonly ToncenterV2Client $client,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function runGetMethod(Contract $contract, string $method, array $stack = []): ResponseStack
    {
        try {
            $address = $contract->getAddress();
        } catch (ContractException $e) {
            throw new TransportException(
                "Address error: " . $e->getMessage(),
                0,
                $e,
            );
        }

        try {
            $response = $this
                ->client
                ->runGetMethod(
                    $address,
                    $method,
                    $stack,
                );

            if ($response->exitCode !== 0) {
                throw new TransportException(
                    "Non-zero exit code, error: " . $response->exitCode,
                    $response->exitCode,
                );
            }

            return ResponseStack::parse($response->stack);
        } catch (TncEx\ClientException | TncEx\TimeoutException | TncEx\ValidationException $e) {
            throw new TransportException(
                sprintf(
                    "Get method error: %s; address: %s, method: %s",
                    $e->getMessage(),
                    $address->toString(true),
                    $method,
                ),
                0,
                $e,
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function send(Uint8Array | string | Cell $boc): void
    {
        try {
            $this
                ->client
                ->sendBoc(
                    $boc,
                );
        } catch (TncEx\ClientException | TncEx\TimeoutException | TncEx\ValidationException $e) {
            throw new TransportException(
                sprintf(
                    "Sending error: %s",
                    $e->getMessage(),
                ),
                0,
                $e,
            );
        }
    }
}

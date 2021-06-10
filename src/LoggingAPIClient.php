<?php


namespace DespatchCloud\LoggingAPI;


use CURLFile;
use DespatchCloud\LoggingAPI\Exceptions\ClientException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoggingAPIClient
{
    /**
     * @var string
     */
    protected $publicUrl;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string|null
     */
    protected $privateUrl;

    /**
     * LoggingAPI constructor.
     */
    public function __construct(string $publicUrl, string $apiKey, string $privateUrl = null)
    {
        $this->publicUrl = $publicUrl;
        $this->apiKey = $apiKey;
        $this->privateUrl = $privateUrl;
    }

    /**
     * @param string|null $uri
     * @return string
     */
    protected function url(string $uri = null): string
    {
        return ($this->privateUrl ?: $this->publicUrl) . $uri;
    }

    /**
     * @param array $headers
     * @return array|string[]
     */
    protected function headers(array $headers = []): array
    {
        return array_merge([
            "Authorization: Bearer {$this->apiKey}",
            'Accept: application/json'
        ], $headers);
    }

    /**
     * Store the log.
     *
     * @param string $contents
     * @param string|null $id
     * @return string
     * @throws ClientException
     */
    public function store(string $contents, string $id = null): string
    {
        $ch = curl_init($this->url('/api/logs/create'));

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'id' => $id,
            'contents' => $contents,
        ]));

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers([
            'Content-Type: application/json',
        ]));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $this->processResult($status, $result);
    }

    /**
     * Upload a log file.
     *
     * @param string $path
     * @param string|null $id
     * @return string
     * @throws ClientException
     */
    public function upload(string $path, string $id = null): string
    {
        $file = new CURLFile($path);
        $ch = curl_init($this->url('/api/logs/upload'));

        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'id' => $id,
            'file' => $file,
        ]);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers([
            'Content-Type: multipart/form-data',
        ]));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $this->processResult($status, $result);
    }

    /**
     * Get the log content.
     *
     * @param string $id
     * @return bool|string
     * @throws ClientException
     */
    public function get(string $id)
    {
        $ch = curl_init($this->url("/api/logs/{$id}"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $this->processResult($status, $result);
    }

    /**
     * Create a streamed response for a given file.
     *
     * @param  string  $path
     * @param  string|null  $name
     * @param  array|null  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function responsetest($path, $name = null, array $headers = [], $disposition = 'inline')
    {
        $response = new StreamedResponse;

        $filename = $name ?? basename($path);

        $disposition = $response->headers->makeDisposition(
            $disposition, $filename, $this->fallbackName($filename)
        );

        $response->headers->replace($headers + [
                'Content-Type' => $this->mimeType($path),
                'Content-Length' => $this->size($path),
                'Content-Disposition' => $disposition,
            ]);

        $response->setCallback(function () use ($path) {
            $stream = $this->readStream($path);
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }

    /**
     * Get the log content.
     *
     * @param string $id
     * @return Response
     * @throws ClientException
     */
    public function response(string $id)
    {
        $ch = curl_init($this->url("/api/logs/{$id}"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $headerSize);
        $result = substr($result, $headerSize);
        $result = $this->processResult($status, $result);

        curl_close($ch);

        $response = new Response;
        $response->headers->replace($this->getHeaders($header, [
            'Content-Type',
            'Content-Length',
            'Content-Disposition',
        ]));

        $response->setContent($result);
        return $response;
    }

    /**
     * @param $status
     * @param $result
     * @return mixed
     * @throws ClientException
     */
    protected function processResult($status, $result)
    {
        if ($status != 200) {
            $message = $result ?? 'Something wrong.';
            throw new ClientException($message, $status);
        }

        return $result;
    }

    /**
     * @param string $header
     * @param array $keys
     * @return array
     */
    protected function getHeaders(string $header, array $keys = []): array
    {
        $results = [];
        foreach (explode(PHP_EOL, $header) as $line) {
            if (preg_match('/([^\:]+)\:(.*)/', $line, $matches)) {
                if (in_array($matches[1], $keys)) {
                    $results[$matches[1]] = trim($matches[2]);
                }
            }
        }

        return $results;
    }
}

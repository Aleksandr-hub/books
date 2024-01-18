<?php

// src/Service/FileBookService.php
namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileBookService
{
    private $httpClient;

    private $slugger;

    public function __construct(HttpClientInterface $httpClient, SluggerInterface $slugger)
    {
        $this->httpClient = $httpClient;
        $this->slugger = $slugger;
    }

    public function getFileSize($url)
    {
        try {
            $response = $this->httpClient->request('HEAD', $url);
            // Перевірка, чи відповідь має заголовок Content-Length
            if ($response->getHeaders()['content-length'][0]) {
                return (int) $response->getHeaders()['content-length'][0];
            }
        } catch (\Exception $e) {
            // Обробка помилок, якщо вони виникнуть
            // Наприклад, якщо файл не знайдено або сервер відмовив у доступі
        }

        return null;
    }

    public function savePhoto($imageUrl, $dirName) :string
    {
        $filename = pathinfo($imageUrl, PATHINFO_FILENAME);
        $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);

        $safeFilename = $this->slugger->slug($filename);

        $fileSize = $this->getFileSize($imageUrl);

        // Перевірка розширення та розміру файлу
        if (in_array($extension, ['jpg', 'png']) && $fileSize <= 2 * 1024 * 1024) {
            // Завантаження зображення за посиланням в директорію проекту
            $uploadDir = $dirName . '/public/uploads/images/';
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            // Завантаження зображення
            $filesystem = new Filesystem();
            $filesystem->copy($imageUrl, $uploadDir . $newFilename);

            // Збереження шляху до файлу в об'єкті книги
            return $newFilename;
        }

        return '';

    }
}

<?php

// src/Controller/Api/BookController.php
namespace App\Controller\Api;

use App\Entity\Author;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\FileBookService;
use App\Service\SerializedAuthorsBooks;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/books", name="api_book_")
 */
class BookController extends AbstractController
{
    private ManagerRegistry $doctrine;

    private $paginator;

    private $fileService;

    private $serializer;

    public function __construct(ManagerRegistry $doctrine, PaginatorInterface $paginator, FileBookService $fileService, SerializerInterface $serializer)
    {
        $this->doctrine = $doctrine;
        $this->paginator = $paginator;
        $this->fileService = $fileService;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // Перевірка наявності обов'язкових ключів
        $requiredKeys = ['title', 'authors'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data ?? [])) {
                return $this->json(['error' => 'Missing required key: ' . $key], Response::HTTP_BAD_REQUEST);
            }
        }

        $book = new Book();
        $book->setTitle($data['title']);
        $book->setDescription($data['description'] ?? null);

        // Встановлення publicationDate як поточної дати та часу, якщо вона відсутня
        $publicationDate = $data['publicationDate'] ?? date("d.m.Y", time());
        $book->setPublicationDate(new \DateTime($publicationDate));

        // Додайте авторів до книги (припускаючи, що автори передаються у вигляді масиву ID)
        $authors = $this->doctrine->getRepository(Author::class)->findBy(['id' => $data['authors']]);
        foreach ($authors as $author) {
            $book->addAuthor($author);
        }


        // Обробка посилання на зображення
        $imageUrl = $data['image'] ?? null;

        if ($imageUrl) {
            $newFilename = $this->fileService->savePhoto($imageUrl, $this->getParameter('kernel.project_dir'));
            if (!empty($newFilename)) {
                $book->setImage('/uploads/images/' . $newFilename);
            } else {
                return $this->json(['error' => 'Invalid file. Please upload a valid jpg or png image (max size: 2MB).'], Response::HTTP_BAD_REQUEST);
            }

        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($book);
        $entityManager->flush();

        return $this->json(['message' => 'Book created successfully'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/list", name="list", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        $booksQuery = $this->doctrine->getRepository(Book::class)->createQueryBuilder('a')
            ->getQuery();
        $pagination = $this->paginator->paginate(
            $booksQuery,
            $request->query->getInt('page', 1), // Номер поточної сторінки
            10 // Кількість елементів на сторінці
        );
        $books = $pagination->getItems();
        $serializedBook = $this->serializer->normalize($books, null, ['groups' => 'list']);

        return $this->json([
            'books' => $serializedBook,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalPages' => $pagination->getPageCount(),
                'totalCount' => $pagination->getTotalItemCount(),
            ],
        ], Response::HTTP_OK);

        return $this->json($data, Response::HTTP_OK);
    }

    /**
     * @Route("/search", name="search_by_author", methods={"GET"})
     */
    public function searchByAuthor(Request $request, BookRepository $bookRepository): JsonResponse
    {
        // Отримати дані з тіла запиту
        $requestData = json_decode($request->getContent(), true);

        // Перевірка, чи існує ключ 'authorLastName' в отриманих даних
        if (!isset($requestData['authorLastName'])) {
            return new JsonResponse(['error' => 'Missing authorLastName in request body'], 400);
        }

        // Отримати прізвище автора з отриманих даних
        $authorLastName = $requestData['authorLastName'];

        // Здійснити пошук книг за прізвищем автора
        $entityManager = $this->doctrine->getManager();
        $books = $bookRepository->findByAuthorLastName($authorLastName);

        // Серіалізація результату в JSON
        $serializedBook = $this->serializer->normalize($books, null, ['groups' => 'list']);
        return new JsonResponse(['books' => $serializedBook]);
    }

    /**
     * @Route("/{id}", name="api_books_show", methods={"GET"})
     */
    public function show(Book $book): JsonResponse
    {
        // Серіалізація результату в JSON
        $serializedBook = $this->serializer->normalize($book, null, ['groups' => 'list']);

        return new JsonResponse(['book' => $serializedBook]);
    }

    /**
     * @Route("/{id}", name="api_books_edit", methods={"PUT"})
     */
    public function edit(Request $request, Book $book, ValidatorInterface $validator): JsonResponse
    {
        // Отримати дані з тіла запиту
        $requestData = json_decode($request->getContent(), true);

        // Оновити властивості книги
        $book->setTitle($requestData['title'] ?? $book->getTitle());
        $book->setDescription($requestData['description'] ?? $book->getDescription());

        $publicationDate = isset($requestData['publicationDate']) ? new \DateTime($requestData['publicationDate']) : $book->getPublicationDate();
        $book->setPublicationDate($publicationDate);

        $authors = $this->doctrine->getRepository(Author::class)->findBy(['id' => $requestData['authors']]);
        foreach ($authors as $author) {
            $book->addAuthor($author);
        }

        // Обробка посилання на зображення
        $imageUrl = $requestData['image'] ?? null;

        if ($imageUrl) {
            $newFilename = $this->fileService->savePhoto($imageUrl, $this->getParameter('kernel.project_dir'));
            if (!empty($newFilename)) {
                $book->setImage('/uploads/images/' . $newFilename);
            } else {
                return $this->json(['error' => 'Invalid file. Please upload a valid jpg or png image (max size: 2MB).'], Response::HTTP_BAD_REQUEST);
            }

        }

        // Виконати валідацію
        $errors = $validator->validate($book);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => $errorMessages], 400);
        }

        // Зберегти зміни в базі даних
        $entityManager = $this->doctrine->getManager();
        $entityManager->flush();

        // Серіалізація оновленої книги в JSON
        $serializedBook = $this->serializer->normalize($book, null, ['groups' => 'list']);

        return new JsonResponse(['book' => $serializedBook]);
    }


}

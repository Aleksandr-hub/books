<?php

// src/Controller/Api/AuthorController.php
namespace App\Controller\Api;

use App\Entity\Author;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/authors", name="api_author_")
 */
class AuthorController extends AbstractController
{

    private ManagerRegistry $doctrine;

    private $paginator;

    public function __construct(ManagerRegistry $doctrine, PaginatorInterface $paginator)
    {
        $this->doctrine = $doctrine;
        $this->paginator = $paginator;
    }
    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $data = json_decode($request->getContent(), true);

        // Перевірка наявності обов'язкових ключів
        $requiredKeys = ['firstName', 'lastName'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data ?? [])) {
                return $this->json(['error' => 'Missing required key: ' . $key], Response::HTTP_BAD_REQUEST);
            }
        }

        // Валідація даних
        $author = new Author();
        $author->setFirstName($data['firstName']);
        $author->setLastName($data['lastName']);
        $author->setMiddleName($data['middleName']);

        $errors = $validator->validate($author);

        if (count($errors) > 0) {
            // Якщо є помилки валідації, поверніть їх у відповідь
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(['errors' =>  $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Збереження автора в базі даних
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($author);
        $entityManager->flush();

        return $this->json(['message' => 'Author created successfully'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/list", name="list", methods={"GET"})
     */
    public function list(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $authorsQuery = $this->doctrine->getRepository(Author::class)->createQueryBuilder('a')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $authorsQuery,
            $requestData['page'] ?? 1, // Номер поточної сторінки
            10 // Кількість елементів на сторінці
        );

        $authorList = [];
        foreach ($pagination->getItems() as $author) {
            $authorList[] = [
                'id' => $author->getId(),
                'firstName' => $author->getFirstName(),
                'lastName' => $author->getLastName(),
                'middleName' => $author->getMiddleName(),
            ];
        }

        return $this->json([
            'authors' => $authorList,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalPages' => $pagination->getPageCount(),
                'totalCount' => $pagination->getTotalItemCount(),
            ],
        ], Response::HTTP_OK);
    }
}
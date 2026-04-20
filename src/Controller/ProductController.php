<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_product_catalog')]
    public function index(ProductRepository $productRepository, Request $request): Response
    {
        // 1. Capture the search term from the URL (e.g., ?q=shirt)
        $searchTerm = $request->query->get('q');

        // 2. If the user searched for something, filter the database
        if ($searchTerm) {
            $products = $productRepository->createQueryBuilder('p')
                ->where('p.name LIKE :term OR p.category LIKE :term')
                ->setParameter('term', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();
        } else {
            // 3. Otherwise, just show all products normally
            $products = $productRepository->findAll();
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    // --- ADD THIS NEW FUNCTION ---
    #[Route('/product/new', name: 'app_product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // 1. Create a blank Product object
        $product = new Product();
        
        // 2. Build the form based on that blank object
        $form = $this->createForm(ProductType::class, $product);
        
        // 3. Inspect the incoming request to see if the user clicked submit
        $form->handleRequest($request);

        // 4. If they submitted valid data, save it to the SQLite database
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            // Redirect back to the catalog to see the new item!
            return $this->redirectToRoute('app_product_catalog');
        }

        // 5. If they haven't submitted yet, just show them the blank form
        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }
}
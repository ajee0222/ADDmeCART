<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(int $id, ProductRepository $productRepo, EntityManagerInterface $entityManager): Response
    {
        // 1. MOCK LOGIN: Find the first user, or create a test user if the database is empty
        $userRepo = $entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy([]);
        
        if (!$user) {
            $user = new User();
            $user->setEmail('test@addmecart.com'); // Required by your entity
            $user->setPassword('password123');     // Required by your entity
            $user->setFirstName('Test');
            $user->setLastName('Shopper');
            $user->setSecurityPin('1234');         // Fixed method name!
            $entityManager->persist($user);
        }

        // 2. Fetch the Cart. If the user doesn't have one, build it.
        $cart = $user->getCart();
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
        }

        // 3. Find the Product they clicked on
        $product = $productRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        // 4. ENFORCE THE LIMIT: Calculate total items currently in the cart
        $currentTotalItems = 0;
        foreach ($cart->getCartItems() as $item) {
            $currentTotalItems += $item->getQuantity();
        }

        if ($currentTotalItems >= 300) {
            $this->addFlash('error', 'Cart Limit Reached: You cannot add more than 300 items.');
            return $this->redirectToRoute('app_product_catalog');
        }

        // 5. Check if the product is already in the cart
        $existingCartItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct() === $product) {
                $existingCartItem = $item;
                break;
            }
        }

        if ($existingCartItem) {
            // Increase quantity if it's already there
            $existingCartItem->setQuantity($existingCartItem->getQuantity() + 1);
        } else {
            // Create a brand new line item
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQuantity(1);
            $cartItem->setCart($cart);
            $entityManager->persist($cartItem);
        }

        // 6. Save everything to SQLite and show a success message
        $entityManager->flush();
        $this->addFlash('success', $product->getName() . ' was added to your cart!');

        return $this->redirectToRoute('app_product_catalog');
    }
}
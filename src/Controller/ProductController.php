<?php

namespace App\Controller;
use App\Form\ProductType;
use App\Form\RechercheCatType;
use App\Form\RechercheType;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ProductController extends AbstractController
{

    /**
     * @Route("/products", name="product_index", methods={"GET", "POST"})
     */
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $tableForm = $this->createForm(ProductType::class);
        $tableForm->handleRequest($request);

        $searchForm = $this->createForm(RechercheType::class, null, [
            'method' => 'POST',
            'action' => $this->generateUrl('product_index'),
        ]);
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted()) {
            $formData = $searchForm->getData();
            $products = $productRepository->findByFilters([
                    'q' => $formData->getName(),
                    'f' => $formData->getCategory()]
            );
        } else {
            $products = $productRepository->findAll();
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'tableForm' => $tableForm->createView(),
            'searchForm' => $searchForm->createView(),
        ]);
    }

    /**
     * @Route("/products/show/{id}", name="product_show", methods={"GET"})
     */
    public function show(ProductRepository $productRepository, EntityManagerInterface $entityManager, $id):Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('The product with ID ' . $id . ' does not exist.');
        }
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);

    }


    /**
     * @Route("/products/export", name="product_export", methods={"GET", "POST"})
     */

    public function export(Request $request, EntityManagerInterface $entityManager): Response
    {

// Start the output buffer.
        ob_start();

// Set PHP headers for CSV output.
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=csv_export.csv');

// Create the headers.

        $header_args = array('ID','Name','Price','Category');
        $data=array($entityManager->getRepository(Product::class)->findAll());

// Clean up output buffer before writing anything to CSV file.
        ob_end_clean();

// Create a file pointer with PHP.
        $output = fopen('php://output', 'w');

// Write headers to CSV file.
        fputcsv($output, $header_args);

// Loop through the prepared data to output it to CSV file.
        foreach ($data as $data_item) {

            fputcsv($output, (array)$data_item);
        }

// Close the file pointer with PHP with the updated output.
        fclose($output);
        exit;
    }






    /**
     * @Route("/products/new", name="product_new", methods={"GET", "POST"})
     */
     public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if the 'name' field is not empty
            if (empty($product->getName())) {
                $form->get('name')->addError(new FormError('Your first name must be at least characters long'));
            } else {
                // Persist and flush the entity
                $entityManager->persist($product);
                $entityManager->flush();


                return $this->redirectToRoute('product_index');
            }
        }

        return $this->render('product/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

 /**
 * @Route("/products/edit/{id}", name="product_edit", methods={"GET", "POST"})
 */
public function edit(Request $request, EntityManagerInterface $entityManager, $id): Response
{
    $product = $entityManager->getRepository(Product::class)->find($id);

    if (!$product) {
        throw $this->createNotFoundException('Le produit avec l\'ID ' . $id . ' n\'existe pas.');
    }

    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        return $this->redirectToRoute('product_index');
    }

    return $this->render('product/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}

/**
 * @Route("/products/delete/{id}", name="product_delete", methods={"POST"})
 */
public function delete(Request $request, EntityManagerInterface $entityManager, $id): Response
{
    $product = $entityManager->getRepository(Product::class)->find($id);

    if (!$product) {
        throw $this->createNotFoundException('The product with ID ' . $id . ' does not exist.');
    }

    if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
        $entityManager->remove($product);
        $entityManager->flush();
    }

    return $this->redirectToRoute('product_index');
}



}
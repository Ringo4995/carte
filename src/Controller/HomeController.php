<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\CarteType;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;


class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            
        ]);
    }

    #[Route('/add', name: 'app_add')]
    public function newGame(Request $requete, PersistenceManagerRegistry $manager)
    {
        $carte = new Card();
        $formulaire = $this->createForm(CarteType::class, $carte);
        $formulaire->handleRequest($requete);
        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $imgCouverture = $formulaire->get('Image')->getData();
            if ($imgCouverture) {
                $newImgcarte = "cover" . uniqid() . "." . $imgCouverture->guessExtension();

                try {
                    $imgCouverture->move($this->getParameter('dossierCarte'), $newImgcarte);
                } catch (FileException $e) {
                    $e->getMessage();
                }
            }
            $carte->setImage($newImgcarte);
            $OM = $manager->getManager();
            $OM->persist($carte);
            $OM->flush();

            return $this->redirectToRoute('app_home');
        }
        return $this->render('home/creation.html.twig', [
            "formulaire" => $formulaire->createView()
        ]);
    }


    #[Route('/affichercarte', name: "affichercarte")]
    public function afficherJoueur(CardRepository $cartesRepository)
    {
        $cartes = $cartesRepository->findAll();
        return $this->render("home/affichercarte.html.twig", [
            "cartes" => $cartes
        ]);
    }

    #[Route('/pdf/{cardId}', name: "pdfmaker")]
    public function creerPDF(CardRepository $cartesRepository, $cardId)
    {   
        $cartes = $cartesRepository->find($cardId);
        
        $imagecarte = $cartes->getImage();
        
        $newimage = $this->getParameter('kernel.project_dir') . '/public/dossierCarte/' . $imagecarte;
        $cartes->setImage($newimage);

        if (!$cartes) {
            throw $this->createNotFoundException('Card not found');
        }

        // on crée l'instance dompdf
        $dompdf = new Dompdf();
        // on met en place des options ( optionnel ducou )
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $dompdf->setOptions($options);
        // on récupère le contenu de notre carte
        $html = $this->renderView("card-template.html.twig", ["cartes" => $cartes]);
        // on met le contenu html dans le dompdf
        $dompdf->loadHtml($html);
        // ici on gère l'orientation et la taille de la carte
        $dompdf->setPaper('A8', 'portrait');
        // effectuer le render vers un pdf
        $dompdf->render();
        // générer le pdf
        $pdfOutput = $dompdf->output();
        // on sauvegarde le pdf à un endroit
        /* $pdfFilePath = $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/dossierCarte/cover653a7db4be8ef.png');
        $this->file_force_contents($pdfFilePath, $pdfOutput); */
        // on return le pdf
        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /* #[Route('/pdf/carte{cardId}', name: 'app_pdf_generator')]
    public function test(Request $requete, CardRepository $cartesRepository, $cardId): Response
    {   
        $card = new Card();

        $form = $this->createForm(CarteType::class, $card);
        
        $form->handleRequest($requete);

        $cartes = $cartesRepository->find($cardId);

        if (!$cartes) {
            throw $this->createNotFoundException('Card not found');
        }
        
        // return $this->render('pdf_generator/index.html.twig', [
        //     'controller_name' => 'PdfGeneratorController',
        // ]);
        $data = [
            'imageSrc'  => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/dossierCarte/cover653a7db4be8ef.png'),
            'name'         => '{{Titre}}',
            'address'      => 'USA'
        ];
        $html =  $this->renderView('home/cartepdf.html.twig', $data);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
         
        return new Response (
            $dompdf->stream('resume', ["Attachment" => false]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/pdf']
        );
    } */

    /* public function file_force_contents($dir, $contents)
    {
        
        $parts = explode('/', $dir);
        $file = array_pop($parts);
        $dir = '';
        foreach ($parts as $part)
            if (!is_dir($dir .= "/$part")) mkdir($dir);
        file_put_contents("$dir/$file", $contents);
    }*/
 
    private function imageToBase64($path) {
        $path = '../public/dossierCarte/cover653a7db4be8ef.png';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    } 


    /* #[Route('/pdf/generator', name: 'app_pdf_generator')]
    public function index(): Response
    {
        // return $this->render('pdf_generator/index.html.twig', [
        //     'controller_name' => 'PdfGeneratorController',
        // ]);
        $data = [
            'imageSrc'  => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/img/profile.png'),
            'name'         => 'John Doe',
            'address'      => 'USA',
            'mobileNumber' => '000000000',
            'email'        => 'john.doe@email.com'
        ];
        $html =  $this->renderView('pdf_generator/index.html.twig', $data);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
         
        return new Response (
            $dompdf->stream('resume', ["Attachment" => false]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/pdf']
        );
    } */
}


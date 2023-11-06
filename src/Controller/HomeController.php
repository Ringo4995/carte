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
        return $this->render('home/index.html.twig', []);
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

   


    #[Route('/pdf/{cardId}', name: 'pdfmaker')]
    public function imagefound(CardRepository $cartesRepository, $cardId): Response
    {

        $cartes = $cartesRepository->find($cardId);
        $path = '../public/dossierCarte/' . $cartes->getImage();
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        $cartes->setImage($base64);
      
        $html =  $this->renderView('card-template.html.twig',  ["cartes" => $cartes]);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response(
            $dompdf->stream('resume', ["Attachment" => false]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/pdf']
        );
    }

   
}

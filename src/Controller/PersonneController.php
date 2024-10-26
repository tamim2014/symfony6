<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Personne;
use App\Form\PersonneType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/personne')]
class PersonneController extends AbstractController
{
   // 1) Affiche toutes les personnes: findAll()
   #[Route('/', name: 'personne.list')]
   public function index(ManagerRegistry $doctrine):response {
      $repository = $doctrine->getRepository(Personne::class);

      $personnes = $repository->findAll();

      return $this->render('personne/index.html.twig', [
       'personnes' => $personnes,
       'isPaginated' => false
      ]);
   }



       // 4) Ajouter une personne: persist($personne)
   #[Route('/edit/{id?0}', name:'personne.edit')]
   public function addPersonne(Personne $personne=null, ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
   { 
      $new = false;
      if(!$personne){
         $new = true;
         $personne = new Personne();
      }
      $entityManager = $doctrine->getManager();
      $form = $this->createForm(PersonneType::class, $personne );
      // Traitement
      $form->handleRequest($request);
      if($form->isSubmitted()){
         /** @var UploadedFile $brochureFile */
         $brochureFile = $form->get('photo')->getData();
         if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
                // Move the file to the directory where brochures are stored
                try {
                $brochureFile->move($originalFilename, $newFilename);
                } catch (FileException $e) {
                // ... handle exception if something happens during file upload
                }
                $personne->setImage($newFilename);


                $manager = $doctrine->getManager();
                $manager->persist($personne);
                $manager->flush();
            
                if($new){
                    $message = " est ajouté avec succès";
                }else{
                    $message = " est mise à jour avec succès";
                }
                $this->addFlash('success', $personne->getNom().$message);  
            }

    }
    return $this->render('personne/add-personne.html.twig', [
        'personne' => $personne,  
        'form' => $form->createView()  
    ]);
}



     // 3) Détail(Affiche une seule personne): find($id)
     #[Route('/{id}', name:'personne.detail')]
     public function detail(ManagerRegistry $doctrine, $id):response {
        $repository = $doctrine->getRepository(Personne::class); 
        $personne = $repository->find($id);
        if(!$personne){
           $this->addFlash('error', 'La personne d id $id n existe pas');
           return $this->redirectToRoute('personne.list');
        }
        return $this->render('personne/detail.html.twig', [
         'personne' => $personne
        ]);
     }



   // 5) Supprimer une personne( usage du param converter): remove($personne)
   #[Route('/delete/{id}', name: 'personne.delete')]
   public function deletePersonne(Personne $personne=null,  ManagerRegistry $doctrine): Response
   {
      // Recuperer la personne
         // si elle existe: la supprimer
         // si elle n'existe pas: message d'erreur
      if($personne){
         $manager = $doctrine->getManager();
         //SUPPRESSION Objet
         $manager->remove($personne);
         // SUPPRESSION Sql(migration objet vers sql)
         $manager->flush();
         $this->addFlash('success', 'La personne est bien supprimée');
      }else{
         $this->addFlash('error', 'Personne inexistante');
      }
      return $this->redirectToRoute('personne.list');
   }
}

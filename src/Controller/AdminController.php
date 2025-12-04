<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_index')]
    public function index(UserRepository $repo, Request $req): Response
    {
        // sorting stuff
        $sort = $req->query->get('sort', 'lastLoginTime');
        $dir = $req->query->get('direction', 'DESC');

        // check if sort is allowed
        $ok_fields = ['name', 'email', 'status', 'lastLoginTime', 'registrationTime'];
        if (!in_array($sort, $ok_fields)) {
            $sort = 'lastLoginTime';
        }
        if (!in_array(strtoupper($dir), ['ASC', 'DESC'])) {
            $dir = 'DESC';
        }

        $list = $repo->findBy([], [$sort => $dir]);

        return $this->render('admin/index.html.twig', [
            'users' => $list,
            'current_sort' => $sort,
            'current_direction' => $dir,
        ]);
    }

    #[Route('/bulk-action', name: 'app_admin_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $req, UserRepository $repo, EntityManagerInterface $em): Response
    {
        $act = $req->request->get('action');
        $ids = $req->request->all('users'); 
        
        if (empty($ids)) {
            $this->addFlash('warning', 'No users selected.');
            return $this->redirectToRoute('app_admin_index');
        }

        $targets = $repo->findBy(['id' => $ids]);
        $me = $this->getUser();
        $kick_me = false;

        foreach ($targets as $u) {
            switch ($act) {
                case 'block':
                    $u->setStatus(User::STATUS_BLOCKED);
                    if ($u === $me) {
                        $kick_me = true;
                    }
                    break;
                case 'unblock':
                    $u->setStatus(User::STATUS_ACTIVE);
                    break;
                case 'delete':
                    $em->remove($u);
                    if ($u === $me) {
                        $kick_me = true;
                    }
                    break;
            }
        }

        $em->flush();

        if ($kick_me) {
            // kill session if i blocked myself
            $this->container->get('security.token_storage')->setToken(null);
            $req->getSession()->invalidate();
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', sprintf('Action "%s" applied to %d users.', $act, count($targets)));

        return $this->redirectToRoute('app_admin_index');
    }
}

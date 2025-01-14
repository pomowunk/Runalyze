<?php

namespace App\Controller\Settings;

use App\Entity\Account;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Form\Settings\TagType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/settings/tags")
 * @Security("is_granted('ROLE_USER')")
 */
class TagsController extends AbstractController
{
    protected TagRepository $tagRepository;

    public function __construct(TagRepository $tagRepository) {
        $this->tagRepository = $tagRepository;
    }

    /**
     * @Route("", name="settings-tags")
     */
    public function overviewAction(Account $account): Response
    {
        return $this->render('settings/tag/overview.html.twig', [
            'tags' => $this->tagRepository->findAllFor($account),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="settings-tags-edit")
     * @ParamConverter("tag", class="App\Entity\Tag")
     */
    public function tagEditAction(Request $request, Tag $tag, Account $account): Response
    {
        if ($tag->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TagType::class, $tag,[
            'action' => $this->generateUrl('settings-tags-edit', ['id' => $tag->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tagRepository->save($tag);

            return $this->redirectToRoute('settings-tags');
        }

        return $this->render('settings/tag/form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/delete", name="settings-tags-delete")
     * @ParamConverter("tag", class="App\Entity\Tag")
     */
    public function tagDeleteAction(
        Request $request,
        Tag $tag,
        Account $account,
        TranslatorInterface $translator,
    ): Response
    {
        if (!$this->isCsrfTokenValid('deleteTag', $request->get('t'))) {
            $this->addFlash('notice', $translator->trans('Invalid token.'));

            return $this->redirect($this->generateUrl('settings-tags'));
        }

        if ($tag->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->tagRepository->remove($tag);

        $this->addFlash('notice', $translator->trans('Tag has been deleted.'));

        return $this->redirect($this->generateUrl('settings-tags'));
    }
}

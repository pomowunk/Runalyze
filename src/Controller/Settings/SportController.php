<?php

namespace App\Controller\Settings;

use App\Entity\Account;
use App\Entity\Sport;
use App\Entity\Type;
use App\Repository\SportRepository;
use App\Repository\TrainingRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportType;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Profile\View\DataBrowserRowProfile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/settings/sport")
 * @Security("is_granted('ROLE_USER')")
 */
class SportController extends AbstractController
{
    protected AutomaticReloadFlagSetter $automaticReloadFlagSetter;
    protected SportRepository $sportRepository;
    protected TrainingRepository $trainingRepository;
    protected TypeRepository $typeRepository;

    public function __construct(
        AutomaticReloadFlagSetter $automaticReloadFlagSetter,
        SportRepository $sportRepository,
        TrainingRepository $trainingRepository,
        TypeRepository $typeRepository)
    {
        $this->automaticReloadFlagSetter = $automaticReloadFlagSetter;
        $this->sportRepository = $sportRepository;
        $this->trainingRepository = $trainingRepository;
        $this->typeRepository = $typeRepository;
    }

    /**
     * @Route("", name="settings-sports")
     */
    public function overviewAction(Account $account): Response
    {
        return $this->render('settings/sport/overview.html.twig', [
            'sports' => $this->sportRepository->findAllFor($account),
            'hasTrainings' => array_flip($this->trainingRepository->getSportsWithTraining($account)),
            'freeInternalTypes' => $this->sportRepository->getFreeInternalTypes($account),
            'calendarView' => new DataBrowserRowProfile()
        ]);
    }

    /**
     * @Route("/{sportid}/type/add", name="sport-type-add", requirements={"sportid" = "\d+"})
     */
    public function typeAddAction(Request $request, $sportid, Account $account, EntityManagerInterface $em): Response
    {
        $type = new Type();
        $type->setAccount($account);
        $form = $this->createForm(SportTypeType::class, $type ,[
            'action' => $this->generateUrl('sport-type-add', ['sportid' => $sportid])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type->setSport($em->getReference(Sport::class, $sportid));
            $this->typeRepository->save($type);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
            return $this->redirectToRoute('sport-edit', ['id' => $sportid]);
        }

        return $this->render('settings/sport/form-type.html.twig', [
            'form' => $form->createView(),
            'sport_id' => $sportid
        ]);
    }

    /**
     * @Route("/type/{id}/edit", name="sport-type-edit", requirements={"id" = "\d+"})
     * @ParamConverter("type", class="App\Entity\Type")
     */
    public function typeEditAction(Request $request, Type $type, Account $account): Response
    {
        if ($type->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(SportTypeType::class, $type ,[
            'action' => $this->generateUrl('sport-type-edit', ['id' => $type->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->typeRepository->save($type);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

            return $this->redirectToRoute('sport-type-edit', ['id' => $type->getId()]);
        }
        return $this->render('settings/sport/form-type.html.twig', [
            'form' => $form->createView(),
            'sport_id' => $type->getSport()->getId()
        ]);
    }

    /**
     * @Route("/type/{id}/delete", name="sport-type-delete", requirements={"id" = "\d+"})
     * @ParamConverter("type", class="App\Entity\Type")
     */
    public function deleteSportTypeAction(
        Request $request,
        Type $type,
        Account $account,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
    ): Response
    {
        if (!$this->isCsrfTokenValid('deleteSportType', $request->get('t'))) {
            $this->addFlash('error', $translator->trans('Invalid token.'));

            return $this->redirect($this->generateUrl('settings-sports'));
        }

        if ($type->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        if ($type->getTrainings()->count() == NULL) {
            $em->remove($type);
            $em->flush();
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
            $this->addFlash('success', $translator->trans('The object has been deleted.'));
        } else {
            $this->addFlash('error', $translator->trans('Object cannot be deleted.').' '.$translator->trans('You have activities associated with this type.'));
        }
        return $this->redirect($this->generateUrl('settings-sports'));
    }

    /**
     * @Route("/add/{internalType}", name="sport-add", requirements={"internalType" = "\d+"})
     * @Route("/add/custom", name="sport-add-custom")
     */
    public function sportAddAction(Request $request, Account $account, $internalType = null): Response
    {
        $sport = new Sport();
        $sport->setAccount($account);

        if (null !== $internalType && $this->sportRepository->isInternalTypeFree($internalType, $account)) {
            $sport->setDataFrom(SportProfile::get($internalType));
            $this->sportRepository->save($sport);

            return $this->redirectToRoute('sport-edit', [
                'id' => $sport->getId()
            ]);
        }

        $form = $this->createForm(SportType::class, $sport,[
            'action' => $this->generateUrl('sport-add')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->sportRepository->save($sport);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

            return $this->redirectToRoute('sport-edit', ['id' => $sport->getId()]);
        }

        return $this->render('settings/sport/form-sport.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="sport-edit", requirements={"id" = "\d+"})
     * @ParamConverter("sport", class="App\Entity\Sport")
     */
    public function sportEditAction(Request $request, Sport $sport, Account $account): Response
    {
        if ($sport->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(SportType::class, $sport,[
            'action' => $this->generateUrl('sport-edit', ['id' => $sport->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->sportRepository->save($sport);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);

            return $this->redirectToRoute('sport-edit', ['id' => $sport->getId()]);
        }
        return $this->render('settings/sport/form-sport.html.twig', [
            'form' => $form->createView(),
            'types' => $this->typeRepository->findAllFor($account, $sport),
            'calendarView' => new DataBrowserRowProfile(),
            'hasTrainings' => array_flip($this->trainingRepository->getTypesWithTraining($account)),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="sport-delete", requirements={"id" = "\d+"})
     * @ParamConverter("sport", class="App\Entity\Sport")
     */
    public function sportDeleteAction(
        Request $request,
        Sport $sport,
        Account $account,
        TranslatorInterface $translator,
    ): Response
    {
        if (!$this->isCsrfTokenValid('deleteSport', $request->get('t'))) {
            $this->addFlash('error', $translator->trans('Invalid token.'));

            return $this->redirect($this->generateUrl('sport-edit', ['id' => $sport->getId()]));
        }

        if ($sport->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        if (0 == $sport->getTrainings()->count()) {
            $this->sportRepository->remove($sport);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_DATA_BROWSER);
            $this->addFlash('success', $translator->trans('The object has been deleted.'));
        } else {
            $this->addFlash('error', $translator->trans('Object cannot be deleted.').' '.$translator->trans('You have activities associated with this type.'));
        }

        return $this->redirect($this->generateUrl('settings-sports'));
    }
}

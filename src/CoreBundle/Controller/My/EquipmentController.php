<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Form;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/my/equipment")
 * @Security("is_granted('ROLE_USER')")
 */
class EquipmentController extends AbstractController
{
    /** @var AutomaticReloadFlagSetter */
    protected $automaticReloadFlagSetter;

    /** @var EquipmentRepository */
    protected $equipmentRepository;

    /** @var EquipmentTypeRepository */
    protected $equipmentTypeRepository;

    public function __construct(
        AutomaticReloadFlagSetter $automaticReloadFlagSetter,
        EquipmentRepository $equipmentRepository,
        EquipmentTypeRepository $equipmentTypeRepository)
    {
        $this->automaticReloadFlagSetter = $automaticReloadFlagSetter;
        $this->equipmentRepository = $equipmentRepository;
        $this->equipmentTypeRepository = $equipmentTypeRepository;
    }

    /**
     * @Route("/category/{typeid}/table", name="equipment-category-table", requirements={"typeid" = "\d+"})
     */
    public function categoryTableAction($typeid, Account $account, ConfigurationManager $configurationManager)
    {
        $equipmentType = $this->equipmentTypeRepository->findOneBy(['id' => $typeid, 'account' => $account->getId()]);
        $equipmentStatistics = $this->equipmentRepository->getStatisticsForType($typeid, $account);

        if (null === $equipmentType) {
            return $this->overviewAction($account);
        }

        $unitSystem = $configurationManager->getList()->getUnitSystem();

        if (1 == $equipmentType->getSport()->count()) {
            $unitSystem->setPaceUnitFromSport($equipmentType->getSport()->first());
        }

        return $this->render('my/equipment/category/table.html.twig', [
            'unitSystem' => $unitSystem,
            'category' => $equipmentType,
            'statistics' => $equipmentStatistics
        ]);
    }

    /**
     * @Route("/overview", name="equipment-overview")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function overviewAction(Account $account)
    {
        return $this->render('my/equipment/overview.html.twig', [
            'equipmentTypes' => $this->equipmentTypeRepository->findAllFor($account)
        ]);
    }

    /**
     * @Route("/category/add", name="equipment-category-add")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function typeAddAction(Request $request, Account $account)
    {
        $equipmentType = new EquipmentType();
        $equipmentType->setAccount($account);

        $form = $this->createForm(Form\EquipmentCategoryType::class, $equipmentType ,[
            'action' => $this->generateUrl('equipment-category-add')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->equipmentTypeRepository->save($equipmentType);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipmentType->getId()
            ]);
        }

        return $this->render('my/equipment/form-category.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/category/{id}/edit", name="equipment-category-edit")
     * @ParamConverter("equipmentType", class="Runalyze\Bundle\CoreBundle\Entity\EquipmentType")
     *
     * @param Request $request
     * @param EquipmentType $equipmentType
     * @param Account $account
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function typeEditAction(Request $request, EquipmentType $equipmentType, Account $account)
    {
        if ($equipmentType->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(Form\EquipmentCategoryType::class, $equipmentType ,[
            'action' => $this->generateUrl('equipment-category-edit', ['id' => $equipmentType->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->equipmentTypeRepository->save($equipmentType);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipmentType->getId()
            ]);
        }

        return $this->render('my/equipment/form-category.html.twig', [
            'form' => $form->createView(),
            'equipment' => $this->equipmentRepository->findByTypeId($equipmentType->getId(), $account)
        ]);
    }

    /**
     * @Route("/category/{id}/delete", name="equipment-category-delete")
     * @ParamConverter("equipmentType", class="Runalyze\Bundle\CoreBundle\Entity\EquipmentType")
     */
    public function deleteEquipmentTypeAction(
        Request $request,
        EquipmentType $equipmentType,
        Account $account,
        TranslatorInterface $translator)
    {
        if (!$this->isCsrfTokenValid('deleteEquipmentCategory', $request->get('t'))) {
            $this->addFlash('error', $translator->trans('Invalid token.'));

            return $this->redirectToRoute('equipment-overview');
        }

        if ($equipmentType->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->equipmentTypeRepository->remove($equipmentType);

        $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
        $this->addFlash('success', $translator->trans('The category has been deleted.'));

        return $this->redirectToRoute('equipment-overview');
    }

    /**
     * @Route("/add/{id}", name="equipment-add", requirements={"id" = "\d+"})
     * @ParamConverter("equipmentType", class="Runalyze\Bundle\CoreBundle\Entity\EquipmentType")
     */
    public function equipmentAddAction(Request $request, EquipmentType $equipmentType, Account $account)
    {
        $equipment = new Equipment();
        $equipment->setAccount($account);
        $equipment->setType($equipmentType);
        $equipment->setDateStart(new \DateTime("now"));

        $form = $this->createForm(Form\EquipmentType::class, $equipment,[
            'action' => $this->generateUrl('equipment-add', ['id' => $equipmentType->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->equipmentRepository->save($equipment);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipment->getType()->getId()
            ]);
        }

        return $this->render('my/equipment/form-equipment.html.twig', [
            'form' => $form->createView(),
            'category_id' => $equipmentType->getId()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="equipment-edit")
     * @ParamConverter("equipment", class="Runalyze\Bundle\CoreBundle\Entity\Equipment")
     * @param Request $request
     * @param Equipment $equipment
     * @param Account $account
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function equipmentEditAction(Request $request, Equipment $equipment, Account $account)
    {
        if ($equipment->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(Form\EquipmentType::class, $equipment,[
            'action' => $this->generateUrl('equipment-edit', ['id' => $equipment->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->equipmentRepository->save($equipment);
            $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipment->getType()->getId()
            ]);
        }

        return $this->render('my/equipment/form-equipment.html.twig', [
            'form' => $form->createView(),
            'category_id' => $equipment->getType()->getId()
        ]);
    }

    /**
     * @Route("/{id}/delete", name="equipment-delete")
     * @ParamConverter("equipment", class="Runalyze\Bundle\CoreBundle\Entity\Equipment")
     */
    public function deleteEquipmentAction(
        Request $request,
        Equipment $equipment,
        Account $account,
        TranslatorInterface $translator)
    {
        if (!$this->isCsrfTokenValid('deleteEquipment', $request->get('t'))) {
            $this->addFlash('error', $translator->trans('Invalid token.'));

            return $this->redirectToRoute('equipment-category-edit', [
                'id' => $equipment->getType()->getId()
            ]);
        }

        if ($equipment->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->equipmentRepository->remove($equipment);
        $this->automaticReloadFlagSetter->set(AutomaticReloadFlagSetter::FLAG_PLUGINS);
        $this->addFlash('success', $translator->trans('The object has been deleted.'));

        return $this->redirectToRoute('equipment-category-edit', [
            'id' => $equipment->getType()->getId()
        ]);
    }
}

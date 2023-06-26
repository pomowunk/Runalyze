<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use App\Entity\Account;
use App\Entity\Equipment;
use App\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Form\Type\DistanceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EquipmentType extends AbstractType
{
    /** @var EquipmentTypeRepository */
    protected $EquipmentRepository;

    /** @var TokenStorageInterface */
    protected $TokenStorage;

    public function __construct(
        EquipmentTypeRepository $equipmentTypeRepository,
        TokenStorageInterface $tokenStorage)
    {
        $this->EquipmentRepository = $equipmentTypeRepository;
        $this->TokenStorage = $tokenStorage;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorage->getToken() ? $this->TokenStorage->getToken()->getUser() : null;

        if (!($account instanceof Account)) {
            throw new \RuntimeException('Equipment type must have a valid account token.');
        }

        return $account;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Equipment $equipment */
        $equipment = $builder->getData();

        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('additionalKm', DistanceType::class, [
                'label' => 'prev. distance',
                'required' => true,
            ])
            ->add('dateStart', DateType::class, [
                'label' => 'Start of use',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'required' => false,
                'attr' => ['class' => 'pick-a-date small-size', 'placeholder' => 'dd.mm.YYYY']
            ])
            ->add('dateEnd', DateType::class, [
                'label' => 'End of use',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'required' => false,
                'attr' => ['class' => 'pick-a-date small-size', 'placeholder' => 'dd.mm.YYYY']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'empty_data' => '',
                'attr' => ['class' => 'fullwidth']
            ]);

        if (null === $equipment->getId()) {
            $builder
                ->add('type', ChoiceType::class, [
                    'choices' => $this->EquipmentRepository->findAllFor($this->getAccount()),
                    'choice_label' => 'name',
                    'choice_value' => 'getId',
                    'choice_translation_domain' => false,
                    'label' => 'Category',
                    'disabled' => true
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class
        ]);
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use App\Entity\Account;
use App\Entity\Sport;
use App\Entity\EquipmentType;
use App\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Form\Type\DistanceType;
use Runalyze\Bundle\CoreBundle\Form\Type\DurationNullableType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EquipmentCategoryType extends AbstractType
{
    /** @var SportRepository */
    protected $SportRepository;

    /** @var TokenStorageInterface */
    protected $TokenStorage;

    public function __construct(
        SportRepository $SportRepository,
        TokenStorageInterface $tokenStorage)
    {
        $this->SportRepository = $SportRepository;
        $this->TokenStorage = $tokenStorage;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorage->getToken() ? $this->TokenStorage->getToken()->getUser() : null;

        if (!($account instanceof Account)) {
            throw new \RuntimeException('Equipment category must have a valid account token.');
        }

        return $account;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $equipmentType = $event->getData();
                $form = $event->getForm();

                if (!$equipmentType || null === $equipmentType->getId()) {
                    $form->add('input', ChoiceType::class, [
                        'choices' => [
                            'Single choice' => EquipmentType::CHOICE_SINGLE,
                            'Multiple Choice' => EquipmentType::CHOICE_MULTIPLE
                        ],
                        'choice_translation_domain' => false,
                        'label' => 'Mode'
                    ]);
                }
            })
            ->add('maxKm', DistanceType::class, [
                'label' => 'max. Distance',
                'required' => false
            ])
            ->add('maxTime', DurationNullableType::class, [
                'label' => 'max. Time',
                'required' => false,
                'attr' => ['class' => 'medium-size']
            ])
            ->add('sport', EntityType::class, [
                'class' => Sport::class,
                'choices' => $this->SportRepository->findAllFor($this->getAccount()),
                'choice_label' => 'name',
                'label' => 'Assigned sports',
                'attr' => [
                    'class' => 'chosen-select full-size',
                    'data-placeholder' => 'Choose sport(s)'
                ],
                'multiple' => true,
                'required' => true,
                'expanded' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EquipmentType::class
        ]);
    }
}

<?php

namespace Runalyze\Bundle\CoreBundle\Form\Tools;

use App\Entity\Account;
use App\Repository\SportRepository;
use App\Repository\TrainingRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PosterType extends AbstractType
{
    /** @var TrainingRepository */
    protected $TrainingRepository;

    /** @var SportRepository */
    protected $SportRepository;

    /** @var TokenStorageInterface */
    protected $TokenStorage;

    public function __construct(
        SportRepository $sportRepository,
        TrainingRepository $trainingRepository,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->SportRepository = $sportRepository;
        $this->TrainingRepository = $trainingRepository;
        $this->TokenStorage = $tokenStorage;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorage->getToken() ? $this->TokenStorage->getToken()->getUser() : null;

        if (!($account instanceof Account)) {
            throw new \RuntimeException('Poster type must have a valid account token.');
        }

        return $account;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('postertype', ChoiceType::class, [
                'multiple' => true,
                'choices' => [
                    'Circular' => 'circular',
                    'Calendar' => 'calendar',
                    'Grid'     => 'grid',
                    'Heatmap'  => 'heatmap'],
                'attr' => ['class' => 'chosen-select full-size']
            ])
            ->add('year', ChoiceType::class, [
                'choices' => $this->TrainingRepository->getActiveYearsFor($this->getAccount(), null, 2),
                'choice_label' => function($year, $key, $index) {
                    return $year;
                },
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => ['maxlength' => 11]
            ])
            ->add('sport', ChoiceType::class, [
                'choices' => $this->SportRepository->findWithDistancesFor($this->getAccount()),
                'choice_label' => function($sport, $key, $index) {
                    /** @var Sport $sport */
                    return $sport->getName();
                },
                'choice_value' => 'getId',
            ])
            ->add('size', ChoiceType::class, [
                'choices' => [
                    'DIN A4' => 4000,
                    'DIN A3' => 5000,
                    'DIN A2' => 7000,
                    'DIN A1' => 10000,
                    'DIN A0' => 14000
                 ],
            ])
            ->add('backgroundColor', ColorType::class, [
                'data' => '#222222',
                'label' => 'Background'
            ])
            ->add('trackColor', ColorType::class, [
                'data' => '#4DD2FF',
                'label' => 'Activity'
            ])
            ->add('textColor', ColorType::class, [
                'data' => '#FFFFFF',
                'label' => 'Text'
            ])
            ->add('raceColor', ColorType::class, [
                'data' => '#FFFF00',
                'label' => 'Race'
            ])
        ;
    }
}

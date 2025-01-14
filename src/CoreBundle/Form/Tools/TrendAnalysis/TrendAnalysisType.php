<?php

namespace Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis;

use App\Entity\Account;
use App\Repository\SportRepository;
use App\Repository\TypeRepository;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TrendAnalysisType extends AbstractType
{
    /** @var SportRepository */
    protected $SportRepository;

    /** @var TypeRepository */
    protected $TypeRepository;

    /** @var TokenStorageInterface */
    protected $TokenStorage;

    /** @var ConfigurationManager */
    protected $ConfigurationManager;

    public function __construct(
        SportRepository $sportRepository,
        TypeRepository $typeRepository,
        TokenStorageInterface $tokenStorage,
        ConfigurationManager $configurationManager
    )
    {
        $this->SportRepository = $sportRepository;
        $this->TypeRepository = $typeRepository;
        $this->TokenStorage = $tokenStorage;
        $this->ConfigurationManager = $configurationManager;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorage->getToken() ? $this->TokenStorage->getToken()->getUser() : null;

        if (!($account instanceof Account)) {
            throw new \RuntimeException('Trend analysis type must have a valid account token.');
        }

        return $account;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateFrom', DateType::class, [
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => ['class' => 'pick-a-date small-size'],
                'model_timezone' => 'UTC',
                'view_timezone' => 'UTC'
            ])
            ->add('dateTo', DateType::class, [
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'attr' => ['class' => 'pick-a-date small-size'],
                'model_timezone' => 'UTC',
                'view_timezone' => 'UTC'
            ])
            ->add('sport', ChoiceType::class, [
                'multiple' => true,
                'choices' => $this->SportRepository->findAllFor($this->getAccount()),
                'choice_label' => function($sport, $key, $index) {
                    /** @var Sport $sport */
                    return $sport->getName();
                },
                'choice_translation_domain' => false,
                'attr' => [
                    'data-placeholder' => __('Choose sport(s)'),
                    'class' => 'chosen-select full-size'
                ],
                'choice_attr' => function($sport, $key, $index) {
                    /* @var Sport $sport */
                    return ['data-id' => $sport->getId()];
                }
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'choices' => $this->TypeRepository->findAllFor($this->getAccount()),
                'choice_label' => function($type, $key, $index) {
                    /** @var Type $type */
                    return $type->getName();
                },
                'choice_translation_domain' => false,
                'attr' => [
                    'data-placeholder' => __('Choose activity type(s)'),
                    'class' => 'chosen-select full-size'
                ],
                'choice_attr' => function($type, $key, $index) {
                    /* @var Type $type */
                    return ['data-sportid' => $type->getSport()->getId()];
                }
            ])
            ->add('valueToLookAt', ChoiceType::class, [
                'choices' => [
                    'Main values' => [
                        'Pace' => QueryValues::PACE,
                        'Distance' => QueryValues::DISTANCE,
                        'Duration' => QueryValues::DURATION,
                        'Heart rate' => QueryValues::HEART_RATE_AVERAGE,
                        'TRIMP' => QueryValues::TRIMP,
                        'Power' => QueryValues::POWER,
                        'Cadence' => QueryValues::CADENCE,
                        'Effective VO2max' => $this->getQueryValueEnumForVO2max(),
                        'Climb Score' => QueryValues::CLIMB_SCORE,
                        'Percentage hilly' => QueryValues::PERCENTAGE_HILLY,
                        'RPE' => QueryValues::RPE
                    ],
                    'Running dynamics' => [
                        'Ground contact time' => QueryValues::GROUND_CONTACT_TIME,
                        'Ground contact balance' => QueryValues::GROUND_CONTACT_BALANCE,
                        'Vertical oscillation' => QueryValues::VERTICAL_OSCILLATION,
                        'Flight time' => QueryValues::FLIGHT_TIME,
                        'Flight ratio' => QueryValues::FLIGHT_RATIO
                    ],
                    'RunScribe' => [
                        'Impact Gs (left)' => QueryValues::IMPACT_GS_LEFT,
                        'Impact Gs (right)' => QueryValues::IMPACT_GS_RIGHT,
                        'Braking Gs (left)' => QueryValues::BRAKING_GS_LEFT,
                        'Braking Gs (right)' => QueryValues::BRAKING_GS_RIGHT,
                        'Footstrike type (left)' => QueryValues::FOOTSTRIKE_TYPE_LEFT,
                        'Footstrike type (right)' => QueryValues::FOOTSTRIKE_TYPE_RIGHT,
                        'Pronation excursion (left)' => QueryValues::PRONATION_EXCURSION_LEFT,
                        'Pronation excursion (right)' => QueryValues::PRONATION_EXCURSION_RIGHT,
                    ],
                    'FIT details' => [
                        'HRV analysis' => QueryValues::FIT_HRV_ANALYSIS,
                        'Performance condition (start)' => QueryValues::FIT_PERFORMANCE_CONDITION_START,
                        'Performance condition (end)' => QueryValues::FIT_PERFORMANCE_CONDITION_END,
                        'Recovery time' => QueryValues::FIT_RECOVERY_TIME,
                        'Training effect' => QueryValues::FIT_TRAINING_EFFECT,
                        'VO2max estimate' => QueryValues::FIT_VO2MAX_ESTIMATE
                    ],
                    'Weather' => [
                        'Temperature' => QueryValues::WEATHER_TEMPERATURE,
                        'Humidity' => QueryValues::WEATHER_HUMIDITY,
                        'Pressure' => QueryValues::WEATHER_PRESSURE
                    ]
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TrendAnalysisData::class
        ]);
    }

    /**
     * @return string
     */
    protected function getQueryValueEnumForVO2max()
    {
        if ($this->ConfigurationManager->getList()->useVO2maxCorrectionForElevation()) {
            return QueryValues::VO2MAX_WITH_ELEVATION;
        }

        return QueryValues::VO2MAX;
    }
}

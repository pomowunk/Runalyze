<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/glossary")
 */
class GlossaryController extends AbstractController
{
    private function renderCorrectView($view, $request)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->render($view, [
                'urlClass' => 'class="window"'
            ]);
        }

        return $this->render($view, [
            'urlClass' => 'class="external"'
        ]);
    }

    /**
     * @Route("/", name="glossary-index")
     */
    public function indexAction(Request $request)
    {
        return $this->renderCorrectView('glossary/overview.html.twig', $request);
    }

    /**
     * @Route("/atl", name="glossary-atl")
     */
    public function atlAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/atl.html.twig', $request);
    }

    /**
     * @Route("/ctl", name="glossary-ctl")
     */
    public function ctlAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/ctl.html.twig', $request);
    }

    /**
     * @Route("/tsb", name="glossary-tsb")
     */
    public function tsbAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/tsb.html.twig', $request);
    }

    /**
     * @Route("/easy-trimp", name="glossary-easy-trimp")
     */
    public function easyTrimpAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/easy_trimp.html.twig', $request);
    }

    /**
     * @Route("/heat-index", name="glossary-heat-index")
     */
    public function heatIndexAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/heat_index.html.twig', $request);
    }

    /**
     * @Route("/hrv", name="glossary-hrv")
     */
    public function hrvAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/hrv.html.twig', $request);
    }

    /**
     * @Route("/marathon-shape", name="glossary-marathon-shape")
     */
    public function marathonShapeAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/marathon_shape.html.twig', $request);
    }

    /**
     * @Route("/maximum-heart-rate", name="glossary-maximum-heart-rate")
     */
    public function maximumHeartRateAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/maximum_heart_rate.html.twig', $request);
    }

    /**
     * @Route("/monotony", name="glossary-monotony")
     */
    public function monotonyAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/monotony.html.twig', $request);
    }

    /**
     * @Route("/performance-condition", name="glossary-performance-condition")
     */
    public function performanceConditionAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/performance_condition.html.twig', $request);
    }

    /**
     * @Route("/resting-heart-rate", name="glossary-resting-heart-rate")
     */
    public function restingHeartRateAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/resting_heart_rate.html.twig', $request);
    }

    /**
     * @Route("/rpe", name="glossary-rpe")
     */
    public function rpeAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/rpe.html.twig', $request);
    }

    /**
     * @Route("/swolf", name="glossary-swolf")
     */
    public function swolfAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/swolf.html.twig', $request);
    }

    /**
     * @Route("/training-effect", name="glossary-training-effect")
     */
    public function trainingEffectAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/training_effect.html.twig', $request);
    }

    /**
     * @Route("/training-strain", name="glossary-training-strain")
     */
    public function trainingStrainAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/training_strain.html.twig', $request);
    }

    /**
     * @Route("/trimp", name="glossary-trimp")
     */
    public function trimpAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/trimp.html.twig', $request);
    }

    /**
     * @Route("/vo2max", name="glossary-vo2max")
     */
    public function vo2maxAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/vo2max.html.twig', $request);
    }

    /**
     * @Route("/windchill", name="glossary-windchill")
     */
    public function windchillAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/windchill.html.twig', $request);
    }

    /**
     * @Route("/groundcontact", name="glossary-groundcontact")
     */
    public function groundcontactAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/groundcontact.html.twig', $request);
    }

    /**
     * @Route("/groundcontact-time-balance", name="glossary-groundcontact-time-balance")
     */
    public function groundcontactTimeBalanceAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/groundcontact_balance.html.twig', $request);
    }

    /**
     * @Route("/vertical-ratio", name="glossary-vertical-ratio")
     */
    public function verticalRatioAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/vertical_ratio.html.twig', $request);
    }

    /**
     * @Route("/vertical-oscillation", name="glossary-vertical-oscillation")
     */
    public function verticalOscillationAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/vertical_oscillation.html.twig', $request);
    }

    /**
     * @Route("/stride-length", name="glossary-stride-length")
     */
    public function strideLengthAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/stride_length.html.twig', $request);
    }

    /**
     * @Route("/cadence", name="glossary-cadence")
     */
    public function cadenceAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/cadence.html.twig', $request);
    }

    /**
     * @Route("/running-effectiveness", name="glossary-running-effectiveness")
     */
    public function runningEffectivenessAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/running_effectiveness.html.twig', $request);
    }

    /**
     * @Route("/efficiency-index", name="glossary-efficiency-index")
     */
    public function efficiencyIndexAction(Request $request)
    {
        return $this->renderCorrectView('glossary/topics/efficiency_index.html.twig', $request);
    }
}

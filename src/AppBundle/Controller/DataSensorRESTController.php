<?php

namespace AppBundle\Controller;

use AppBundle\Entity\DataSensor;
use AppBundle\Form\DataSensorType;
use InfluxDB\Point;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Voryx\RESTGeneratorBundle\Controller\VoryxController;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTime;

/**
 * DataSensor controller.
 * @RouteResource("DataSensor")
 */
class DataSensorRESTController extends VoryxController
{
    /**
     * Get a DataSensor entity
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param $idSensor
     *
     * @return Response
     *
     */
    public function getAction($idSensor)
    {
        return $this->get("influxdb_database")->getQueryBuilder()
        ->select('*')
        ->from('test_metric')
        ->where(["cpucount = '".$idSensor."'"])
        ->getResultSet()
        ->getPoints();;
    }
    /**
     * Get all DataSensor entities.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return Response
     */
    public function cgetAction()
    {
        return $this->get("influxdb_database")->query('select * from test_metric LIMIT 5')->getPoints();;
    }
    /**
     * Create a DataSensor entity.
     *
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function postAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
	$sensor = $em->getRepository('AppBundle:Sensor')->findOneBy(array("displayname" => $request->request->get('sensor', '')));
	$time = new DateTime();
dump($request->request->get('receivedAt'));
        $points = $this->get("influxdb_database")->writePoints([new Point(
                'temperature', // name of the measurement
                $request->request->get('value', 0),// the measurement value
                ['sensor' => $sensor->getId()], // optional additional fields
		[],
		$request->request->get('receivedAt', exec('date +%s%N'))
        )]);

        return new JsonResponse($sensor);
    }
    /**
     * Update a DataSensor entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, DataSensor $entity)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new DataSensorType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->flush();

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Partial Update to a DataSensor entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, DataSensor $entity)
    {
        return $this->putAction($request, $entity);
    }
    /**
     * Delete a DataSensor entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, DataSensor $entity)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($entity);
            $em->flush();

            return null;
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
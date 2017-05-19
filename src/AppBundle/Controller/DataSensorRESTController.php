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
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
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
        ->from('temperature')
        ->where(["sensor = '".$idSensor."'"])
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
     * REST action which returns type by id.
     * Method: GET, url: /api/datasensor/{request}.{_format}
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Push a sensor data",
     *   output = "AppBundle\Entity\DataSensorType",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
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
        $sensor = $em->getRepository('AppBundle:Sensor')->findOneBy(array("uuid" => $request->request->get('sensor', '')));

        //TODO : check if the uuid received is ok

        if ($sensor) {
            $this->container->get('app.influx_service')->persist(
                $request->request->get('measurement', ""),
                $request->request->get('value', 0),
                $sensor,
                $request->request->get('receivedAt', exec('date +%s%N'))
            );

            return new JsonResponse("ok");
        } else {
            return new JsonResponse("nok");
        }
    }    
}

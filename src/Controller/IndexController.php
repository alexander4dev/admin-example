<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AbstractWorkingPlace;
use App\Entity\AbstractSupplier;
use App\Entity\Branch;
use App\Entity\Sector;
use App\Entity\Supplier;
use App\Entity\SupplierDeliveryExtra;
use App\Entity\SupplierDeliverySchedule;
use App\Entity\DeliverySectorRouteSheet;
use App\Entity\DeliveryRouteSheet;
use App\Entity\Warehouse;
use DateInterval;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(Request $request): Response
    {
        $originId = $request->get('originId');
        $destinationId = $request->get('destinationId');
        $orderDate = $request->get('orderDate', '');
        $deliveryDate = $request->get('deliveryDate', '');
        $orderDateTime = DateTime::createFromFormat('d.m.Y H:i', $orderDate) ?: new DateTime();
        $deliveryDateTime = DateTime::createFromFormat('d.m.Y', $deliveryDate) ?: new DateTime();
        $orderCreatingMinutes = (int)$request->get('orderCreatingMinutes', 30);
        $deliveryAcceptingMinutes = (int)$request->get('deliveryAcceptingMinutes', 30);
        $receiveMethod = $request->get('receiveMethod', 'pickup');
        $sectorId = $request->get('sectorId');
        $origins = $this->getOrigins();
        $destinations = $this->getDestinations($originId);
        $em = $this->getDoctrine()->getManager();
        $sectors = [];
        $sectorArrivals = [];

        if (null === $originId) {
            $originId = current(array_keys($origins));
        }

        if (null === $destinationId || $originId === $destinationId) {
            $destinationId = current(array_keys($destinations));
        }

        if ('delivery' === $receiveMethod) {
            $sectorsBranchId = (int)ltrim($destinationId, 'b');
            /* @var $sectorsBranch Branch */
            $sectorsBranch = $em->find(Branch::class, $sectorsBranchId);

            foreach ($sectorsBranch->getSectors() as $sector) {
                /* @var $sector Sector */
                $sectors[$sector->getId()] = sprintf('%s (%s)', $sector->getName(), $sector->getBranch()->getName());
            }

            if (null === $sectorId) {
                $sectorId = current(array_keys($sectors));
            }
        }

        $deliveryInfo = $this->getDeliveryInfoUsingCalendar($originId, $destinationId, $orderDateTime, $orderCreatingMinutes, $deliveryAcceptingMinutes);
        $deliveryInfoClosest = $this->getDeliveryInfoUsingCalendar($originId, $destinationId, new DateTime(), $orderCreatingMinutes, $deliveryAcceptingMinutes);
        $deliveryInfo['closestOrderDeadline'] = $deliveryInfoClosest['orderDeadline'];

        foreach ($deliveryInfo['route'] as $route) {
            if ($originId === $route['departurePointId']) {
                $deliveryInfo['info'][$route['departurePointId']]['orderDate'] = $deliveryInfo['orderDate'];
            }

            $deliveryInfo['info'][$route['departurePointId']]['departurePointName'] = $route['departurePointName'];
            $deliveryInfo['info'][$route['departurePointId']]['departureDate'] = $route['departureDate'];
            $deliveryInfo['info'][$route['arrivalPointId']]['departurePointName'] = $route['arrivalPointName'];
            $deliveryInfo['info'][$route['arrivalPointId']]['arrivalDate'] = $route['arrivalDate'];
            $deliveryInfo['info'][$route['arrivalPointId']]['acceptedDate'] = $route['acceptedDate'];
        }

        $closestDeadline = DateTime::createFromFormat('d.m.Y H:i', $deliveryInfo['orderDeadline'])->sub(new DateInterval('PT1S'));
        $cycleCount = 0;

        do {
            $deliveryInfoClosestTest = $this->getDeliveryInfoUsingCalendar($originId, $destinationId, $closestDeadline, $orderCreatingMinutes, $deliveryAcceptingMinutes);
            $closestDeadline = DateTime::createFromFormat('d.m.Y H:i', $deliveryInfoClosestTest['orderDeadline'])->add(new DateInterval(sprintf('PT%dM', $orderCreatingMinutes)));

            if ($deliveryInfoClosest['deliveryDate'] == $deliveryInfoClosestTest['deliveryDate']) {
                $deliveryInfo['closestOrderDeadline'] = $deliveryInfoClosestTest['orderDeadline'];
            }
        } while ($deliveryInfoClosest['deliveryDate'] == $deliveryInfoClosestTest['deliveryDate']);

        $deliveryInfo['closestDeliveryDate'] = $deliveryInfoClosest['deliveryDate'];

        $actualDeliveryDateTime = DateTime::createFromFormat('d.m.Y H:i', $deliveryInfo['deliveryDate']);

        if ($deliveryDateTime < $actualDeliveryDateTime) {
            $deliveryDateTime = $actualDeliveryDateTime;
            $deliveryDate = $deliveryDateTime->format('d.m.Y');
        }

        if ('delivery' === $receiveMethod) {
            foreach ($sectorsBranch->getWarehouse()->getDeliverySectorDepartures() as $sectorDeparture) {
                /* @var $sectorDeparture DeliverySectorRouteSheet */
                if ((int)$sectorId === $sectorDeparture->getSector()->getId()) {
                    $sectorDepartureDate = $sectorDeparture->getTimeDeparture();
                    $sectorDepartureDate->setDate((int)$deliveryDateTime->format('Y'), (int)$deliveryDateTime->format('n'), (int)$deliveryDateTime->format('j'));

                    if ($sectorDepartureDate > $actualDeliveryDateTime) {
                        $sectorArrivals[$sectorDeparture->getTimeArrival()->getTimestamp()] = $sectorDeparture->getTimeArrival()->format('H:i');
                    }
                }
            }

            ksort($sectorArrivals);
        }

        return $this->render('index.html.twig', compact(
            'orderCreatingMinutes',
            'deliveryAcceptingMinutes',
            'originId',
            'destinationId',
            'orderDate',
            'origins',
            'destinations',
            'deliveryInfo',
            'receiveMethod',
            'sectors',
            'sectorId',
            'deliveryDate',
            'sectorArrivals'
        ));
    }

    /**
     * @Route("/calendar")
     */
    public function calendar(Request $request): Response
    {
        $entityId = $request->get('entityId');
        $entityType = $request->get('entity');
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        $em = $this->getDoctrine()->getManager();

        if ('Supplier' === $entityType) {
            /* @var $supplier Supplier */
            $supplier = $em->find(Supplier::class, $entityId);
        } else {
            /* @var $supplier Warehouse */
            $supplier = $em->find(Warehouse::class, $entityId);
        }

        $dateTimeFrom = DateTime::createFromFormat('Y-m-d', $dateFrom);
        $dateTimeTo = DateTime::createFromFormat('Y-m-d', $dateTo);
        $deliveries = $this->getCalendarDeliveries($supplier, $dateTimeFrom, $dateTimeTo);

        return $this->render('admin/supply_calendar.html.twig', compact(
            'deliveries'
        ));
    }

    /**
     * @Route("/cancelDelivery")
     */
    public function cancelDelivery(Request $request): Response
    {
        $entityId = $request->get('entityId');
        $entityType = $request->get('entity');
        $date = DateTime::createFromFormat('d.m.Y H:i', $request->get('date'));
        $destinationId = (int)$request->get('destinationId');
        $em = $this->getDoctrine()->getManager();

        if ('Supplier' === $entityType) {
            /* @var $supplier Supplier */
            $supplier = $em->find(Supplier::class, $entityId);
        } else {
            /* @var $supplier Warehouse */
            $supplier = $em->find(Warehouse::class, $entityId);
        }

        /* @var $supplierTo Warehouse */
        $supplierTo = $em->find(Warehouse::class, $destinationId);

        $deliveryExtra = new SupplierDeliveryExtra();
        $deliveryExtra->setSupplierFrom($supplier);
        $deliveryExtra->setSupplierTo($supplierTo);
        $deliveryExtra->setOrderDate($date);
        $deliveryExtra->setOrderTime($date);
        $deliveryExtra->setIsSupply(false);

        $em->persist($deliveryExtra);
        $em->flush();

        return $this->json([]);
    }

    /**
     * @Route("/cancelDeliveryExtra")
     */
    public function cancelDeliveryExtra(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $deliveryExtra SupplierDeliveryExtra */
        $deliveryExtra = $em->find(SupplierDeliveryExtra::class, $request->get('entityId'));
        $em->remove($deliveryExtra);
        $em->flush();

        return $this->json([]);
    }

    /**
     * @param string $originId
     * @param string $destinationId
     * @param string $orderDate
     * @param int $orderCreatingMinutes
     * @param int $deliveryAcceptingMinutes
     * @return array
     */
    private function getDeliveryInfoUsingCalendar(
        string $originId,
        string $destinationId,
        DateTime $orderDate,
        int $orderCreatingMinutes,
        int $deliveryAcceptingMinutes
    ): array {
        $result = [];
        $em = $this->getDoctrine()->getManager();
        $deliveryRoute = $this->getDeliveryRoute($originId, $destinationId);
        $orderCreatingInterval = new DateInterval(sprintf('PT%dM', $orderCreatingMinutes));
        $deliveryAcceptingInterval = new DateInterval(sprintf('PT%dM', $deliveryAcceptingMinutes));
        $orderDateCurrent = clone $orderDate;
        $orderDateCurrent->add($orderCreatingInterval);
        $result['orderDate'] = $orderDateCurrent->format('d.m.Y H:i');

        $departureId = array_shift($deliveryRoute);
        $destinationId = (int)ltrim($destinationId, 'b');
        $oneDayInterval = new DateInterval('P1D');

        while ($intermediateArrivalBranchId = array_shift($deliveryRoute)) {
            $resultRoute = [];
            $intermediateArrivalBranchId = (int)ltrim($intermediateArrivalBranchId, 'b');
            /* @var $arrivalBranch Branch */
            $arrivalBranch = $em->find(Branch::class, $intermediateArrivalBranchId);
            $departurePointIsSupplier = 's' === $departureId[0];

            if ($departurePointIsSupplier) {
                $supplierId = (int)ltrim($departureId, 's');
                $em = $this->getDoctrine()->getManager();
                /* @var $supplier Supplier */
                $supplier = $em->find(Supplier::class, $supplierId);

                $resultRoute['departurePointName'] = $supplier->getName();
                $resultRoute['departurePointId'] = 's' . $supplierId;
            } else {
                $departureBranchId = (int)ltrim($departureId, 'b');
                /* @var $branch Branch */
                $branch = $em->find(Branch::class, $departureBranchId);
                $supplier = $branch->getWarehouse();

                $resultRoute['departurePointName'] = $supplier->getName();
                $resultRoute['departurePointId'] = 'b' . $departureBranchId;
            }

            $cycleCount = 0;
            $closestDelivery = false;

            while (!$closestDelivery) {
                $closestWorkingDay = $supplier->getClosestWorkingDay($orderDateCurrent)['date'];
                $calendarDeliveries = $this->getCalendarDeliveries($supplier, $closestWorkingDay, $closestWorkingDay, $arrivalBranch->getWarehouse()->getId());

                foreach ($calendarDeliveries as $calendarDelivery) {
                    $departureDate = DateTime::createFromFormat('d.m.Y H:i', $calendarDelivery['departure_date'] . ' ' . $calendarDelivery['departure_time']);

                    if ($calendarDelivery['is_success'] && $orderDateCurrent < $departureDate) {
                        $closestDelivery = $calendarDelivery;
                        break 2;
                    }
                }

                $orderDateCurrent->add($oneDayInterval)->setTime(0, 0);
            }

            $resultRoute['departureDate'] = $closestDelivery['departure_date'] . ' ' . $closestDelivery['departure_time'];
            $resultRoute['arrivalPointName'] = $arrivalBranch->getWarehouse()->getName();
            $resultRoute['arrivalPointId'] = 'b' . $arrivalBranch->getId();
            $resultRoute['arrivalDate'] = $closestDelivery['arrival_date'];

            $intermediateDeliveryDate = DateTime::createFromFormat('d.m.Y H:i', $closestDelivery['arrival_date']);
            $intermediateDeliveryDate->add($deliveryAcceptingInterval);
            $resultRoute['acceptedDate'] = $intermediateDeliveryDate->format('d.m.Y H:i');
            $result['route'][] = $resultRoute;

            if ($intermediateArrivalBranchId === $destinationId) {
                $result['deliveryDate'] = $this->getClosestArrivalDate($arrivalBranch, $intermediateDeliveryDate)->format('d.m.Y H:i');
                $result['deliveryPointName'] = $arrivalBranch->getName();
                $result['orderDeadline'] = DateTime::createFromFormat('d.m.Y H:i', $result['route'][0]['departureDate'])->sub($orderCreatingInterval)->format('d.m.Y H:i');
            } else {
                $departureId = 'b' . $arrivalBranch->getId();
                $orderDateCurrent = $intermediateDeliveryDate;
            }
        }

        return $result;
    }

    /**
     * @param AbstractSupplier $supplier
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param int $destinationId
     * @return array
     */
    private function getCalendarDeliveries(AbstractSupplier $supplier, DateTime $dateFrom, DateTime $dateTo, int $destinationId = null): array
    {
        $deliveries = [];
        $deliveryInfo = [];
        $calendarDate = clone $dateFrom;
        $calendarDate->setTime(0, 0);

        while ($calendarDate <= $dateTo) {
            $closestWorkingDay = $supplier->getClosestWorkingDay($calendarDate);
            $isWorkingDay = $closestWorkingDay['date'] == $calendarDate;
            $dateWeekDayNumber = (int)$calendarDate->format('N');

            if ($supplier instanceof Supplier) {
                foreach ($supplier->getDeliverySchedule() as $delivery) {
                    /* @var $delivery SupplierDeliverySchedule */
                    if ($destinationId && $destinationId !== $delivery->getWarehouse()->getId()) {
                        continue;
                    }

                    if ($dateWeekDayNumber !== $delivery->getDayNumber()) {
                        continue;
                    }

                    if (!array_key_exists($delivery->getWarehouse()->getId(), $deliveryInfo)) {
                        $deliveryInfo[$delivery->getWarehouse()->getId()]['departure_name'] = $delivery->getWarehouse()->getName();
                        $deliveryInfo[$delivery->getWarehouse()->getId()]['departure_id'] = $delivery->getWarehouse()->getId();
                        $deliveryInfo[$delivery->getWarehouse()->getId()]['extra'] = [];
                    }

                    $orderDate = clone $delivery->getOrderTime();
                    $orderDate->setDate((int)$calendarDate->format('Y'), (int)$calendarDate->format('n'), (int)$calendarDate->format('j'));
                    $deliveryDateExpected = clone $orderDate;
                    $deliveryDateExpected->add($delivery->getDeliveryTimeAmount());

                    $deliveryInfoSchedule = [
                        'order_date' => $orderDate,
                        'delivery_date_expected' => $deliveryDateExpected,
                        'delivery_date_actual' => $this->getClosestArrivalDate($delivery->getWarehouse(), $deliveryDateExpected),
                        'is_working' => $isWorkingDay,
                        'is_extra' => false,
                    ];

                    $deliveryInfo[$delivery->getWarehouse()->getId()]['schedule'][] = $deliveryInfoSchedule;
                }
            } else {
                foreach ($supplier->getDeliveryDepartures() as $delivery) {
                    /* @var $delivery DeliveryRouteSheet */
                    if ($destinationId && $destinationId !== $delivery->getWarehouseTo()->getId()) {
                        continue;
                    }

                    if (!array_key_exists($delivery->getWarehouseTo()->getId(), $deliveryInfo)) {
                        $deliveryInfo[$delivery->getWarehouseTo()->getId()]['departure_name'] = $delivery->getWarehouseTo()->getName();
                        $deliveryInfo[$delivery->getWarehouseTo()->getId()]['departure_id'] = $delivery->getWarehouseTo()->getId();
                        $deliveryInfo[$delivery->getWarehouseTo()->getId()]['extra'] = [];
                    }

                    $orderDate = clone $delivery->getTimeDeparture();
                    $orderDate->setDate((int)$calendarDate->format('Y'), (int)$calendarDate->format('n'), (int)$calendarDate->format('j'));
                    $deliveryDateExpected = clone $delivery->getTimeArrival();
                    $deliveryDateExpected->setDate((int)$calendarDate->format('Y'), (int)$calendarDate->format('n'), (int)$calendarDate->format('j'));

                    $deliveryInfoSchedule = [
                        'order_date' => $orderDate,
                        'delivery_date_expected' => $deliveryDateExpected,
                        'delivery_date_actual' => $this->getClosestArrivalDate($delivery->getWarehouseTo(), $deliveryDateExpected),
                        'is_working' => $isWorkingDay,
                        'is_extra' => false,
                    ];

                    $deliveryInfo[$delivery->getWarehouseTo()->getId()]['schedule'][] = $deliveryInfoSchedule;
                }
            }

            foreach ($supplier->getDeliveryExtraOutgoing() as $deliveryExtra) {
                /* @var $deliveryExtra SupplierDeliveryExtra */
                if ($destinationId && $destinationId !== $deliveryExtra->getSupplierTo()->getId()) {
                    continue;
                }

                if ($calendarDate != $deliveryExtra->getOrderDate()) {
                    continue;
                }

                if (!array_key_exists($deliveryExtra->getSupplierTo()->getId(), $deliveryInfo)) {
                    $deliveryInfo[$deliveryExtra->getSupplierTo()->getId()]['departure_name'] = $deliveryExtra->getSupplierTo()->getName();
                    $deliveryInfo[$deliveryExtra->getSupplierTo()->getId()]['departure_id'] = $deliveryExtra->getSupplierTo()->getId();

                    if (!array_key_exists('schedule', $deliveryInfo[$deliveryExtra->getSupplierTo()->getId()])) {
                        $deliveryInfo[$deliveryExtra->getSupplierTo()->getId()]['schedule'] = [];
                    }
                }

                $extraOrderDate = $deliveryExtra->getOrderTime();
                $extraOrderDate->setDate((int)$deliveryExtra->getOrderDate()->format('Y'), (int)$deliveryExtra->getOrderDate()->format('n'), (int)$deliveryExtra->getOrderDate()->format('j'));

                $deliveryInfoExtra = [
                    'order_date' => $extraOrderDate,
                    'delivery_date_expected' => $deliveryExtra->getDeliveryDate(),
                    'is_supply' => $deliveryExtra->getIsSupply(),
                    'delivery_date_actual' => $deliveryExtra->getIsSupply() ? $this->getClosestArrivalDate($deliveryExtra->getSupplierTo(), $deliveryExtra->getDeliveryDate()): false,
                    'is_working' => $isWorkingDay,
                    'is_extra' => true,
                    'extra_id' => $deliveryExtra->getId(),
                ];

                $deliveryInfo[$deliveryExtra->getSupplierTo()->getId()]['extra'][$extraOrderDate->getTimestamp()] = $deliveryInfoExtra;
            }

            $calendarDate->add(new DateInterval('P1D'));
        }

        foreach ($deliveryInfo as $arrivalId => $delivery) {
            $deliveryCaption = sprintf('%s (#%s)', $delivery['departure_name'], $arrivalId);
            $arrivalDeliveries = [];

            foreach ($delivery['schedule'] as $deliverySchedule) {
                $deliveryScheduleFinal = $delivery['extra'][$deliverySchedule['order_date']->getTimestamp()] ?? $deliverySchedule;

                $deliveryRow = [
                    'is_extra' => $deliveryScheduleFinal['is_extra'],
                    'extra_id' => $deliveryScheduleFinal['extra_id'] ?? null,
                    'is_extra_overrided' => $deliveryScheduleFinal['is_extra'],
                    'is_supply' => $deliveryScheduleFinal['is_supply'] ?? true,
                    'departure_date' => $deliveryScheduleFinal['order_date']->format('d.m.Y'),
                    'departure_time' => $deliveryScheduleFinal['order_date']->format('H:i'),
                    'departure_is_working' => $deliveryScheduleFinal['is_working'],
                    'arrival_is_working' => $deliveryScheduleFinal['delivery_date_expected'] == $deliveryScheduleFinal['delivery_date_actual'],
                    'arrival_date' => $deliveryScheduleFinal['delivery_date_expected'] ? $deliveryScheduleFinal['delivery_date_expected']->format('d.m.Y H:i') : false,
                    'is_success' => $deliveryScheduleFinal['is_working'] && $deliveryScheduleFinal['delivery_date_expected'] === $deliveryScheduleFinal['delivery_date_actual'],
                ];

                $arrivalDeliveries[$deliveryScheduleFinal['order_date']->getTimestamp()] = $deliveryRow;
            }

            foreach ($delivery['extra'] as $deliveryExtraTimestamp => $deliveryExtra) {
                if (array_key_exists($deliveryExtraTimestamp, $arrivalDeliveries) || !$deliveryExtra['is_supply']) {
                    continue;
                }

                $deliveryRow = [
                    'is_extra' => true,
                    'extra_id' => $deliveryExtra['extra_id'],
                    'is_extra_overrided' => false,
                    'is_supply' => $deliveryExtra['is_supply'] ?? true,
                    'departure_date' => $deliveryExtra['order_date']->format('d.m.Y'),
                    'departure_time' => $deliveryExtra['order_date']->format('H:i'),
                    'departure_is_working' => $deliveryExtra['is_working'],
                    'arrival_is_working' => $deliveryExtra['delivery_date_expected'] == $deliveryExtra['delivery_date_actual'],
                    'arrival_date' => $deliveryExtra['delivery_date_expected'] ? $deliveryExtra['delivery_date_expected']->format('d.m.Y H:i') : false,
                    'is_success' => $deliveryExtra['is_working'] && $deliveryExtra['delivery_date_expected'] === $deliveryExtra['delivery_date_actual'],
                ];

                $arrivalDeliveries[$deliveryExtra['order_date']->getTimestamp()] = $deliveryRow;
            }

            if (!$arrivalDeliveries) {
                continue;
            }

            ksort($arrivalDeliveries);

            if (null === $destinationId) {
                $deliveries[$deliveryCaption]['deliveries'] = array_values($arrivalDeliveries);
                $deliveries[$deliveryCaption]['destination_id'] = $delivery['departure_id'];
            } else {
                $deliveries = array_values($arrivalDeliveries);
            }
        }

        if (null === $destinationId) {
            ksort($deliveries);
        }

        return $deliveries;
    }

    /**
     * @param string $destinationId
     * @return array
     */
    private function getOrigins(string $destinationId = null): array
    {
        $origins = [];

        $originsSql = '
            SELECT
                CONCAT("s", s.id) as id,
                s.name
            FROM supplier s
            WHERE 1 = 1
                AND EXISTS (
                    SELECT
                        id
                    FROM supplier_delivery_schedule
                    WHERE 1 = 1
                        AND supplier_id = s.id
                )
            UNION SELECT
                CONCAT("b", b.id) as id,
                b.name
            FROM branch b
            JOIN warehouse w ON w.branch_id = b.id
            WHERE 1 = 1
                AND EXISTS (
                    SELECT
                        id
                    FROM delivery_route_sheet
                    WHERE 1 = 1
                        AND warehouse_from_id = w.id
                )
        ';

        if (null !== $destinationId) {
            $originsSql .= ' AND b.id != ' . ltrim($destinationId, 'b');
        }

        $originsRows = $this->getDoctrine()->getConnection()->executeQuery($originsSql)->fetchAll();

        foreach ($originsRows as $originsRow) {
            $origins[$originsRow['id']] = $originsRow['name'];
        }

        return $origins;
    }

    /**
     * @param string $originId
     * @return array
     */
    private function getDestinations(string $originId = null): array
    {
        $destinations = [];

        $destinationsSql = '
            SELECT
                CONCAT("b", b.id) as id,
                b.name
            FROM branch b
            JOIN warehouse w ON w.branch_id = b.id
            WHERE 1 = 1
                AND EXISTS (
                    SELECT
                        id
                    FROM delivery_route_sheet
                    WHERE 1 = 1
                        AND warehouse_to_id = w.id
                )
        ';

        if ($originId && 'b' === $originId[0]) {
            $destinationsSql .= ' AND b.id != ' . ltrim($originId, 'b');
        }

        $destinationsRows = $this->getDoctrine()->getConnection()->executeQuery($destinationsSql)->fetchAll();

        foreach ($destinationsRows as $destinationsRow) {
            $destinations[$destinationsRow['id']] = $destinationsRow['name'];
        }

        return $destinations;
    }

    /**
     * @return array
     */
    private function getSupplierDeliveryDestinations(): array
    {
        $destinationsSql = '
            SELECT DISTINCT
                CONCAT("s", sds.supplier_id) as supplier_id,
                CONCAT("b", w.branch_id) as branch_id
            FROM supplier_delivery_schedule sds
            JOIN warehouse w ON w.id = sds.warehouse_id
        ';

        $destinationsRows = $this->getDoctrine()->getConnection()->executeQuery($destinationsSql)->fetchAll();

        return $destinationsRows;
    }

    /**
     * @return array
     */
    private function getWarehouseDeliveryDestinations(): array
    {
        $destinationsSql = '
            SELECT DISTINCT
                CONCAT("b", wf.branch_id) as from_branch_id,
                CONCAT("b", wt.branch_id) as to_branch_id
            FROM delivery_route_sheet drs
            JOIN warehouse wf ON wf.id = drs.warehouse_from_id
            JOIN warehouse wt ON wt.id = drs.warehouse_to_id
        ';

        $destinationsRows = $this->getDoctrine()->getConnection()->executeQuery($destinationsSql)->fetchAll();

        return $destinationsRows;
    }

    /**
     * @return array
     */
    private function getAdjacencyLists(): array
    {
        $adjacencyLists = [];
        $supplierDestinations = $this->getSupplierDeliveryDestinations();

        foreach ($supplierDestinations as $supplierDestination) {
            $adjacencyLists[$supplierDestination['supplier_id']][$supplierDestination['branch_id']] = 1;
        }

        $warehouseDestinations = $this->getWarehouseDeliveryDestinations();

        foreach ($warehouseDestinations as $warehouseDestination) {
            $adjacencyLists[$warehouseDestination['from_branch_id']][$warehouseDestination['to_branch_id']] = 1;
        }

        return $adjacencyLists;
    }

    /**
     * @param string $originId
     * @param string $destinationId
     * @return array
     */
    private function getDeliveryRoute(string $originId, string $destinationId): array
    {
        $result = [];
        $visited = [];
        $path = [];
        $queue = [];
        $adjacencyLists = $this->getAdjacencyLists();
        $vertexCount = count($adjacencyLists);

        foreach ($adjacencyLists as $vertex => $adjacencyList) {
            $queue[$vertex] = INF;
            asort($adjacencyList);
            $adjacencyLists[$vertex] = $adjacencyList;
        }

        $queue[$originId] = 0;

        while (count($visited) < $vertexCount) {
            $minVertex = array_search(min($queue), $queue, true);

            foreach ($adjacencyLists[$minVertex] as $neighbor => $neighborCost) {
                if (in_array($neighbor, $visited)) {
                    continue;
                }

                $minCost = $queue[$minVertex];

                if ($minCost + $neighborCost < ($queue[$neighbor] ?? INF)) {
                    $queue[$neighbor] = $minCost + $neighborCost;
                    $path[$neighbor] = $minVertex;
                }
            }

            $visited[] = $minVertex;
            unset($queue[$minVertex]);
        }

        if (!array_key_exists($destinationId, $path)) {
            return $result;
        }

        $pos = $destinationId;

        while ($pos !== $originId) {
            $result[] = $pos;
            $pos = $path[$pos];
        }

        if ($result) {
            $result[] = $originId;
        }

        $resultReversed = array_reverse($result);

        return $resultReversed;
    }

    /**
     * @param string $originId
     * @param string $destinationId
     * @param string $orderDate
     * @param int $orderCreatingMinutes
     * @param int $deliveryAcceptingMinutes
     * @return array
     */
    private function getDeliveryInfo(
        string $originId,
        string $destinationId,
        DateTime $orderDate,
        int $orderCreatingMinutes,
        int $deliveryAcceptingMinutes
    ): array {
        $result = [];
        $em = $this->getDoctrine()->getManager();
        $deliveryRoute = $this->getDeliveryRoute($originId, $destinationId);
        $orderCreatingInterval = new DateInterval(sprintf('PT%dM', $orderCreatingMinutes));
        $deliveryAcceptingInterval = new DateInterval(sprintf('PT%dM', $deliveryAcceptingMinutes));
        $orderDateCurrent = clone $orderDate;
        $orderCreatingDate = $orderDateCurrent->add($orderCreatingInterval);
        $result['orderDate'] = $orderCreatingDate->format('d.m.Y H:i');
        $departureId = array_shift($deliveryRoute);
        $destinationId = (int)ltrim($destinationId, 'b');

        while ($intermediateArrivalBranchId = array_shift($deliveryRoute)) {
            $resultRoute = [];
            $intermediateArrivalBranchId = (int)ltrim($intermediateArrivalBranchId, 'b');
            /* @var $arrivalBranch Branch */
            $arrivalBranch = $em->find(Branch::class, $intermediateArrivalBranchId);
            $departurePointIsSupplier = 's' === $departureId[0];
            $cycleCount = 0;

            do {
                if ($departurePointIsSupplier) {
                    $supplierId = (int)ltrim($departureId, 's');
                    $em = $this->getDoctrine()->getManager();
                    /* @var $supplier Supplier */
                    $supplier = $em->find(Supplier::class, $supplierId);
                    $expectedDeliveryDate = $this->getSupplierClosestDeliveryDate($orderDateCurrent, $supplierId, $arrivalBranch->getWarehouse()->getId());
                    $expectedDepartureDate = $this->getSupplierClosestDepartureDate($orderDateCurrent, $supplierId, $arrivalBranch->getWarehouse()->getId());
                    $resultRoute['departurePointName'] = $supplier->getName();
                    $resultRoute['departurePointId'] = 's' . $supplierId;
                    $resultRoute['departureDate'] = $expectedDepartureDate->format('d.m.Y H:i');
                } else {
                    $departureBranchId = (int)ltrim($departureId, 'b');
                    /* @var $departureBranch Branch */
                    $departureBranch = $em->find(Branch::class, $departureBranchId);
                    $expectedDeliveryDate = $this->getWarehouseClosestDeliveryDate($orderDateCurrent, $departureBranch->getWarehouse()->getId(), $arrivalBranch->getWarehouse()->getId());
                    $expectedDepartureDate = $this->getWarehouseClosestDepartureDate($orderDateCurrent, $departureBranch->getWarehouse()->getId(), $arrivalBranch->getWarehouse()->getId());
                    $resultRoute['departurePointName'] = $departureBranch->getWarehouse()->getName();
                    $resultRoute['departurePointId'] = 'b' . $departureBranchId;
                    $resultRoute['departureDate'] = $expectedDepartureDate->format('d.m.Y H:i');
                }

                $resultRoute['arrivalPointName'] = $arrivalBranch->getWarehouse()->getName();
                $resultRoute['arrivalPointId'] = 'b' . $arrivalBranch->getId();

                $intermediateDeliveryDate = $this->getClosestArrivalDate($arrivalBranch->getWarehouse(), $expectedDeliveryDate);

                if ($expectedDeliveryDate != $intermediateDeliveryDate) {
                    $orderDateCurrent = $expectedDepartureDate;
                }
            } while ($expectedDeliveryDate != $intermediateDeliveryDate);

            $resultRoute['arrivalDate'] = $intermediateDeliveryDate->format('d.m.Y H:i');
            $intermediateDeliveryDate->add($deliveryAcceptingInterval);
            $resultRoute['acceptedDate'] = $intermediateDeliveryDate->format('d.m.Y H:i');
            $result['route'][] = $resultRoute;

            if ($intermediateArrivalBranchId === $destinationId) {
                $result['deliveryDate'] = $this->getClosestArrivalDate($arrivalBranch, $intermediateDeliveryDate)->format('d.m.Y H:i');
                $result['deliveryPointName'] = $arrivalBranch->getName();
                $result['orderDeadline'] = DateTime::createFromFormat('d.m.Y H:i', $result['route'][0]['departureDate'])->sub($orderCreatingInterval)->format('d.m.Y H:i');
            } else {
                $departureId = 'b' . $arrivalBranch->getId();
                $orderDateCurrent = $intermediateDeliveryDate;
            }
        }

        return $result;
    }

    /**
     * @param AbstractWorkingPlace $workingPlace
     * @param DateTime $expectedArrivalDate
     * @return DateTime
     */
    private function getClosestArrivalDate(AbstractWorkingPlace $workingPlace, DateTime $expectedArrivalDate): DateTime
    {
        $oneDayInterval = new DateInterval('P1D');
        $intermediateDeliveryDay = $workingPlace->getClosestWorkingDay($expectedArrivalDate);
        $workingDayBegin = DateTime::createFromFormat('H:i', $intermediateDeliveryDay['time_from']);
        $workingDayBegin->setDate(
            (int)$intermediateDeliveryDay['date']->format('Y'),
            (int)$intermediateDeliveryDay['date']->format('n'),
            (int)$intermediateDeliveryDay['date']->format('j')
        );

        if ($this->isEquialDates($expectedArrivalDate, $intermediateDeliveryDay['date'])) {
            $workingDayEnd = DateTime::createFromFormat('H:i', $intermediateDeliveryDay['time_to']);
            $workingDayEnd->setDate(
                (int)$intermediateDeliveryDay['date']->format('Y'),
                (int)$intermediateDeliveryDay['date']->format('n'),
                (int)$intermediateDeliveryDay['date']->format('j')
            );

            if ($expectedArrivalDate >= $workingDayEnd) {
                $intermediateDeliveryDay = $workingPlace->getClosestWorkingDay(clone $expectedArrivalDate->add($oneDayInterval));
                $closestArrivalDate = DateTime::createFromFormat('H:i', $intermediateDeliveryDay['time_from']);
                $closestArrivalDate->setDate(
                    (int)$intermediateDeliveryDay['date']->format('Y'),
                    (int)$intermediateDeliveryDay['date']->format('n'),
                    (int)$intermediateDeliveryDay['date']->format('j')
                );
            } elseif ($expectedArrivalDate < $workingDayBegin) {
                $closestArrivalDate = $workingDayBegin;
            } else {
                $closestArrivalDate = $expectedArrivalDate;
            }
        } else {
            $closestArrivalDate = $workingDayBegin;
        }

        return $closestArrivalDate;
    }

    /**
     * @param DateTime $firstDate
     * @param DateTime $secondDate
     * @return bool
     */
    private function isEquialDates(DateTime $firstDate, DateTime $secondDate): bool
    {
        $firstDateToCompare = clone $firstDate;
        $firstDateToCompare->setTime(0, 0);
        $secondDateToCompare = clone $secondDate;
        $secondDateToCompare->setTime(0, 0);

        return $firstDateToCompare == $secondDateToCompare;
    }

    /**
     * @param DateTime $date
     * @param int $supplierId
     * @param int $warehouseId
     * @return DateTime
     */
    private function getSupplierClosestDeliveryDate(DateTime $date, int $supplierId, int $warehouseId): DateTime
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $supplier Supplier */
        $supplier = $em->find(Supplier::class, $supplierId);
        $closestDeliveryDate = $supplier->getClosestDeliveryDate($warehouseId, $date);

        return $closestDeliveryDate;
    }

    /**
     * @param DateTime $date
     * @param int $supplierId
     * @param int $warehouseId
     * @return DateTime
     */
    private function getSupplierClosestDepartureDate(DateTime $date, int $supplierId, int $warehouseId): DateTime
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $supplier Supplier */
        $supplier = $em->find(Supplier::class, $supplierId);
        $closestDeliveryDate = $supplier->getClosestDepartureDate($warehouseId, $date);

        return $closestDeliveryDate;
    }

    /**
     * @param DateTime $date
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @return DateTime
     */
    private function getWarehouseClosestDeliveryDate(DateTime $date, int $fromWarehouseId, int $toWarehouseId): DateTime
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $warehouse Warehouse */
        $warehouse = $em->find(Warehouse::class, $fromWarehouseId);
        $closestDeliveryDate = $warehouse->getClosestDeliveryDate($toWarehouseId, $date);

        return $closestDeliveryDate;
    }

    /**
     * @param DateTime $date
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @return DateTime
     */
    private function getWarehouseClosestDepartureDate(DateTime $date, int $fromWarehouseId, int $toWarehouseId): DateTime
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $warehouse Warehouse */
        $warehouse = $em->find(Warehouse::class, $fromWarehouseId);
        $closestDeliveryDate = $warehouse->getClosestDepartureDate($toWarehouseId, $date);

        return $closestDeliveryDate;
    }
}

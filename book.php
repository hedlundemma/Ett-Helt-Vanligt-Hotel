<?php


declare(strict_types=1);
require __DIR__ . "/vendor/autoload.php";
include 'hotelFunctions.php';

const roomPrices = [
    'budget' => 3,
    'standard' => 5,
    'luxury' => 8
];

$client = new \GuzzleHttp\Client();

$transferCode = $_POST['transferCode'];
$arrival = $_POST['arrival'];
$departure = $_POST['departure'];
$db = connect('vanligtHotelDB.sqlite');
//$totalPrice = calcTotalPrice();

function checkIfDateIsFree(DateTime $arrival, DateTime $departure, $db)
{

    //connect to db
    $query = 'SELECT booking.arrival, booking.departure FROM booking';
    $sth = $db->query($query);
    $bookedDates = $sth->fetchAll(PDO::FETCH_ASSOC);


    $dayIntervall = DateInterval::createFromDateString('1 day');
    $datesStaying = new DatePeriod($arrival, $dayIntervall, $departure);

    $datesOccupied = [];
    foreach ($bookedDates as $bookedDate) {
        $bookedDateArrival = new DateTime($bookedDate['arrival']);
        $bookedDateDeparture = new DateTime($bookedDate['departure']);

        $datesBooked = new DatePeriod($bookedDateArrival, $dayIntervall, $bookedDateDeparture);

        foreach ($datesBooked as $bookedDate) {
            array_push($datesOccupied, $bookedDate->format('l Y-m-d'));
        }
    }

    foreach ($datesStaying as $dateStaying) {
        $dateStayingAsString = $dateStaying->format('l Y-m-d');
        foreach ($datesOccupied as $dateOccuppied) {
            if ($dateStayingAsString === $dateOccuppied) {
                return false;
            }
        }
    }

    return true;
}

function calcTotalPrice()
{
    $roomPrice = $_POST['room'];
    $daysStayed = $_POST['departure'] - $_POST['arrival'];
    $totalPrice = $roomPrice * $daysStayed;
    return $totalPrice;
}

function checkIfTransferCodeIsValid(string $transferCode, int $roomPrice, $client)
{
    $response =  $client->post('https://www.yrgopelago.se/centralbank/transferCode', [
        'form_params' => [
            'transferCode' => $transferCode,
            'totalcost' => roomPrices[$roomPrice]
        ]
    ]);

    $responseBody = json_decode((string)$response->getBody(), true);

    if (isset($responseBody['error'])) {
        return true;
    } else {
        return false;
    }
}

//TODO:: don't have user name in code
function beginTransaction($client, $transferCode)
{
    $response =  $client->post('https://www.yrgopelago.se/centralbank/transferCode', [
        'form_params' => [
            'user' => 'Lucas',
            'transfercode' => $transferCode
        ]
    ]);
}

function printJson($arrival, $departure, $totalCost)
{
    $confirmJson = [
        'island' => 'La isla normal',
        'hotel' => 'Det Vanliga Hotelet',
        'arrival_date' => $arrival,
        'departure_date' => $departure,
        'total_cost' => $totalCost,
        'stars' => 3,
        'features' => 'none',
        'additional_info' => ''
    ];

    echo json_encode($confirmJson);
}

function logToDB($arrival, $departure, $costumer, $room)
{
    $db = connect('vanligtHotelDB.sqlite');
    $query = 'INSERT INTO booking VALUES
    (6,  "' . $arrival . '" , "' . $departure . '", "' . $costumer . '" , "' . $room . '")';


    $sth = $db->prepare($query);
    $sth->execute();
}

function calcDaysBeetwenArrivalAndDepature($arrival, $departure)
{
    $daysInSeconds = strtotime($arrival) - strtotime($departure);
    return abs(round($daysInSeconds / 86400));
}

//logToDB($arrival, $departure, 'Ander', 'budget');



//execute
/*
if (checkIfTransferCodeIsValid($transferCode, $roomPrice, $client)) {
    beginTransaction($client, $transferCode);
    logToDB();
} else {
    echo 'The transferCode is not valid';
}
*/

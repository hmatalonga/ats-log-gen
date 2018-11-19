<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/Statement.php';
require_once __DIR__ . '/lib/Registar.php';

$GLOBALS['users'] = 0;
$GLOBALS['block'] = 0;
$GLOBALS['groups'] = 0;

$GLOBALS['session'] = 'logout';

$GLOBALS['stack'] = [
    'empresa' => [],
    'cliente' => [],
    'condutor' => [],
];

// Helpers --------------------------------------------------------------------

function str_quoted($str = '')
{
    return "\"$str\"";
}

function appendToStack($ref, $value)
{
    array_push($GLOBALS['stack'][$ref], $value);
}

function getFromStack($ref, $asArray = false, $delimiter = ':')
{
    if (getCountFromStack($ref) === 0) {
        return null;
    }

    $idx = array_rand($GLOBALS['stack'][$ref]);
    if ($asArray) {
        return explode($delimiter, $GLOBALS['stack'][$ref][$idx]);
    }

    return $GLOBALS['stack'][$ref][$idx];
}

function getCountFromStack($ref)
{
    return count($GLOBALS['stack'][$ref]);
}

function getCredentials()
{
    $items = array_merge(
        $GLOBALS['stack'][Registar::List[Registar::Cliente]]
    );
    $idx = array_rand($items);
    return explode(':', $items[$idx]);
}

function genPassword($faker)
{
    return str_quoted($faker->regexify('[A-Z0-9a-z]{6,10}'));
}

function genPlate($faker)
{
    return str_quoted($faker->regexify('[0-9]{2}-[A-Z]{2}-[0-9]{2}'));
}

function genTuple($faker)
{
    $x = $faker->randomFloat(1, 0, 100);
    $y = $faker->randomFloat(1, 0, 100);
    return "($x, $y)";
}

// Statements Generators ------------------------------------------------------

function genEmpresa($faker)
{
    $name = str_quoted($faker->company);
    $pass = str_quoted($faker->regexify('[A-Z0-9a-z]{6,10}'));

    appendToStack(Registar::List[Registar::Empresa], "$name:$pass");

    return Registar::List[Registar::Empresa] . " $name $pass ;";
}

function genCliente($faker)
{
    $user = str_quoted($faker->freeEmail);
    $name = str_quoted($faker->name);
    $pass = genPassword($faker);
    $address = str_quoted($faker->address);
    $birthday = $faker->date();
    $tuple = genTuple($faker);

    appendToStack(Registar::List[Registar::Cliente], "$user:$pass");

    return Registar::List[Registar::Cliente] . " $user $name $pass $address $birthday $tuple ;";
}

function genCondutor($faker)
{
    $user = str_quoted($faker->freeEmail);
    $name = str_quoted($faker->name);
    $pass = genPassword($faker);
    $address = str_quoted($faker->address);
    $birthday = $faker->date();
    $number = $faker->numberBetween(10, 100);
    // get company name
    $company = $faker->boolean ?
                getFromStack(Registar::List[Registar::Empresa], true) :
                null;

    appendToStack(Registar::List[Registar::Condutor], "$user:$pass");

    $cmd = Registar::List[Registar::Condutor];

    if (is_null($company)) {
        return "$cmd $user $name $pass $address $birthday $number ;";
    }
    return "$cmd $user $name $pass $address $birthday $number $company[0] ;";
}

function genVehicle($faker, $vehicleType)
{
    $plate = genPlate($faker);
    $reliability = $faker->numberBetween(10, 100);
    $tuple = genTuple($faker);
    // get company name
    $company = $faker->boolean ?
                getFromStack(Registar::List[Registar::Empresa], true) :
                null;

    $cmd = Registar::List[$vehicleType];

    if (is_null($company)) {
        // Get a driver
        $driver = getFromStack(Registar::List[Registar::Condutor], true);
        return "$cmd $plate $reliability $tuple $driver[0] ;";
    }
    return "$cmd $plate $reliability $tuple empresa $company[0] ;";
}

function genRegistar($faker, $stmt)
{
    $constraints = function ($stmt) {
        if ($GLOBALS['users'] > 0) {
            // If statement number is a vehicle, make sure there are drivers
            if ($stmt === Registar::Mota) {
                return getCountFromStack(Registar::List[Registar::Condutor]) > 0;
            }
            return true;
        }
        return $stmt >= Registar::Empresa && $stmt <= Registar::Condutor;
    };

    $regType = $faker->valid($constraints)->numberBetween(0, 6);

    switch ($regType) {
        case Registar::Empresa:
            return $stmt . genEmpresa($faker);
        case Registar::Cliente:
            $GLOBALS['users'] += 1;
            return $stmt . genCliente($faker);
        case Registar::Condutor:
            return $stmt . genCondutor($faker);
        case Registar::Mota:
        case Registar::Carro:
        case Registar::Carrinha:
        case Registar::Helicoptero:
            return $stmt . genVehicle($faker, $regType);
  }
}

function genStatement($faker, $constraints)
{
    // Constraints to generate lines following a correct sequence
    $stmtType = null;
    $isValid = false;

    while (!$isValid) {
        try {
            $stmtType = $faker
            ->optional($weight = 0.85, $default = Statement::Registar)
            ->numberBetween(0, 5);
        } catch (\OverflowException $e) {
        }
        if ($constraints($stmtType)) {
            $isValid = true;
        }
    }

    // Match statements
    switch ($stmtType) {
      case Statement::Viajar:
      case Statement::Recusar:
        return Statement::List[$stmtType] . ';';
      case Statement::Login:
        $GLOBALS['session'] = Statement::List[Statement::Login];
        $credentials = getCredentials();
        return "\n" . Statement::List[$stmtType] . " $credentials[0] $credentials[1] ;";
      case Statement::Logout:
        $GLOBALS['session'] = Statement::List[Statement::Logout];
        return Statement::List[$stmtType] . ";\n" ;
      case Statement::Solicitar:
        $tuple = genTuple($faker);
        return Statement::List[$stmtType] . " $tuple ;";
      case Statement::Registar:
        $stmt = Statement::List[$stmtType] . ' ';
        return genRegistar($faker, $stmt);
    }
}

function main($numOfBlocks = 10, $maxBlockSize = 12, $debug = false)
{
    $faker = Faker\Factory::create('pt_PT');

    $constraints = function ($stmt) use ($maxBlockSize) {
        if ($GLOBALS['users'] === 0) {
            return $stmt === 1;
        }
        if ($GLOBALS['session'] === 'login') {
            if ($GLOBALS['block'] == $maxBlockSize) {
                $GLOBALS['block'] = 0;
                return $stmt == 5;
            }
            if ($stmt === 5) {
                $GLOBALS['block'] = 0;
                $GLOBALS['groups'] += 1;
            } else {
                $GLOBALS['block'] += 1;
            }
            return $stmt >= 2 && $stmt <= 5;
        } else {
            return $stmt >= 0 && $stmt <= 1;
        }
    };

    while ($GLOBALS['groups'] <= $numOfBlocks) {
        $line = genStatement($faker, $constraints);
        echo "$line\n";
    }

    if ($debug) {
        print_r($GLOBALS['stack']);
    }
}

if ($argc >= 2) {
    main($numOfBlocks = intval($argv[1]));
} else {
    main();
}

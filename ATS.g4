grammar ATS;

@header
{
  import java.awt.geom.Point2D;
  import java.time.LocalDate;
  import java.lang.Double;
}

@members
{
  UMeR umer = null;
  
  String user = "";
  
  String pass = "";
  
  /** Helper methods -------------------------- */

  private String unquote(String str) {
    return str.substring(1,str.length()-1);
  }

  private String[] parsePoint(String point) {
    return unquote(point).split("\\s*,\\s*");
  }

  private Point2D.Double makePoint(String point) {
    String[] coordinates = parsePoint(point);
    double x = Double.parseDouble(coordinates[0]);
    double y = Double.parseDouble(coordinates[1]);
    return new Point2D.Double(x, y);
  }

  private void login(String user, String pass) {
    this.user = user;
    this.pass = pass;
  }

  private void logout() {
    user = "";
    pass = "";
  }
  
}

/**
 *
 * REGRAS
 *
 */

//início

log : statement* ;

statement
  : 'login' user=STRING pass=STRING
  { login(unquote($user.getText()), unquote($pass.getText()));}
  | 'registar' stmt_register
  | 'solicitar' point=TUPLE
  {
    $ctx.getStart();
    Trip trip = umer.newTripClosest(
      umer.getClients().get(user),
      makePoint($point.getText())
    );
    try {
      if (trip != null)
        umer.addTrip(trip.getClient(), trip.getDriver(), trip.getLicencePlate(), trip);
      else
        System.out.println($ctx.getStart().getLine());
    } catch(NullPointerException e) {
      e.printStackTrace();
    }
  }
  | 'recusar viagem'
  | 'viajar'
  | 'logout'
  { logout(); }
  ;

stmt_register
  : 'condutor' reg_condutor
  | 'empresa' user=STRING pass=STRING
  { umer.registerCompany(unquote($user.getText()), unquote($pass.getText())); }
  | 'cliente' email=STRING user=STRING pass=STRING address=STRING birthday=DATE TUPLE
  { 
    umer.registerUser(
        new Client(
            unquote($email.getText()),
            unquote($user.getText()),
            unquote($pass.getText()),
            unquote($address.getText()),
            LocalDate.parse($birthday.getText())
        ),
        null
    );
  }
  | 'mota' serial=STRING reliability=NUMBER position=TUPLE email=STRING
  { 
    umer.registerVehicleP(
      new Bike(
        unquote($serial.getText()), 
        Double.parseDouble($reliability.getText()), 
        makePoint($position.getText()),
        unquote($email.getText())
      )
    );
  }
  | 'mota' serial=STRING reliability=NUMBER position=TUPLE 'empresa' owner=STRING
  {
    umer.registerVehicleP(
      new Van(
        unquote($serial.getText()),
        Double.parseDouble($reliability.getText()),
        makePoint($position.getText()),
        unquote($owner.getText())
      )
    );
  }
  | 'carro' serial=STRING reliability=NUMBER position=TUPLE email=STRING
  {
    umer.registerVehicleP(
      new Car(
        unquote($serial.getText()),
        Double.parseDouble($reliability.getText()),
        makePoint($position.getText()),
        unquote($email.getText())
      )
    );
  }
  | 'carro' serial=STRING reliability=NUMBER position=TUPLE 'empresa' owner=STRING
  {
    umer.registerVehicleP(
      new Van(
        unquote($serial.getText()),
        Double.parseDouble($reliability.getText()),
        makePoint($position.getText()),
        unquote($owner.getText())
      )
    );
  }
  | 'carrinha' serial=STRING reliability=NUMBER position=TUPLE email=STRING
  {
    umer.registerVehicleP(
      new Van(
        unquote($serial.getText()),
        Double.parseDouble($reliability.getText()),
        makePoint($position.getText()),
        unquote($email.getText())
      )
    );
  }
  | 'carrinha' serial=STRING reliability=NUMBER position=TUPLE 'empresa' owner=STRING
  {
    umer.registerVehicleP(
      new Van(
        unquote($serial.getText()),
        Double.parseDouble($reliability.getText()),
        makePoint($position.getText()),
        unquote($owner.getText())
      )
    );
  }
  | 'helicoptero' serial=STRING reliability=NUMBER position=TUPLE email=STRING
  {
    umer.registerVehicleP(
      new Van(
        unquote($serial.getText()),
        Double.parseDouble($reliability.getText()),
        makePoint($position.getText()),
        unquote($email.getText())
      )
    );
  }
  | 'helicoptero' serial=STRING reliability=NUMBER position=TUPLE 'empresa' owner=STRING
  { 
    umer.registerVehicleP(
      new Van(
        unquote($serial.getText()),
        Double.parseDouble($reliability.getText()),
        makePoint($position.getText()),
        unquote($owner.getText())
      )
    );
  }
  ;

reg_condutor
  : email=STRING user=STRING pass=STRING address=STRING birthday=DATE time=NUMBER
  {
    umer.registerUser(
      new Driver(
        unquote($email.getText()),
        unquote($user.getText()),
        unquote($pass.getText()),
        unquote($address.getText()),
        LocalDate.parse($birthday.getText()),
        Double.parseDouble($time.getText()),
        null
      ),
      null
    );
  }
  | email=STRING user=STRING pass=STRING address=STRING birthday=DATE time=NUMBER company=STRING
  {
    umer.registerUser(
      new Driver(
        unquote($email.getText()),
        unquote($user.getText()),
        unquote($pass.getText()),
        unquote($address.getText()),
        LocalDate.parse($birthday.getText()),
        Double.parseDouble($time.getText()),
        unquote($company.getText())
      ),
      unquote($company.getText())
    );
  }
  ;

DATE
  : DIGIT DIGIT DIGIT DIGIT '-' DIGIT? DIGIT '-' DIGIT? DIGIT
  ;

TUPLE
  : '(' DECIMAL (',' | ', ') DECIMAL ')'
  ;

NUMBER
  : DIGIT +
  ;

DECIMAL
  : DIGIT + '.' DIGIT +
  ;

/** 
  original definition:
  '"' (~[\\"] | '\\' [\\"])* '"'
*/
STRING
  : '"' ~ ["\r\n] * '"'
  ;

DIGIT
  : [0-9]
  ;

STMT_END
  : ';' -> skip
  ;

WS
  : [ \t\r\n]+ -> skip
  ;
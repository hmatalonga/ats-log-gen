<?php

abstract class Statement
{
    const Login = 0;
    const Registar = 1;
    const Solicitar = 2;
    const Recusar = 3;
    const Viajar = 4;
    const Logout = 5;

    const List = [
      'login',
      'registar',
      'solicitar',
      'recusar viagem',
      'viajar',
      'logout'
    ];
}

<?php

use Brick\Math\BigDecimal;

function t($expected, $n, $sd, $rm)
{
    $value = BigDecimal::of($n);
    $precision = \XRPL_PHP\Core\MathUtilities::getPrecision($sd, $rm);
    \PHPUnit\Framework\assertEquals($expected, $precision);
}

t('1e+27', '1.2345e+27', 1);


/* d = digits
 *   P.precision = P.sd = function (z) {
    var k,
      x = this;

    if (z !== void 0 && z !== !!z && z !== 1 && z !== 0) throw Error(invalidArgument + z);

    if (x.d) {
      k = getPrecision(x.d);
      if (z && x.e + 1 > k) k = x.e + 1;
    } else {
      k = NaN;
    }

    return k;
  };

   P.toPrecision = function (sd, rm) {
    var str,
      x = this,
      Ctor = x.constructor;

    if (sd === void 0) {
      str = finiteToString(x, x.e <= Ctor.toExpNeg || x.e >= Ctor.toExpPos);
    } else {
      checkInt32(sd, 1, MAX_DIGITS);

      if (rm === void 0) rm = Ctor.rounding;
      else checkInt32(rm, 0, 8);

      x = finalise(new Ctor(x), sd, rm);
      str = finiteToString(x, sd <= x.e || x.e <= Ctor.toExpNeg, sd);
    }

    return x.isNeg() && !x.isZero() ? '-' + str : str;
  };
 */


t('1e+27', '1.2345e+27', 1);
t('1.2e+27', '1.2345e+27', 2);
t('1.23e+27', '1.2345e+27', 3);
t('1.235e+27', '1.2345e+27', 4);
t('1.2345e+27', '1.2345e+27', 5);
t('1.23450e+27', '1.2345e+27', 6);
t('1.234500e+27', '1.2345e+27', 7);

t('-1e+27', '-1.2345e+27', 1);
t('-1.2e+27', '-1.2345e+27', 2);
t('-1.23e+27', '-1.2345e+27', 3);
t('-1.235e+27', '-1.2345e+27', 4);
t('-1.2345e+27', '-1.2345e+27', 5);
t('-1.23450e+27', '-1.2345e+27', 6);
t('-1.234500e+27', '-1.2345e+27', 7);

t('7', 7, 1);
t('7.0', 7, 2);
t('7.00', 7, 3);

t('-7', -7, 1);
t('-7.0', -7, 2);
t('-7.00', -7, 3);

t('9e+1', 91, 1);
t('91', 91, 2);
t('91.0', 91, 3);
t('91.00', 91, 4);
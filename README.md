# captcha\_code\_js.php
Generate captchas that require the user to fix javascript syntax errors

## Overview
A captcha generated might look like the following.

    let vdx = [-5]reverse().concat([-11]).sort();
    for(let d of [-98, 43].toString()) 
      d += vdx.join(d);
      let wcj = (-82 + (139 * 229));
      wcj += vdxindexOf(203)
    }

    There are 4 characters missing in this JS code,
    enter them in the order they are missing.

The corresponding answer is ` .{.;`

## Usage
Download captcha\_code\_js.php and include it. Use the following line to
generate a new captcha.

    $captcha = new CaptchaCodeJS();

The question and solution can be accessed through the variables
`$captcha->question` and `$captcha->solution`.

## License
[GPLv3](https://www.gnu.org/licenses/gpl-3.0.html)

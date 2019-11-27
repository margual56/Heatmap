function f(x){
  return Math.pow(x, 4)/1500000000+100;
}

function lerp(a, b, f){
  return  (a * (1.0 - f)) + (b * f);
}

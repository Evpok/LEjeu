/** @jsx dom */
var dom = React.createElement;

ReactDOM.render(dom(
  "h1",
  null,
  "Hello,",
  dom(
    "em",
    null,
    "world!"
  ),
  "  ",
  dom(
    "button",
    { "class": "btn btn-primary" },
    "OK"
  )
), document.getElementById('main'));

//# sourceMappingURL=app.js.map
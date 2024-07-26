(function (blocks, element) {
  var el = element.createElement;

  blocks.registerBlockType("var/foo", {
    title: "Foo",
    icon: "smiley",
    category: "common",
    edit: function (props) {
      return el("p", { className: props.className }, "Hello from the editor!");
    },
    save: function () {
      return el("p", {}, "Hello from the saved content!");
    },
  });
})(window.wp.blocks, window.wp.element);

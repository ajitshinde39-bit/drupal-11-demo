(function ($, Drupal, once) {

  "use strict";

  Drupal.behaviors.mexantTheme = {
    attach: function (context, settings) {

      /* -------------------------------------------
       *  HEADER SCROLL CHANGE
       * ------------------------------------------- */
      $(window, context).on("scroll", function () {
        var scroll = $(window).scrollTop();
        var box = $('.header-text', context).height();
        var header = $('header', context).height();

        if (box && header && scroll >= box - header) {
          $("header", context).addClass("background-header");
        } else {
          $("header", context).removeClass("background-header");
        }
      });

      /* -------------------------------------------
       *  ISOTOPE FILTER
       * ------------------------------------------- */
      let $grid = $(".grid", context);
      if ($grid.length) {
        $grid.isotope({
          itemSelector: ".all",
          percentPosition: true,
          masonry: { columnWidth: ".all" }
        });
      }

      once('filters', '.filters ul li', context).forEach(function (el) {
        $(el).on("click", function () {
          $('.filters ul li').removeClass('active');
          $(this).addClass('active');

          var data = $(this).attr('data-filter');
          if ($grid.length) {
            $grid.isotope({ filter: data });
          }
        });
      });

      /* -------------------------------------------
       *  ACCORDION
       * ------------------------------------------- */
      const Accordion = {
        openAccordion: function (toggle, content) {
          toggle.classList.add("is-open");
          let final_height = Math.floor(content.children[0].offsetHeight);
          content.style.height = final_height + "px";
        },

        closeAccordion: function (toggle, content) {
          toggle.classList.remove("is-open");
          content.style.height = 0;
        },

        init: function (el) {
          const sections = el.getElementsByClassName("accordion");
          const all_toggles = el.getElementsByClassName("accordion-head");
          const all_contents = el.getElementsByClassName("accordion-body");

          for (let i = 0; i < sections.length; i++) {
            const toggle = all_toggles[i];
            const content = all_contents[i];

            toggle.addEventListener("click", function () {
              if (toggle.classList.contains("is-open")) {
                Accordion.closeAccordion(toggle, content);
              } else {
                Accordion.openAccordion(toggle, content);
              }
            });
          }
        }
      };

      once('accordion-init', '.accordions', context).forEach(function (el) {
        Accordion.init(el);
      });

      /* -------------------------------------------
       *  Naccs Tabs
       * ------------------------------------------- */
      once('naccs', '.naccs .menu div', context).forEach(function (el) {
        $(el).on("click", function () {
          var numberIndex = $(this).index();

          if (!$(this).hasClass("active")) {
            $(".naccs .menu div").removeClass("active");
            $(".naccs ul li").removeClass("active");

            $(this).addClass("active");
            $(".naccs ul li").eq(numberIndex).addClass("active");

            var listItemHeight = $(".naccs ul li").eq(numberIndex).innerHeight();
            $(".naccs ul").height(listItemHeight + "px");
          }
        });
      });

      /* -------------------------------------------
       *  OWL TESTIMONIALS
       * ------------------------------------------- */
      once('owl-init', '.owl-testimonials', context).forEach(function (el) {
        $(el).owlCarousel({
          items: 1,
          loop: true,
          dots: true,
          nav: false,
          autoplay: true,
          margin: 15
        });
      });

      /* -------------------------------------------
       *  MOBILE MENU TOGGLE
       * ------------------------------------------- */
      once('menu-toggle', '.menu-trigger', context).forEach(function (el) {
        $(el).on('click', function () {
          $(this).toggleClass('active');
          $('.header-area .nav').slideToggle(200);
        });
      });

      /* -------------------------------------------
       *  SMOOTH SCROLL + ACTIVE MENU
       * ------------------------------------------- */
      let sectionMap = [];

      $('.nav a[href^="#"]', context).each(function () {
        let href = $(this).attr("href");
        if (href.length > 1 && $(href).length) {
          sectionMap.push({
            link: $(this),
            section: $(href)
          });
        }
      });

      function onScroll() {
        const scrollPos = $(document).scrollTop() + 120; // offset

        sectionMap.forEach(item => {
          if (!item.section.length) return;

          const top = item.section.position().top;
          const bottom = top + item.section.outerHeight();

          if (scrollPos >= top && scrollPos <= bottom) {
            $('.nav a').removeClass('active');
            item.link.addClass('active');
          }
        });
      }

      $(document, context).on("scroll", onScroll);

      $('.nav a[href^="#"]', context).each(function () {
        $(this).on("click", function (e) {
          e.preventDefault();

          const href = $(this).attr("href");
          const target = $(href);

          if (!target.length) return;

          $('html, body').stop().animate({
            scrollTop: target.offset().top - 110
          }, 600);

          $('.nav a').removeClass("active");
          $(this).addClass("active");
        });
      });

      /* -------------------------------------------
       *  COUNTER ANIMATION
       * ------------------------------------------- */
      $(window, context).on("scroll", function () {
        $(".count-digit", context).each(function () {
          if ($(this).hasClass("counter-loaded")) return;

          if (isVisible($(this))) {
            $(this).addClass("counter-loaded");

            let $this = $(this);
            jQuery({ Counter: 0 }).animate({
              Counter: $this.text()
            }, {
              duration: 3000,
              easing: 'swing',
              step: function () {
                $this.text(Math.ceil(this.Counter));
              }
            });
          }
        });
      });

      function isVisible($el) {
        let winTop = $(window).scrollTop();
        let winBottom = winTop + $(window).height();
        let elTop = $el.offset().top;
        let elBottom = elTop + $el.height();

        return elBottom >= winTop && elTop <= winBottom;
      }

    } // attach
  };

})(jQuery, Drupal, once);

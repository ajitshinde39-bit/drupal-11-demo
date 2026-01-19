// Note 1:  following code we used for display title and body in normal html format

// (function (Drupal, once) {
//   Drupal.behaviors.articleList = {
//     attach(context) {
//       once('article-list', '[data-article-list]', context)
//         .forEach((container) => {

//           console.log('Hello, this is coming from component js');

//           fetch('/api/articles', {
//             headers: {
//               'Accept': 'application/json'
//             }
//           })
//             .then(res => res.json())
//             // .then(data => {
//             //   console.log(data);
//             //   container.innerHTML = data.map(item => `
//             //     <article class="article">
//             //       <h3>${item.title}</h3>
//             //       <p>${item.body}</p>
//             //     </article>
//             //   `).join('');
//             // });

//             // added loop
//             .then(data => {
//               console.log(data);
//               let html = '';

//               data.forEach((item, index) => {
//                 const className = index % 2 === 0 ? 'even' : 'odd';

//                 html += `
//                   <article class="article ${className}">
//                     <h3>${item.title}</h3>
//                     <p>${item.body}</p>
//                   </article>
//                   <hr>
//                 `;
//               });

//               container.innerHTML += html;
//             });
//         });
//     }
//   };
// })(Drupal, once);

// Note 2:  following code we used for display title and body in bootstrap tabs format

(function (Drupal, once) {
  Drupal.behaviors.articleTabs = {
    attach(context) {
      once('article-tabs', '[data-article-tabs]', context)
        .forEach((wrapper) => {

          const titleContainer = wrapper.querySelector('[data-tab-titles]');
          const contentContainer = wrapper.querySelector('[data-tab-content]');

          fetch('/api/articles', {
            headers: { 'Accept': 'application/json' }
          })
            .then(res => res.json())
            .then(data => {

              data.forEach((item, index) => {
                const activeClass = index === 0 ? 'active' : '';
                const showClass = index === 0 ? 'show active' : '';

                // Tab title
                titleContainer.insertAdjacentHTML('beforeend', `
                  <li class="nav-item" role="presentation">
                    <button
                      class="nav-link ${activeClass}"
                      data-bs-toggle="tab"
                      data-bs-target="#tab-${index}"
                      type="button"
                      role="tab">
                      ${item.title}
                    </button>
                  </li>
                `);

                // Tab content
                contentContainer.insertAdjacentHTML('beforeend', `
                  <div
                    class="tab-pane fade ${showClass}"
                    id="tab-${index}"
                    role="tabpanel">
                    <p>${item.body}</p>
                  </div>
                `);
              });

            });
        });
    }
  };
})(Drupal, once);


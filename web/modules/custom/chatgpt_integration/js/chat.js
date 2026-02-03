(function (Drupal, once) {
  Drupal.behaviors.chatgptChat = {
    attach(context) {
        console.log("hello, i am here");
    //   once('chatgpt', '#chat-send', context).forEach((element) => {
    //     element.addEventListener('click', () => {
    //         element.addEventListener('click', (e) => {
          once('chatgpt', '.chatgpt-form', context).forEach((form) => {
            const button = form.querySelector('#chat-send');
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const message = document.getElementById('chat-message').value;

                if (!message) {
                    alert('Please enter a message');
                    return;
                }

                // fetch('/chatgpt/chat?message=' + encodeURIComponent(message))
                //     .then(res => res.json())
                //     .then(data => {
                //     document.getElementById('chat-response').innerText =
                //         data.reply || 'No response';
                //     })
                //     .catch((err) => {
                //     console.error(err);
                //     document.getElementById('chat-response').innerText =
                //         'Something went wrong';
                //     });

                fetch('/chatgpt/chat?message=' + encodeURIComponent(message))
                    .then(response => {
                        if (!response.ok) {
                        throw new Error('API error');
                        }
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('chat-response').innerText =
                        data.reply || 'No response from ChatGPT';
                    })
                    .catch(() => {
                        document.getElementById('chat-response').innerText =
                        '⚠️ Technical issue. Please try again later.';
                    });

                });
        });
      //});
    }
  };
})(Drupal, once);

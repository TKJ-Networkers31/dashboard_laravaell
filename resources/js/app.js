window.Echo.channel('lab-channel')
    .listen('.lab.updated', (e) => {
        window.dispatchEvent(new CustomEvent('lab-update', {
            detail: e.data
        }));
    });

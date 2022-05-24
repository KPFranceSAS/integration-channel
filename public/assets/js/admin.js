

document.addEventListener('DOMContentLoaded', () => {
    Admin.createButtonResizeControls();
});


const Admin = (() => {
    const createButtonResizeControls = () => {
        const closeButton = document.getElementById('closebutton');
        if (null !== closeButton) {
            closeButton.addEventListener('click', () => {
                const oldValue = localStorage.getItem('ea/sidebar/width') || 'normal';
                const newValue = 'normal' == oldValue ? 'compact' : 'normal';
    
                document.querySelector('body').classList.remove('ea-sidebar-width-' + oldValue);
                document.querySelector('body').classList.add('ea-sidebar-width-' + newValue);
                localStorage.setItem('ea/sidebar/width', newValue);
            });
        }
    };

    return {
        createButtonResizeControls: createButtonResizeControls,
    };
})();

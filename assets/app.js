/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// start the Stimulus application
import './bootstrap';




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


import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.togglesalechannels();
        this.togglechannels();
        this.toggleadmin();
        
    }


    togglesalechannels(event){
        var checked = document.getElementById('User_isPricingManager').checked;
        if(checked){
            this.displayElement('User_saleChannels');
        } else{
            this.hideElement('User_saleChannels');
        }
    }


    togglechannels(event){
        var checked = document.getElementById('User_isOrderManager').checked;
        if(checked){
            this.displayElement('User_channels');
        } else{
            this.hideElement('User_channels');
        }
    }

    toggleadmin(event){
        var checked = document.getElementById('User_isAdmin').checked;
        if(checked){
            this.displayElement('User_isSuperAdmin');
        } else{
            this.hideElement('User_isSuperAdmin');
        }
    }


   




    displayElement(idElement){
        this.toggleElement(idElement, true);
    }


    hideElement(idElement){
       this.toggleElement(idElement, false);
    }


    toggleElement(idElement, show) {
        var container = document.getElementById(idElement).parentNode.parentNode.parentNode;
        var label = document.getElementById(idElement);
        
        if(show){
            label.classList.add('required');
            container.classList.remove('d-none');
        } else {
            label.classList.remove('required');
            container.classList.add('d-none');
        }
       
    }




}

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.togglesalechannels();
    }


    togglesalechannels(event){
        var checked = document.getElementById('User_isPricingManager').checked;
        if(checked){
            this.displayElement('User_saleChannels');
        } else{
            this.hideElement('User_saleChannels');
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

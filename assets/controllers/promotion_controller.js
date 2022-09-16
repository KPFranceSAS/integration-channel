import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.toggletype();
        this.togglefrequency();
    }


    toggletype(event){
        var type = document.getElementById('Promotion_discountType').value;
        if(type=='percent'){
            this.displayElement('Promotion_percentageAmount');
            this.hideElement('Promotion_fixedAmount');
        } else{
            this.hideElement('Promotion_percentageAmount');
            this.displayElement('Promotion_fixedAmount');
        }
    }


    togglefrequency(event){
        var type = document.getElementById('Promotion_frequency').value;
        if(type=='time'){
            this.displayElement('Promotion_weekDays');
            this.displayElement('Promotion_beginHour');
            this.displayElement('Promotion_endHour');
        } else{
            this.hideElement('Promotion_weekDays');
            this.hideElement('Promotion_beginHour');
            this.hideElement('Promotion_endHour');
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

import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.toggletype();
        this.togglefrequency();
    }


    toggletype(event){
        var type = document.getElementById('multi_promotion_discountType').value;
        if(type=='percent'){
            this.displayElement('multi_promotion_percentageAmount');
            this.hideElement('multi_promotion_fixedAmount');
        } else{
            this.hideElement('multi_promotion_percentageAmount');
            this.displayElement('multi_promotion_fixedAmount');
        }
    }


    togglefrequency(event){
        var type = document.getElementById('multi_promotion_frequency').value;
        if(type=='time'){
            this.displayElement('multi_promotion_weekDays');
            this.displayElement('multi_promotion_beginHour');
            this.displayElement('multi_promotion_endHour');
        } else{
            this.hideElement('multi_promotion_weekDays');
            this.hideElement('multi_promotion_beginHour');
            this.hideElement('multi_promotion_endHour');
        }
    }




    displayElement(idElement){
        this.toggleElement(idElement, true);
    }


    hideElement(idElement){
       this.toggleElement(idElement, false);
    }


    toggleElement(idElement, show) {
        var container = document.getElementById(idElement).parentNode;
        var label = document.getElementById(idElement);
        
        if(show){
            label.required = true;
            container.classList.remove('d-none');
        } else {
            label.required = "";
            container.classList.add('d-none');
        }
       
    }




}

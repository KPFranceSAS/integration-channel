import { Controller } from '@hotwired/stimulus';
import 'chartjs-adapter-moment';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'eager' */
export default class extends Controller {
    connect() {
        
        this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
        this.element.addEventListener('chartjs:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
        this.element.removeEventListener('chartjs:connect', this._onConnect);
    }

    _onPreConnect(event) {
        // The chart is not yet created
        console.log(event.detail.options); // You can access the chart options using the event details
        
    }

    _onConnect(event) {

        event.detail.chart.options.plugins.tooltip = {
            position: 'nearest',
            callbacks: {
                afterLabel: function(context) {
                    return context.raw.label;
                },
                label: function(context) {
                    return context.dataset.label+' > '+context.parsed.y+' '+context.raw.currency;
                }
            }
          }

         

        // For instance you can listen to additional events
        event.detail.chart.options.onHover = (mouseEvent) => {
            /* ... */
        };
        event.detail.chart.options.onClick = (mouseEvent) => {
            /* ... */
        };
    }
}

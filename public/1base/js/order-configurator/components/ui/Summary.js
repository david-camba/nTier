import { h, initComponent } from '../../lifetree.js';

function Summary(props){
    const innerPropsKeys = [
        'model', 'color', 'extras', 'price', 
        'title', 'priceText', 'noExtrasText'
    ];

    const setProp = initComponent(props, innerPropsKeys, "Summary");

    return h('div', {class:'configurator-summary'},
        h('h2', { innerText: props.title }),
        h('div', { class: 'summary-details' }, 
        
            h('h3', {class:'model-summary'},
                h('span', { innerText: props.model.name + ' ' }),
                h('span', { innerText: `(${props.model.price.toFixed(2)} €)` })
            ),
            h('p', {class:'color-summary'}, 
                h('strong', { innerText: 'Color: ' }),
                h('span', { class: 'color-name', innerText: props.color.name + ' ' }),
                h('span', { class: 'color-price', innerText: `(+${props.color.price.toFixed(2)} €)` })
            ),

            //NOTA: Esta lista no es dinámica, no tiene "$" delante y no se actualizará. TO-DO FRAMEWORK FRONTEND: admitir multiples hijos para contenedores que usan listas NO DINAMICAS. 
            h('div', {class:'extras-container', innerText: 'Extras: ', style:'font-weight:bold'},            
                () => {
                    if (props.extras && props.extras.length > 0){
                        return props.extras.map(extra => 
                            h('div', null, [
                                h('span', { class: 'extra-name', innerText: `• ${extra.name}`}),
                                h('span', { class: 'extra-price', innerText: ` (+${extra.price.toFixed(2)} €)` })
                            ])
                        )
                    }
                    else{
                        return h('span', {innerText: props.noExtrasText})
                    }
                }
            ),                      
            h('div', {class:'total-price', innerText: `${props.priceText} - ${props.price.toFixed(2)}€`}),
        )        
    )
}

export default Summary; 


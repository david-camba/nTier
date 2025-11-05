import { h, initComponent } from '../../lifetree.js';

function SelectionCard(props){
    const innerPropsKeys = ['name', 'image', 'price', '$isSelected', 'additionalProps'];

    const setProp = initComponent(props, innerPropsKeys, "SelectionCard");

    return  h('div', 
                { 
                    class: () => {
                        if(props.$isSelected) return 'selection-card selected'
                        else{return 'selection-card'}
                    }
                    ,updateAnimation: {
                        from:{     
                            opacity: 0, 
                            transition: 'opacity 0.8s ease'
                        },
                        to:{
                            opacity: 1, 
                            transition: 'opacity 0.8s ease'
                        }
                    } 
                }, //la animación ya está controlada por el CSS, fijamos la default a nada
                [
                    // Hijos de cada tarjeta
                    h('img', { src: props.image, alt: props.name }),
                    h('h3', { innerText: props.name }),
                    h('p', { innerText: `${props.additionalProps.priceText}${props.price.toFixed(2)} €` })
                ]
            )
}

export default SelectionCard; 


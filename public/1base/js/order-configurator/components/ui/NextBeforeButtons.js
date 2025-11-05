import { h, initComponent } from '../../lifetree.js';

function NextBeforeButtons(props){
    const innerPropsKeys = ['$step', '$allowedSteps', 'additionalProps'];

    const setProp = initComponent(props, innerPropsKeys, "NextBeforeButtons");

    return h('div', {class:'step-actions'}, 
        h('button', {
            style: () => (props.$step <= 1 ? 'display:none' : null),
            onclick: () => {if(props.$allowedSteps?.[props.$step-1]) setProp('$step', props.$step-1)}, 
            innerText: `${props.additionalProps.textBefore}`,
        }),
        h('button', {
            onclick: () => {
                if(props.$allowedSteps?.[props.$step+1] //si el siguiente paso está permitido, avanzamos
                    || props.$step === props.$allowedSteps?.length-1) //si estamos en el ultimo paso, permitimos terminar el pedido
                    setProp('$step', props.$step+1)
            }, 
            innerText: () => (props.$step >= props.$allowedSteps?.length-1) ? `${props.additionalProps.textAssignClient} (LEGACY INTEGRATION)` : `${props.additionalProps.textAfter}`,
            setAttr: () => { 
                if((props.$step === props.$allowedSteps?.length-1) || props.$allowedSteps?.[props.$step+1]) 
                    return {disabled:false}
                return {disabled:true}
            },
        }),
    );
}

export default NextBeforeButtons; 


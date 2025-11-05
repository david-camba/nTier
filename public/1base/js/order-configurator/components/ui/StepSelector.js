import { h, initComponent } from '../../lifetree.js';

function StepSelector(props){
    const innerPropsKeys = ['$step', '$allowedSteps', 'additionalProps'];

    const setProp = initComponent(props, innerPropsKeys, "StepSelector");

    return h('div', {class: 'step-indicator'}, 
        props.additionalProps.stepsData.map( (step,index) => { 
            return h('div', 
                {   
                    innerText: step.name, 
                    class: () => 
                        props.$step === index+1 
                            ? 'step active' 
                            : !props.$allowedSteps?.[index+1] ? 'step disabled' : 'step',
                    onclick: () => {
                        if (props.$allowedSteps?.[index+1] && index+1 !== props.$step) 
                            setProp('$step', index+1)
                    },
                    setAttr: () => { 
                        if(props.$allowedSteps?.[index+1]) return {disabled:false}
                        return {disabled:true}
                    },
                }
            )                                
        })        
    );
    /*
    //h('p', { innerText: `${props.additionalProps.texto}` }),
        h('button', {
            style: () => (props.$step <= 1 ? 'display:none' : null),
            onclick: () => {if(props.$allowedSteps?.[props.$step-1]) setProp('$step', props.$step-1)}, 
            innerText: `ANTERIOR`,
        }),
        h('button', {
            onclick: () => {
                if(props.$allowedSteps?.[props.$step+1] //si el siguiente paso está permitido, avanzamos
                    || props.$step === props.$allowedSteps?.length-1) //si estamos en el ultimo paso, permitimos terminar el pedido
                    setProp('$step', props.$step+1)
            }, 
            innerText: () => (props.$step >= props.$allowedSteps?.length-1) ? `CREAR PEDIDO` : `SIGUIENTE`,
            setAttr: () => { 
                if((props.$step === props.$allowedSteps?.length-1) || props.$allowedSteps?.[props.$step+1]) return {disabled:false}
                return {disabled:true}
            },
        }),
    */
}

export default StepSelector; 


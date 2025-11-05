import { h, initComponent } from '../../lifetree.js';

function StepValidator(props){
    const innerPropsKeys = ['$step', '$allowedSteps', '$selectedModel', '$saveModel','$selectedColor', 'childComponent','childProps'];

    const setProp = initComponent(props, innerPropsKeys, "StepValidator");

    props.beforeUpdate = (vNode, changesToDo, prevProps) => {
        let allowedStepsUpdate = {};

        // Si desde fuera nos indican que cambiemos, no recalculamos los steps permitidos
        if(!changesToDo?.$allowedSteps) allowedStepsUpdate = checkConditionsForAllowedSteps(changesToDo);
        // Por qué? Esto permite que el estado inicial (o el estado en un determinado momento), se pueda fijar desde fuera y evitar inconsistencias. Por ejemplo, en nuestro caso, App fijará el estado inicial con los datos recogidos del servidor, y desde ahí, nuestro "StepValidator" cogerá el mando

        return {...allowedStepsUpdate};
    }   

    function checkConditionsForAllowedSteps(changesToDo){
        if(changesToDo.$saveModel){
            return setProp('$allowedSteps', [false, true, true, false, false]); //Si se cambia de modelo, abrimos el siguiente paso y cerramos el resto. Cambiar de modelo resetea la configuración.
        }

        // Detectamos si se acaba de setear el modelo o color y antes no estaba seteado
        const modelStepCompletedNow = (changesToDo.$selectedModel != null && !props.$selectedModel) // Si se acaba de seleccionar un modelo y antes no habia
        const modelStepRemovedNow = (changesToDo.$selectedModel === null); // Si se está dejando a null la selección de modelo.

        const colorStepCompletedNow = (changesToDo.$selectedColor != null && !props.$selectedColor);
        const colorStepRemovedNow = (changesToDo.$selectedColor === null); // Si se está dejando a null la selección de color.

        if(!modelStepCompletedNow && !colorStepCompletedNow && !modelStepRemovedNow && !colorStepRemovedNow) 
            return {}; //Si no hay cambios devolvemos un objeto vacio: no hay nada que setear

        //Mapa de reglas - si la condición se cumple, se permite los pasos indicados
        // IMPORTANTE: el "toggleSteps" NUNCA debe incluir el propio paso, si no podríamos dejar cerrado para siempre el propio paso//Mapa de reglas - si la condición se cumple, se permite los pasos indicados
        const stepRules = [
            { allowCondition: modelStepCompletedNow, denyCondition: modelStepRemovedNow, toggleSteps: [2] }, //si el color se cumple, 
            { allowCondition: colorStepCompletedNow, denyCondition: colorStepRemovedNow, toggleSteps: [3,4] }, //si tenemos el color seteado, permitimos avanzar hasta el final porque los extras no son obligatorios
        ];

        const newAllowedSteps = [...props.$allowedSteps]; //Hacemos una copia para modificar sobre el estado actual.
        //Importante: debemos hacer una copia, sino, estaríamos modificando directamente las props y eso en el framework está totalmente prohibido: solo se puede modificar con setProp() o con las funciones de hacer target a un componente dentro de un slot (como hace nuestro StepController)

        //Comprobamos si se cumple la condición y seteamos los pasos indicados en nuestro mapa
        stepRules.forEach(rule => {
            //Si la regla tiene pasos a permitir/denegar
            if (rule.toggleSteps) {
                if (rule.allowCondition === true) rule.toggleSteps.forEach(i => newAllowedSteps[i] = true);                    
                else if(rule.denyCondition === true) rule.toggleSteps.forEach(i => newAllowedSteps[i] = false);
            }
        });

        return setProp('$allowedSteps', newAllowedSteps); //seteamos y devolvemos el objeto que nos devuelve setProp()
    } 

    return h(props.childComponent, 
    {   defMap: {
            $step: props.defMap.$step, 
            $allowedSteps: props.defMap.$allowedSteps
        },
        additionalProps: {...props.childProps} //nos permitirá a la vista de selección hija textos o formatos de forma desacoplada
    });
}

export default StepValidator; 


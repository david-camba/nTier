import { h, initComponent } from '../../lifetree.js';

function SmartPriceDisplay(props){
    const innerPropsKeys = ['$selectedModel', '$saveModel', '$selectedColor', '$selectedExtras', '$totalPrice'];

    /*  TO-DO FRAMEWORK para desacoplar este componente
        Después de:
            1- Permitir que innerPropKeys, en vez de un array de strings, sea un objeto con keys y valores que permita fijar valores por defecto. ej: 
                innerPropsKeys = {$totalPrice: 0.0, ...}

                Nota: incluso se podría indicar con alguna refenrencia de "Aquí necesito que me pases una prop del estado global", lo que incluso podría hacer innecesario un "defMap" explícito porque iría directamente inyectado aquí. 
                
                Podría ser algo totalmente explicito para inyectarlo desde fuera como:
                    h(SmartPriceDisplay, {$totalPrice: { default: 0.0, from: '$totalPrice' }},

                O una convención en la definición, que al detectar que el nombre empieza por "$" (dinámico) (o por "$$" => dinámico global), ya directamente al hacer initComponent buscara que nos han pasado un string apuntando a un estado global y comprobando que esa propiedad exista. Ejemplo:

                    const innerPropsKeys = {$$price: Number} 
                        //para expresar el tipo y comprobarlo, o podría evolucionar a TypeScript
                
                Desde fuera:
                    h(SmartPriceDisplay, {$$price: '$totalPrice'}, //luego al hacer initComponent se comprobaría que esta key existe, y sino, error.

                *Nota: esta funcionalidad no es compleja de implementar, sería realizar modificaciones en initComponent, al principio en la fase de comprobación de props. Esta funcionalidad tiene prioridad número 1 por facilidad y mejora.
                

            2- Conseguir que el defMap pueda solicitar subniveles de los objetos en vez de objetos enteros, para que un componente no tenga que llevarse una prop del estado entera sino que pueda solicitar solo subniveles:

                SE INYECTARIA EN EL COMPONENTE:
                {defMap: {$selectedModel : '$currentSess.$selectedModel' } }

                EL ESTADO SERIA: 
                state= {
                    $currentSess{ 
                        $selectedModel: { $data,  $saveModel: false} }
                        $selectedExtras: ...                
                }
                Y que cada componente solicite y actualice SOLO aquella parte que de $initSession o sus subniveles que necesite, no todo el objeto. 
                *NOTA: en el main_configurator.js se explica más de este implementación. No es trivial y requiere tocar varias partes del flujo: initComponent, setProp, updateDom, handleSlot y posiblemente el proxy.
            
            3- El siguiente paso natural es crear props de entrada variables (en el componente "StepValidator" ya se deja intuir esta estructura con "childProps", que permite pasarle unos valores fijos a la vista que le pasemos)

            Esto debe ampliarse para que no sean solo valores fijo variables, sino que por ejemplo hubiera un:
                $requestedGlobalProps: ['$selectedModel', '$saveModel', '$selectedColor', '$selectedExtras']

            Para no obligar a los componentes a definir todo lo que necesitan, sino dar la posibilidad de ser flexibles en este punto. Esto permitiría a SmartPriceDisplay recibir:
                1- Una función "doEarlyPriceUpdate" que compruebe la lógica para actualizar el precio
                2- Las propiedades que necesita esa función para funcionar

                *Luego se invocaría en el hook "beforeUpdate" de forma desacoplada como:                    
                    const updatedPrice = props.doEarlyPriceUpdate(props.$requestedGlobalProps);
                    return updatedPrice;
                
                Así podríamos definir fuera del componente toda la lógica de comprobación y pasarle exactamente las funciones que necesitara. Esto permitiría crear componentes totalmente desacoplados a los que se les inyectaría las funciones y las props que necesitaran esas funciones desde fuera.
    */

    const setProp = initComponent(props, innerPropsKeys, "SmartPriceDisplay");

    props.beforeUpdate = (vNode, changesToDo, prevProps) => {
        //Si se realizo algún cambio en modelo, color, extra, lo reflejamos sin actualizar el servidor. La sesión (exceptuando con los extras que es instantaneo), solo se guardará al hacer click en siguiente, pero visualmente se actualizará todo al instante al realizarse un cambio en un solo loop de updateDOM.

        // FRAMEWORK FRONTEND DONE: los setProp() aquí lanzados, no se van a aplicar en este ciclo de actualización, sino en el siguiente. Debería este hook modificar directamentes las props a actualizar para que pudieran actualizarse en este ciclo? Exigiría devolver los cambios para ser añadidos, y mantener el "setProp" para actualizar las propiedades globales igualmente. 
        // DONE: Se hace devolviendo los cambios a aplicar. Se ha mejorado setProp para que devuelva lo que el updateDOM necesita en este punto para aplicar los cambios en el mismo loop sin generar inconsistencias. En el siguiente loop, si algún otro componente externo (no hijo, estos se actualizarán en el mismo loop) se ve afectado por los cambios de esta actualizaicón, en el siguiente loop se actualizarán correctamente. 
        // IMPORTANTE: nunca se debe modificar un target en este hook.

        //Nuestra función validará el momento actual para decidir que pasos están permitidos y actualizarlo en este mismo loop del updateDOM. Si ya viene una actualización de cambios definida, no hacemos la comprobación.        

        //Si viene una actualizaicón directa en el precio, salimos. Ya se va a actualizar y siempre confiamos en que los valores seteados desde fuera son los correctos
        //Tambien salimos si no tenemos un totalPrice fijado: al iniciar la app, el servidor devolverá totalPrice con al cálculo del precio y los items que forman ese precio. A partir de ahí podemos calcular antes que el servidor viendo las cosas nuevas que añade el cliente. Pero al principio siempre esperamos a que el servidor nos mande el precio inicial para hacer los calculos de forma consistente.


        //TO-DO: refactorizar toda esta lógica a una función auxliar. Por motivos de Demo del hook "beforeUpdate", lo mantenemos aquí para que se vea más claro el uso de setProp y el tipo de return a realizar
        if(changesToDo.$totalPrice || typeof props.$totalPrice !== 'number' || isNaN(props.$totalPrice)){ 
            return {}; //buena práctica: devolvemos al updateDOM "el hook no necesita updatear nada"        
        }

        let priceUpdate = {};

        //Si hay cambio de modelo pendiente por guardar, solo se fijará el precio del modelo porque resetea toda la configuración      
        if(changesToDo.$saveModel || props.$saveModel){
            const currentModelPrice = (changesToDo.$selectedModel?.price) //si hay cambios en el precio, fijamos ese precio
                ? changesToDo.$selectedModel.price //sino, fijamos el precio que se va a guardar
                : props.$selectedModel.price
            //Si el precio existe y es diferente el precio total actual, actualizamos
            if(currentModelPrice && props.$totalPrice !== currentModelPrice) 
                priceUpdate = setProp('$totalPrice', currentModelPrice);              
            return {...priceUpdate};         
        }

        //Precio sin actualizar
        let modelPrice = props.$selectedModel?.price ?? 0.0;
        let colorPrice = props.$selectedColor?.price ?? 0.0;        
        let extrasPrice = props.$selectedExtras?.reduce((sum, extra) => sum + (extra.price ?? 0), 0.0);

        //Verificamos si ha cambiado el precio del color
        if(typeof changesToDo?.$selectedColor?.price === 'number') colorPrice = changesToDo.$selectedColor.price;
        
        //Verificamos si ha cambiado el precio de los extras
        if(typeof changesToDo?.$selectedExtras?.[0]?.price === 'number') 
            extrasPrice = changesToDo.$selectedExtras?.reduce((sum, extra) => sum + (extra.price ?? 0), 0.0);

        //Si hay cambio de precio, seteamos el precio de todo y devolvemos para que los cambios se ejecuten al instante. NOTA: se ha mejorado el framework para que haga esta comprobación por su cuenta     
        const calculatedTotal = modelPrice + colorPrice + extrasPrice;
        if(props.$totalPrice !== calculatedTotal) priceUpdate = setProp('$totalPrice', calculatedTotal);

        return {...priceUpdate, /*...otherWantedChange*/}; //IMPORTANTE: devolvemos el objeto que nos devuelve setProp para actualizar los cambios en esta iteración. Si no se lo pasamos, si este componente tiene hijos que dependen de estas props actualizadas no se actualizarán correctamente y su estado podría quedar corrupto.

        //TO-DO FRAMEWORK: añadir un warning o una comprobación de cuantos setProps se han hecho durante esta fase para ver si concuerdan con el número de keys devueltas.
    }

    return h('div', {class: 'price-container'},
        h('div', {class:'price-label', innerText: props.title}),
        h('div', {class: 'price-display'},
            h('span', {class:'amount', innerText: () => props.$totalPrice.toFixed(2)}),
            h('span', {class:'currency', innerText:'€'}),
        )
    );
}

export default SmartPriceDisplay; 


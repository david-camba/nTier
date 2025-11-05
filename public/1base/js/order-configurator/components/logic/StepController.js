import { h, initComponent, addIdKey, Empty} from '../../lifetree.js';
import SingleItemSelector from '../forms/SingleItemSelector.js';
import SelectionCard from '../ui/SelectionCard.js';
import MultiPicker from '../forms/MultiPicker.js';
import Summary from '../ui/Summary.js';

/* 
Este el cerebro de la aplicación. Renderiza el paso actual y maneja todas las interacciones con el backend.

Se ha hecho lo más eficiente posible sin perder robustez: solo nos comunicamos con el backend cuando es estrictamente necesario, reduciendo las llamadas al mínimo. Ejemplos:
• Se guardan los modelos y colores recibidos por si se avanza adelante y atrás
• al seleccionar un modelo no se llama automáticamente al backend sino que se espera a que se le de a "siguiente", 
• los extras se guardan 1 a 1 para la conveniencia del usuario
...etc


IMPORTANTE

Está TREMENDAMENTE acoplado al backend, por que el backend funciona de la siguiente manera, da dos opcioens:
1- 
a) Le mandas los datos para guardar un paso 
b) y le pides que te mande los datos para renderizar el siguiente. 
Ej: mandas el modelo de coche seleccionado y te devuelve los colores asociados a ese coche.

2- Le pides los datos del paso "X" de tu sesión activa. 
Ej: Detecta el modelo que tienes guardado en la DB, y te lo manda.

En cuanto a seguridad es robusto: hay validación de usuario y sesión de configurador.

Pero, en cuanto a endpoints es tremendamente rígido. 

Lo creé en su momento simplemente para tener algo en lo que mostrar la arquitectura Modelo-Servicio-Controlador-Vista, y cuando lo implementé con mi framework DECIDI NO TOCAR EL BACKEND como ejercicio práctico y prueba de fuego para demostrar la flexibilidad del framework. Conseguir un frontend lo más desacoplado posible sobre un backend muy rígido.

TO-DO: evolucionar el backend para que cuente 2 endpoints.

1- GUARDAR TODO Y DEVOLVER EL PASO QUE SE LE PIDA
    • El EndPoint recibe TODOS los datos de la sesión (coche, color, extras), los válida y si todo está bien los guarda.
    • Admite un "&step=2", para devolver los datos de seleción de la sesión asociados al step que se le pida.

2- OBTENER UN PASO CONCRETO
    • Le mandas la sesión, y devuelve el último paso asociado a esa sesión

*/

function StepController(props){
    const innerPropsKeys = 
    [   'target_StepComponent', '$stepOrder',
        '$sessionId' ,'$step', '$allowedSteps',
        '$models', '$selectedModel', '$saveModel',
        '$colors', '$selectedColor', '$saveColor',
        '$extras', '$selectedExtras', '$saveExtras',
        '$totalPrice', 'translations',
    ];

    const setProp = initComponent(props, innerPropsKeys, "StepController");

    // Mapa para encontrar fácilmente la función asociada a cada paso
    const stepFuncMap = {
        'models': showModels,
        'colors': showColors,
        'extras': showExtras,
        'summary': showSummary,
    }

    props.afterUpdate = async (vNode, propsUpdated, prevProps) => {   
        if(propsUpdated.$step && props.$stepOrder){
            if(propsUpdated.$step === props.$stepOrder.length){
                // Si nos hemos pasado del ultimo paso, es que se ha creado un pedido. 
                // Terminamos y mandamos al código legacy para asignar el pedido
                window.location.href = `/app/clients.php?conf_session=${props.$sessionId}`; 
                return;
            }
            //Si el paso ha sido actualizado por algun "StepValidator", ejecutamos la función relativa a esa paso
            const stepName = props.$stepOrder[propsUpdated.$step];
            stepFuncMap[stepName]();
        } 
            
        //Si después de actualizar el componente, hay cambios para hacer, los gestiona
        if(props.$saveExtras) {
            // Caso MultiPicker - Guardar automáticamente los cambios de extras al seleccionar
            // NOTA: El guardado de modelo y color se realiza al hacer click en "Siguiente" si hubo cambios, pero en el caso de los extras los guardaremos automáticamente en cuanto se seleccione uno, para que la experiencia sea más fluida y "user-friendly" al ver la actualización del precio directamente.
            let updatedExtras = propsUpdated.$selectedExtras;
            if (updatedExtras && updatedExtras !== prevProps.$selectedExtras){
                //Si los extras estan vacios y han cambiado, le pedimos al backend que los limpie
                //Si no, le pasamos el array de idKeys para que guarde el estado actual
                updatedExtras = (updatedExtras.length === 0) ? ['cleanExtras'] : updatedExtras.map(extra => extra.idKey);

                const response = await fetch(`/api/configurator/session/${props.$sessionId}/extras?include=next-step`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ extraIds: updatedExtras })
                });
                if (!response.ok) throw new Error('Error del servidor al guardar los extras.');
             
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                console.log("Extras guardados correctamente");

                const price = result.data.priceDetails.total;  
                const selectedExtras = addIdKey(result.data.priceDetails.extras, 'id_extra'); //le añadimos la idKey para que sea detectable en nuestra lista (convencion framework frontend)        
                
                if(props.$totalPrice !== price) setProp('$totalPrice', price); //si el precio es diferente, actualizamos. Por qué podría ser igual? Porque nuestro "smartPriceDisplay" lo actualiza al detectar que se añade o quita un extra, sin esperar respuesta del servidor.
                
                setProp('$selectedExtras', selectedExtras) //no es necesario actualizar: los datos que nos devuelve el servidor de los extras guardados son exactamente los que hemos enviado. Lo mantenemos por consistencia, por si quizás se aplique una lógica de negocio en el backend que permita filtrados, correcciones, actualizaciones un extra por su versión moderna... Con la lógica actual, generá un loop de updateDOM extra sin ningún beneficio, pero aporta consistencia.

                setProp('$saveExtras', false);                               
            }
        }
    };

    async function showModels(){
        let models = [];
        //si ya he pedido los modelos, utilizo eso

        if(props?.$models?.length > 0 ) models = props.$models?.idKey; //sino, los pido     
        else{
            const response = await fetch('/api/configurator/models');
            if (!response.ok) throw new Error('Error del servidor al cargar modelos.');
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            models = result.data.models;
            setProp('$models', models);
        }

        const target = props.targetStepComponent();
        target.type = SingleItemSelector;
        target.props = {
            ItemViewComponent: SelectionCard,
            childrenProps: {priceText: props.translations.model_card_price_prefix},
            title: props.translations.model_picker_title, 
            filterBy: false, 
            defMap: { 
                $selected: '$selectedModel', $itemList: '$loadedModels', $saveChanges: '$saveModel'},                 
                /*idKey: "current_step"*/
            }; //DONE: hacer que no sea necesario poner el idKey current_step cuando se hace esta sustitución. El renderSlotChild ahora se ocupará de esto.
    }

    async function showColors(){ 
        let currentModelColors = props.$colors.filter(
            color => color.id_model === props.$selectedModel?.idKey
        );
        //si ya he pedido los modelos, utilizo eso    

        // Si no tenemos colores para el modelo o se quiere guardar un nuevo modelo, pedimos los colores.
        // Sino, se usaran los colores ya guardados (es por si se da adelante y atrás sin realizar cambios)
        if(currentModelColors.length === 0 || props.$saveModel) {
            const {colors, price, modelData} = await saveModelAndGetColors(props.$selectedModel);//se encargará de guardar o no guardar en función del flag "$saveChanges"
           
            if (currentModelColors.length === 0)
                setProp('$colors', [...colors, ...props.$colors]); //si he vuelto atrás y he cambiado de modelo, no elimino los colores que tenía, añado más. Si ya tenía colores, significa que el servidor ya me lo habías devuelto y no hace falta actualizar el estado

            if(props.$totalPrice !== price) setProp('$totalPrice', price);            
            if(props.$saveModel) setProp('$saveModel', false); //Si se necesitaba, ya se ha guardado, cambiamos el flag           
            setProp('$selectedModel', modelData); //guardamos el modelo selecionado     
        }
        
        const target = props.targetStepComponent();
        target.type = SingleItemSelector;
        target.props = {
            title: props.translations.step2_title, 
            ItemViewComponent: SelectionCard,
            childrenProps: {priceText: '+'},            
            $itemList: props.$colors, 
            filterBy: {field: 'id_model', value: props.$selectedModel.id_model}, 
            defMap: { $selected: '$selectedColor', $saveChanges: '$saveColor'},             
        }
    }

    async function saveModelAndGetColors(model){
        const modelId = model.idKey ?? model;
        if(!modelId) return console.warn("Model debe ser un id o un objeto con idKey", model);

        let response;

        if(!props.$saveModel) {
            response = await fetch(`/api/configurator/session/${props.$sessionId}/colors`);
        }
        else{
            response = await fetch(`/api/configurator/session/${props.$sessionId}/model`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ modelId: modelId })
            });
        }

        if (!response.ok) throw new Error('Error del servidor al cargar modelos.');

        if(props.$saveModel){
            //Si hemos guardado un nuevo modelo tenemos que resetear el estado
            if(props.$selectedColor !== null) setProp('$selectedColor', null);
            if(props.$selectedExtras.length > 0) setProp('$selectedExtras', []);
            setProp('$extras', []); //También fijamos los extras a vacio. 
            // {id_extra: 23, name: 'Suspensión neumática adaptativa', description: 'Ajuste electrónico de la altura y dureza de la suspensión.', price: 2100, models: '1,2'}
            // Por qué es mejor no acumularlo? Porque el filtro que debemos hacer en el frontend es demasiado si se acumulan muchos extras. Hay que extraer "models", de ahí separar el string y ver si para el modelo escogido funciona. Es mejor hacer una nueva petición al servidor si se cambia de modelo para tener solo los extras que se necesitan sin necesidad de filtrar en el frontend.
        }

        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        const colors = result.data.colors;
        const totalPrice = result.data.priceDetails.total;
        const modelSelected = result.data.priceDetails.model;

        // Como la API del backend devuelve los colors con keys diferentes, necesitamos mapearlo para que funcionen con nuestro componente "SingleItemSelector" y no haya que crear un nuevo componente.
        
        // TO-DO: mantener la consistencia en las respuestas en los diferentes endpoints del backend para que no haya que datos en cliente.
        const transformedModel = {...modelSelected, idKey: modelSelected.id_model};
        const transformedColors = colors.map(color => ({
            ...color, 
            image: color.img,
            idKey: color.id_color,
            price: color.price_increase
        }));        

        return {colors: transformedColors, price: totalPrice, modelData: transformedModel};
    }

    async function showExtras() {
        let extras = [];

        if(props?.$extras?.length > 0 && !props.$saveColor) extras = props.$extras;   
        
        //Si no tenemos extras para el modelo, los pedimos. 
        if(extras.length === 0) {
            const {extras, price, selectedExtras, selectedModel, selectedColor} = await saveColorAndGetExtras(props.$selectedColor);//se encargará de guardar o no guardar en función del flag "$saveColor"

            setProp('$extras', extras); //si he vuelto atrás y he cambiado de modelo, no lo elimino, lo guardo en memoria 
            setProp('$saveColor', false); //Si se necesitaba, ya se ha guardado, cambiamos el flag  
            setProp('$totalPrice', price);  
            setProp('$selectedExtras', selectedExtras);  
            setProp('$selectedModel', selectedModel);
            setProp('$selectedColor', selectedColor);     
        }
        
        const target = props.targetStepComponent();
        target.type = MultiPicker;
        target.props = {
            title: props.translations.extras_picker_title, 
            filterBy: null, 
            defMap: { 
                $pickedList: '$selectedExtras', 
                $itemList: '$loadedExtras', 
                $saveChanges: '$saveExtras'
            }
        }
    }

    async function saveColorAndGetExtras(colorData, onlySave = false){
        const colorId = colorData.idKey;
        let response;

        if(!props.$saveColor) {
            response = await fetch(`/api/configurator/session/${props.$sessionId}/extras`);        
        }
        else{
            response = await fetch(`/api/configurator/session/${props.$sessionId}/colors?include=next-step`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ colorId: colorId })
            });
        }

        if (!response.ok) throw new Error('Error del servidor al cargar modelos.');
        if (onlySave) return true; //si solo queríamos guardar, aquí nos vamos

        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        const extras = result.data.extras;
        const totalPrice = result.data.priceDetails.total;
        const selectedModel = addIdKey(result.data.priceDetails.model, 'id_model');
        const selectedColor = addIdKey(result.data.priceDetails.color, 'id_color');
        const selectedExtras = addIdKey(result.data.priceDetails.extras, 'id_extra');

        const transformedExtras = extras.map(extra => ({
            idKey: extra.id_extra,
            name: extra.name,
            description: extra.description,
            price: extra.price,
        }));

        return {extras: transformedExtras, price: totalPrice, selectedModel: selectedModel, selectedColor: selectedColor, selectedExtras: selectedExtras};
    }

    async function showSummary(){
        let response;

        //Vemos si el color esta pendiente de guardar. Podríamos estar realizando un salto desde "elegir color" sin haberlo guardado en el servidor.
        //TO-DO BACKEND: muy importante - debería haber un endpoint que permitiera guardar de golpe toda la sesión (no una API para modelo, otra para color y otra para extras) y solicitar el paso deseado. Eso permitiría desacoplar y haría la lógica del frontend mucho más sencilla y escalable, además de simplificar el backend enormemente. Ahora la rigidez del endpoint nos perjudica.
        if(props.$saveColor){
            await saveColorAndGetExtras(props.$selectedColor, true);
            setProp('$saveColor', false);
        }
        
        //Si no hay extras seleccionados para guardar, mando un vacio para indicarle al backend que guarde en extras un string vacio que representa: "nada seleccionado". Si se reinicia el configurador, directamente se empezará en este paso. 
        // SUBOPTIMO: si se vuelva adelante y atrás más de una vez sin añadir extras, se harás repetidas llamadas al backend para que guarde lo mismo "no hay extras", pero para evitar este raro caso habría que complicar la lógica solo por evitar una llamada. Lo dejamos así. 
        if (props.$selectedExtras?.length === 0) { 
            response = await fetch(`/api/configurator/session/${props.$sessionId}/extras?include=next-step`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ extraIds: [] })
            });
        }
        else{response = await fetch(`/api/configurator/session/${props.$sessionId}/summary`);}

        if (!response.ok) throw new Error('Error del servidor.');
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        const selectedModel = addIdKey(result.data.priceDetails.model, 'id_model');
        const selectedColor = addIdKey(result.data.priceDetails.color, 'id_color');
        const selectedExtras = addIdKey(result.data.priceDetails.extras, 'id_extra');
        const totalPrice = result.data.priceDetails.total;

        setProp('$selectedModel',selectedModel);
        setProp('$selectedColor',selectedColor);
        setProp('$selectedExtras',selectedExtras);
        setProp('$totalPrice',totalPrice);

        const target = props.targetStepComponent();
        target.type = Summary;
        target.props = {
            model: selectedModel, color: selectedColor, extras: selectedExtras, price: totalPrice,
            title: props.translations.summary_title, 
            priceText: props.translations.summary_final_price, 
            noExtrasText: props.translations.summary_no_extras
        };
    }

    //USADA PARA DEBUG - resetea el paso inicial
    async function reset() {
        const response = await fetch(`/api/configurator/session/${props.$sessionId}/reset-to-step1`, {
        method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
        });

        if (!response.ok) throw new Error('Error del servidor.');
    
        const result = await response.json();
        if (!result.success) throw new Error(result.message);        
    }

    return h(Empty); //Devolvemos el componente vacio del framework, StepController no necesita vista

    //return h('button', {onclick: () => reset(), innerText: `RESET`});
}

export default StepController; 
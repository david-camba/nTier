// 0- Importamos el framework 
// Obligatorios: h() para definir App y plant() para lanzarla
//      NOTA: Si todos los componentes se ubican en slots no es necesario porque el arból detectará las propiedades por "idKey".
//      IMPORTANTE: Y, si tenemos UN solo componente de cada tipo en la raiz y no comparten propiedades (no usan internamente los dos "$count", por ejemplo), no es obligatorio tampoco para el correcto funcionamiento pero es altamente recomendado por posibles extensiones futuras de la App. 
import { h, plant, Empty, initComponent, createDomNode, setAllPropsAsDefMap} from './lifetree.js';

import StepController from './components/logic/StepController.js'; //gestiona las peticiones al servidor y renderiza el paso actual

import StepValidator from './components/logic/StepValidator.js'; //valida los pasos disponibles en función del estado y permite implementar diferentes vistas para seleccionar el paso

import NextBeforeButtons from './components/ui/NextBeforeButtons.js'; //avanzar-retrodecer en los pasos.
import StepSelector from './components/ui/StepSelector.js'; //elige al paso que quieres ir

import SmartPriceDisplay from './components/ui/SmartPriceDisplay.js'; //actualiza el precio sin necesidad de recibir respuesta del servidor. TO-DO: actualizar el framework para permitir que la lógica de actualización y las props necesarias para realizar esta actualización venga desde fuera

import ConfirmActionGateway from './components/ui/ConfirmActionGateway.js'; //ui con "aceptar" y "cancelar" que resuelve una promesa en función de la respuesta del usuario. (lo usamos para parar el flujo)

// 1- DEFINIR NUESTRA APP
let $stepSlot = [
        {
            type: Empty,
            props: {},
            idKey: 'current_step',
        },
    ]

//Fijamos el estado: las propiedades iniciales y los objetos que van a dar estructura a nuestros slots
const appState = {
    $stepSlot, //En este caso, nuestro paso actual estará representado en un solo componente en el slot. Podríamos poner más dentro del slot, pero el resto de componentes solo se actualizarán. El del slot cambiará completamente
    $stepOrder : [],
    $currentStep : 0, //el paso en el que estamos,
    $allowedSteps: null, //se fijaran los pasos permitidos
    
    $loadedModels : [], //array de objetos, será una lista para mostrar todos los modelos disponibles
    $loadedColors : [],
    $loadedExtras : [],

    $selectedModel : null, //id 
    $selectedColor : null, //id
    $selectedExtras : [], //array de ids

    $sessionId : null,

    //flag para detectar si hay cambios que guardar, para evitar hacer llamadas API de guardar si nada ha cambio
    $saveModel: false,
    $saveColor : false, 
    $saveExtras : false,

    $totalPrice : 0.0,

    $translations: {},

    /*
    TO-DO FRAMEWORK: los objetos de estado a trackear por cada arbol debería poder estar aninados en sub-niveles. 
    Es decir, poder armar:
    $dataLoaded: {
        $loadedModels:[],
        $loadedColors : [], 
        $loadedExtras : [],     
    }
    Y poder pasarle a un objeto en su defMap "$dataLoaded.$loadedExtras", para que solamente escuche y pueda modificar ese subnivel. Es una modificación que toca varias capas: initComponent-SetProp, updateDOM y el proxy makeReactive. Probablemente la mejor solución sería poder pasar el anidamiento de forma descriptiva como "$dataLoaded.$loadedExtras.$mainExtra", y que al hacer initComponent eso creará del estilo:
        $dataLoaded = {
            "nestedGlobalProps":{    
                '$loadedExtras': {
                    '$mainExtra' : {value: 'Techo descapotable'}, 
                    '$secondaryExtra' : {value: 'GPS'}, 
                }
            }            
        }

    Este anidamiento facilitaría enormemente la búsqueda en "updateDOM" y "setProp" para actualizar el componente o setear propiedades internas. Por qué es necesario? Porque necesitamos ver el cambio desde el primer nivel, luego ya se verá si eso se ha actualizado o no, pero es imprescindible verlo desde el primer nivel porque los objetos enteros van a seguir viajando por el updateDOM si ha habido un cambio. Así, si se tiene la key en el componente al hacer el updateDOM, al detectar que hay "nestedGlobalProps", se recorrerían las keys del objeto para ver si alguna coincide, si coincidiera, se miraría si existe "value", sino existe, se seguiría recorriendo comprobando claves de ese nuevo subnivel. Cuando se encontraría value, se compararía, y si se ha actualizado, se setearía. 

    Además, al estar estos valores contenidos en la referencia de la global prop, seguiría siendo totalmente accesible la prop haciendo "prop.internalProp", sin ninguna complicación, este cambio sería completamente invisible para el desarrollador.
    */

};

// Definimos nuestra aplicación: qué componentes, slots y listas dinámicas vamos a tener
function App(props) {
    const innerPropsKeys = setAllPropsAsDefMap(props); //creamos el defMap con TODAS las props recibidas para que App puede setear cualquier propiedad recibida del estado global y las ponemos como nuestras innerPropsKeys

    // Este hook se lanzara durante la inicialización del componente, antes de crear el arbol virtual. 
    // Nos permite trabajar con las props antes de que el componente se haya creado
    props.beforeInitComponent = (setProp) => {
        //Sacamos las traducciones que el servidor nos ha pasado al cargar la página
        const translationsElement = document.getElementById('configurator-translations');
        const translations = JSON.parse(translationsElement.textContent);
        setProp('$translations', translations); //seteamos la propiedad
    }

    const setProp = initComponent(props, innerPropsKeys, "App-StepsConfigurator"); //lo inicializamos para tener acceso a "setProp"    

    props.style = 'position:relative'; //para que sirva como contenedor global de todos sus componentes hijos

    props.defMap.$step = '$currentStep'; //fijamos en el defMap manualmente el nombre de la key que contiene el step del sistema. NOTA: se ha hecho así por propósitos de demostración en como enlazar las props de otros componentes con keys de otro nombre, lo ideal aquí sería llamarlo igual
    
    props.onMount = (vNode) => {
        setInitialState();   //al lanzar la app, hacemos una llamada API al servidor para que nos devuelva el estado actual de la sesión     
    };

    /**
     * Determina en qué paso del configurador estamos basándose en la sesión activa.
     * NOTA: podríamos mover esta lógica al backend para que directamente el backend calculara y nos dijera directamente en qué paso estamos.
     */
    function setInitialState() {
        //Sacamos los datos que el servidor nos ha pasado al crear la página
        const sessionDataElement = document.getElementById('active-session-data');
        const initSession = JSON.parse(sessionDataElement.textContent);

        // Mapa para comprobar facilmente si el servidor nos ha mandado los datos de ese paso
        const initConditions = {
            'models': 'id_model',
            'colors': 'id_color',
            'extras': 'extras',
            'summary': true,
        }

        // Definimos el orden del configurar en función del step
        // Si en el futuro el orden viniera definido por el backend, se recuperaría el array mandando por el backend en vez de fijarlo en el frontend.
        setProp('$stepOrder', ['0','models', 'colors', 'extras', 'summary']);
        //fijamos el 0 en la primera posición para mantener coherencia. Ahora i=1 es step 1, i=2 es step 2...
 
        setProp('$sessionId', initSession.id_conf_session);
        setProp('$selectedModel', initSession.id_model); //DONE: si el backend devolviera el vehiculo seleccionado entero en vez de solo el id, podríamos calcular el precio actual fácilmente en el frontend
        setProp('$selectedColor', initSession.id_color);
        setProp('$selectedExtras', initSession.extras ? initSession.extras.split(',').map(Number) : []);  
        
        const updatedCurrentSteps = [false, true, false, false, false];

        //Comprobamos si falta algo, si falta algo vamos a ese paso
        for (let i = 1; i < props.$stepOrder.length; i++) {
            const step = props.$stepOrder[i];
            const initConditionValue = initSession[initConditions[step]];
            // Lo detectamos por los datos de sesion que nos ha enviado el backend
            // Si el hay no hay modelo, color o extras seleccionado, vamos a ese paso
            // Si en todas hay y hemos llegado al último paso, vamos al último paso
            if (initConditionValue === null || i === props.$stepOrder.length-1){
                console.log(`Sin ${initConditions[step]}, vamos al step ${step}`);                

                if(updatedCurrentSteps[3] === true) updatedCurrentSteps[4] = true; //caso especial: si el paso de los extras está permitido, como no es obligatorio añadir ningún extrá, se permite ir al último paso

                //NOTA: ahora en el montaje usando los datos del servidor, será la única vez que StepController fije el paso del sistema y los pasos permitidos. De ahora en adelante será StepValidator el encargado de comprobar y avanzar el paso.
                setProp('$step', i); //seteamos el step
                setProp('$allowedSteps', updatedCurrentSteps); //seteamos los pasos permitidos

                //stepFuncMap[step](); //lanzamos la funcion asociada con el paso 
                //Ya no es necesario, al setear el paso ya se actualizará solo
                return;
            }
            updatedCurrentSteps[i+1] = true; //si se cumple la condición, ir al siguiente paso está permito
        }
    };

    let forceModelUpdate = false; //flag para forzar el cambio de modelo cuando el usuario confirme
    props.beforeUpdate = (vNode, changesToDo, currentProps) => {
        //App servirá como guardián. Si se detecta que se va a realizar un cambio de coche, se parará y se lanzará un componente de confirmación para no cambiar de coche hasta que el usuario confirme.
        //El hook beforeUpdate es ideal para esto: se ejecuta justo antes de actualizar el componente, y como App es el punto de paso de todo el estado, es el lugar ideal para controlarlo.

        //Si ya hay un color seleccionado (requisito para seleccionar extras) y se quiere guardar un modelo, se avisa al usuario de que esto reseteará la configuración para que confirme antes de proceder
        if(forceModelUpdate || !changesToDo.$saveModel || !props.$selectedColor){
            forceModelUpdate = false;
            return {};
        }
        
        //Guardamos los cambios a hacer y el estado actual, para decidir qué hacer en función de la decisión del usaurio usuario
        const changesBackup = {...changesToDo};
        const beforeChangeBackup = {...currentProps};

        //Creamos la promesa y guardamos el callback que le pasaremos a nuestro componente
        let resolveUserResponse;
        const userResponse = new Promise((resolve) => {
            resolveUserResponse = resolve;
        });

        // TO-DO FRAMEWORK: crear un proxy para pasar este tipo de referencias estilo React. Es muy simple: cuando intentas acceder/setear el objeto, te redirecciona al current
        let domGateway = { current: null } //Envolvemos el objeto que le pasamos a nuestro componente de confirmación para mantener la referencia cuando lo modifiquemos. No reasignaremos "domGateway", lo que haría que se apuntara a nuevo valor y se reompiera la referencia, reasignaremos su propiedad "current"

        //Creamos nuestro domNode 
        const vGateway = h(ConfirmActionGateway,
            {   
                message: 'Si cambias el modelo se borrará toda la configuración anterior. ¿Quieres continuar?',
                acceptText: 'ACEPTAR',
                cancelText: 'CANCELAR',
                promiseResolve: resolveUserResponse,
                domReference: domGateway,
            }
        );
        domGateway.current = createDomNode(vGateway); //creamos el gateway domNode en la referencia que le hemos pasado (el solo se encargará de autodestruirse cuando el usuario realice la acción)

        //insertamos nuestro componente de confirmación como primer hijo de la app
        const appDomNode = vNode.dom;
        appDomNode.insertBefore(domGateway.current, appDomNode.firstChild); 

        //Lanzamos la promesa para esperar la confirmación al usuario DE FORMA SINCRONA. No esperamos a que el usuario responda, necesitamos limpiar los cambios del loop actual ahora
        userResponse.then(
            (confirmed) => {
                if(confirmed){
                    //Si confirma el cambio, seteamos todas las keys que nos habían llegado
                    for (const key in changesBackup) {
                        setProp(key, changesBackup[key]);
                    }
                    forceModelUpdate = true; //seteamos la flag a true para permitir que se realicen los cambios en que acabamos de setear
                }else{    
                    //Si se cancela el cambio, seteamos los valores antes del cambio de modelo                 
                    for (const key in beforeChangeBackup) {
                        setProp(key, beforeChangeBackup[key]);
                    }
                }
            }                
        );

        return {...beforeChangeBackup}; //devolvemos el objeto de las props antes del cambio, sobreescribiendo los cambios que se iban a realizar en el loop actual para que no se realice ninguna actualización hasta que confirme el usuario (la actualización sobreescribirá los datos actuales con los mismos datos actuales, no hará NADA).  
        //IMPORTANTE: aquí lo modificamos directamente, NO HEMOS HECHO setProp como en un hook normal "beforeUpdate". Hacer setProp lanzaría un nuevo loop de updateDom y lo que queremos precisamente es pararlo todo hasta que el usuario confirme.
    };

    return h('div', {class: "configurator-app-container"},

        h(StepController, {
            target_StepComponent: {slotPath: ['$stepSlot'], idKey: 'current_step'},
            translations: props.$translations,
            defMap: {
                $sessionId: '$sessionId', $stepOrder : '$stepOrder',
                $step: '$currentStep', $allowedSteps: '$allowedSteps',
                $models: '$loadedModels', $selectedModel: '$selectedModel', $saveModel: '$saveModel',
                $colors: '$loadedColors', $selectedColor: '$selectedColor', $saveColor: '$saveColor',
                $extras: '$loadedExtras', $selectedExtras: '$selectedExtras', $saveExtras: '$saveExtras', 
                $totalPrice: '$totalPrice'
            }
        }),

        h(StepValidator, {
            childComponent: StepSelector,
            childProps: {
                stepsData: [ 
                    {name: `1. ${props.$translations.step1_title}`}, 
                    {name: `2. ${props.$translations.step2_title}`}, 
                    {name: `3. ${props.$translations.step3_title}`},
                    {name: `4. ${props.$translations.summary_title}`},
                ]
            },
            defMap:{   
                $step: '$currentStep', $allowedSteps: '$allowedSteps', $saveModel: '$saveModel',  
                $selectedModel: '$selectedModel', $selectedColor: '$selectedColor',
            },
        }),

        h('slot', {childrenDef: props.$stepSlot, slotName: '$stepSlot', domProps: {class: 'step-container', /*podria añadir onClick o lo que necesitara*/}}), 

        h('div', {class:'price-buttons-container'},
            h(SmartPriceDisplay, 
            {   
                title: props.$translations.price_label,
                defMap:
                {   $selectedModel: '$selectedModel', $saveModel: '$saveModel', 
                    $selectedColor: '$selectedColor', $selectedExtras: '$selectedExtras', $totalPrice: '$totalPrice'
                } 
            }),
            
            h(StepValidator, {
                childComponent: NextBeforeButtons,
                childProps: {
                    textBefore: props.$translations.back_button,
                    textAfter: props.$translations.next_button,
                    textAssignClient: props.$translations.assign_client_button,
                },
                defMap:{   
                    $step: '$currentStep', $allowedSteps: '$allowedSteps', $saveModel: '$saveModel',
                    $selectedModel: '$selectedModel', $selectedColor: '$selectedColor',
                },
            })
        ),
    );
}

//const appState = structuredClone(state); //no podemos hacer un clon porque nuestro estado podría incluir funciones: las listas dinámicas dentro de los slots. Además, el framework está preparado para funcionar de forma más eficiente con un sistema de duplicado por capas sin necesidad de crear un árbol completamente nuevo

//Apuntamos al contenedor raiz en el que correrá nuestra aplicación
const rootElement = document.getElementById('spa-content-area');

// Montamos la aplicación en el root element. 
// Le pasamos "state" para que funcione la reactividad y "true" al final para activar el debug (nos permitirá ver con más claridad al inspeccionar el HTML la estructura de nuestro arbol virtual)
plant(App, appState, rootElement, true);

//A partir de aquí, los componentes son los reponsables de manejar la App. Toda la lógica debería estar contenida dentro de ellos, que son los que tienen los helpers y la lógica para gestionar su propio estado, o el estado de otros componentes sobre los que tengan control
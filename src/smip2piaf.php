<?php
    
    use Joomla\CMS\HTML\HTMLHelper;
    HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.core.js', array('version' => 'auto', 'relative' => false));
    HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.tiqGraphQL.min.js', array('version' => 'auto', 'relative' => false));
    
    require_once 'thinkiq_context.php';
    $context = new Context();

    use Joomla\CMS\Response\JsonResponse; // Used for returning data to the client.
    use GuzzleHttp\Client;
    use GuzzleHttp\Psr7\Request;
    use \TiqUtilities\Database\PgSQL;


    if ($_POST['fetch_data'] == true) {

        $httpClient = new Client();

        $request = new Request('GET', $_POST['url']);
        $response = $httpClient->sendAsync($request)->wait();
        $content = json_decode($response->getBody()->getContents());
        $script = $content->script_templates[0]->script;
        $script4sql = str_replace("'", "''", $script);

        $script_id = $context->std_inputs->script_id;

        $db = new PgSQL(new \TiqConfig());
        $query = "UPDATE model.scripts_detail s set script='$script4sql' WHERE s.id=$script_id;";
        $result = $db->run($query)->fetch();

        die(new JsonResponse($result));

    } 
?>

<script>
    document.title='Import Equipment Types from AVEVA AF';
</script>

<div id="app">

    <div class="alert alert-info">
        Update Alert: You're running {{gitContext.release}} - you should update to {{updateToVersion}}.
        <button @click="updateNow" class="btn btn-secondary btn-sm pull-right mb-2">Update Now</button>
    </div>

    <div class="row">            
        <div class="col-12">
            <h1 class="pb-2 pt-2 text-center">{{pageTitle}}</h1>
            <p class="pb-4 text-center">
                <a v-bind:href="`index.php?option=com_modeleditor&view=script&id=${context.std_inputs.script_id}`" target="_blank">source</a>
            </p>
        </div>   
    </div>

    <div class="row">

        <div class="col-3">
            <h3>Load XML File</h3>
            <div class="card mt-3" style="max-height: 60rem;">
                <div class="card-body">
                    <div class="card-title">
                        <label class="text-reader">
                            <input class="btn btn-sm-light" type="file" @change="loadXmlFromFile" style="color: transparent;">
                        </label>   
                        <button @click="loadEquipmentTypes" class="btn btn-light btn-sm pull-right mt-2"><i class="fa fa-refresh" aria-hidden="true"></i> types</button>
                    </div>
                    <div class="list-group" style="max-height: 54rem; overflow-y:auto;">
                        <button v-for="aAfElementTemplate in afElementTemplates" class="list-group-item list-group-item-action" v-bind:class="{ active: activeTemplate==null ? false : activeTemplate.name==aAfElementTemplate.name }" @click="()=>{activeAttribute=null; activeTemplate=aAfElementTemplate;}">
                            {{aAfElementTemplate.name}}
                            <a 
                                :href="`?option=com_modeleditor&view=modeleditor&view_mode=flat&EqptTypeGrid_display_name=${encodeURIComponent(aAfElementTemplate.existingTypeDisplayName)}#EqptTypeGriddiv`" 
                                v-if="aAfElementTemplate.exists" target="_blank" :style="activeTemplate==null ? '' : activeTemplate.name==aAfElementTemplate.name ? 'color:darkblue;' : ''"
                                class="pull-right">already exists</a>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div v-if="activeTemplate">
                <h3>
                    Information Model
                    <button class="btn btn-primary pull-right mb-2" :disabled="activeTemplate.exists || templateNeedsBaseType(activeTemplate)" @click="importType">
                        Import Type
                    </button>
                </h3>
                <div class="card mt-3" style="max-height: 60rem;">
                    <div class="card-body">
                        <div class="card-title">
                            Name: {{activeTemplate.name}}<br/>
                            BaseType: {{activeTemplate.baseTemplateName==null ? 'n/a' : activeTemplate.baseTemplateName}}
                        </div>
                        <div class="card-text">
                            Description: {{activeTemplate.nodes.find(x=>x.tagName=='Description').textContent}}</br>
                            <div class="row">
                                <div class="col-4">
                                    </br>
                                    Attributes:</br>
                                    <div class="list-group" style="max-height: 51rem; overflow-y:auto;">
                                        <button v-for="aAfAttribute in activeTemplate.attributes" 
                                            class="list-group-item list-group-item-action" 
                                            v-bind:class="{ active: activeAttribute==null ? false : activeAttribute.name == aAfAttribute.name}" @click="()=>{activeAttribute=aAfAttribute;}">
                                            {{aAfAttribute.name}}
                                        </button>
                                    </div>
                                </div>
                                <div class="col-8">
                                    <div v-if="activeAttribute">
                                        </br>
                                        Attribute Settings:</br></br>
                                        Name: {{activeAttribute.nodes.find(x=>x.tagName=='Name')?.textContent}}</br>
                                        Description: {{activeAttribute.nodes.find(x=>x.tagName=='Description')?.textContent}}</br>
                                        Data Type: {{activeAttribute.nodes.find(x=>x.tagName=='Type')?.textContent}}</br>
                                        UoM: {{activeAttribute.nodes.find(x=>x.tagName=='DefaultUOM')?.textContent}}</br>
                                        Ref: {{activeAttribute.nodes.find(x=>x.tagName=='DataReference')?.textContent}}</br>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>

    var app = new core.Vue({
        el: "#app",
        data(){
            return {
                pageTitle: "Import Equipment Types from AVEVA AF",
                context:<?php echo json_encode($context)?>,
                gitContext:{
                    org: 'ThinkIQ-Labs',
                    repo: 'SMIP2PIAF',
                    release: 'v0.3.0-alpha'
                },
                offerUpdate: false,
                updateUrl: '',
                updateToVersion: '',
                afElementTemplates:[],
                activeTemplate:null,
                activeAttribute:null,
                existingEquipmentTypes:[],
                existingUoms:[]
            }
        },
        mounted: async function(){
            await this.checkGitVersion();
            await this.loadEquipmentTypes();
        },
        methods: {
            checkGitVersion: async function(){
                let aResponse = await fetch(`https://api.github.com/repos/${this.gitContext.org}/${this.gitContext.repo}/releases`);
                let releases = await aResponse.json();
                if(releases[0].name==this.gitContext.release){
                    console.log(`Running version ${this.gitContext.release} - you're up to date.`);
                } else {
                    console.log(`Running version ${this.gitContext.release} - you should update to version ${releases[0].name}.`);
                    this.offerUpdate = true;
                    this.updateToVersion = releases[0].name;

                    let assetsResponse = await fetch(releases[0].assets_url);
                    let assets = await assetsResponse.json();
                    this.updateUrl = assets[0].browser_download_url;

                }
            },
            updateNow: async function(){
                const apiRoute = 'index.php?option=com_thinkiq&task=invokeScript';
                let settings = { method: 'POST', headers: {} };
                let formData = new FormData();
                formData.append('script_name', this.context.std_inputs.script_name + '.php');
                formData.append('script_id', this.context.std_inputs.script_id);
                formData.append('output_type', 1);
                formData.append('fetch_data', true);
                formData.append('url', this.updateUrl);

                settings.body = formData;

                let response = await fetch(apiRoute, settings);
                const responseData = await response.json();

                console.log(responseData.data);
            },
            templateNeedsBaseType(aTemplate){
                if(aTemplate.baseTemplateName==null){
                    return false;
                } else if(this.existingEquipmentTypes.find(x=>x.displayName==aTemplate.baseTemplateName)==null){
                    return true;
                } else {
                    return false;
                }
            },
            getTextContentByTagName: function(nodeList, tagName){
                let returnValue = "";
                let aNode = nodeList.find(x=>x.tagName==tagName);
                if(aNode){
                    returnValue = aNode.textContent;
                }
                return returnValue;
            },
            importType: async function(){
                // create type
                let baseTypeId = null;
                if(this.activeTemplate.baseTemplateName!=null){
                    baseTypeId = this.existingEquipmentTypes.find(x=>x.displayName==this.activeTemplate.baseTemplateName).id;
                }
                let createEquipmentTypeQuery = `mutation m1{
                    createEquipmentType(
                        input: {
                          equipmentType: { 
                                displayName: "${this.activeTemplate.name}", 
                                description: "${this.activeTemplate.nodes.find(x=>x.tagName=='Description').textContent}" 
                                ${baseTypeId == null ? '' : 'subTypeOfId: "' + baseTypeId + '"'}
                            }
                        }
                      ) {
                        equipmentType{
                          id
                        }
                      }
                }`;
                let equipmentTypeId = (await tiqGraphQL.makeRequestAsync(createEquipmentTypeQuery)).data.createEquipmentType.equipmentType.id;
                this.activeTemplate.exists = true;
                await this.loadEquipmentTypes();
                // create attributes
                this.activeTemplate.attributes.forEach(async aAttribute => {
                    let description = JSON.stringify(this.getTextContentByTagName(aAttribute.nodes, 'Description'));
                    let dataType='';
                    let defaultType='';
                    let dataReferenceString = this.getTextContentByTagName(aAttribute.nodes, 'DataReference');
                    let source = dataReferenceString == "PI Point" ? "DYNAMIC" : "CONFIG";
                    
                    switch (aAttribute.nodes.find(x=>x.tagName=='Type').textContent)
                    {
                        case "Boolean":
                            dataType = "BOOL";
                            defaultType = "defaultBoolValue";
                            defaultValue= `${aAttribute.nodes.find(x=>x.tagName=='Value').textContent == 'FALSE' ? 'false' : 'true'}`;
                            break;
                        case "String":
                            dataType = "STRING";
                            defaultType = "defaultStringValue";
                            defaultValue= JSON.stringify(this.getTextContentByTagName(aAttribute.nodes, 'Value'));
                            break;
                        case "Double":
                        case "Single":
                            dataType = "FLOAT";
                            defaultType = "defaultFloatValue";
                            defaultValue= aAttribute.nodes.find(x=>x.tagName=='Value').textContent;
                            break;
                        case "Byte":
                        case "Int16":
                        case "Int32":
                        case "Int64":
                            dataType = "INT";
                            defaultType = "defaultIntValue";
                            defaultValue= `"${aAttribute.nodes.find(x=>x.tagName=='Value').textContent}"`;
                            break;
                        case "AFEnumerationValue":
                            dataType = "STRING";
                            defaultType = "defaultStringValue";
                            defaultValue= JSON.stringify(this.getTextContentByTagName(aAttribute.nodes, 'Value'));
                            break;
                        default:
                            break;
                    }

                    let afUom = this.getTextContentByTagName(aAttribute.nodes, 'DefaultUOM');
                    let aExistingUom = this.existingUoms.find(x=>x.symbol==afUom);
                    let uomId = aExistingUom!=null ? aExistingUom.id : '';
                    let quanityId = aExistingUom!=null ? aExistingUom.quantity.id : '';

                    let createTypeToAttributeTypeQuery = `mutation m1{
                        createTypeToAttributeType(
                            input: {
                                typeToAttributeType: {
                                    partOfId: "${equipmentTypeId}"
                                    displayName: "${aAttribute.name}"
                                    description: ${description}
                                    dataType: ${dataType}
                                    ${defaultType}: ${defaultValue}
                                    sourceCategory: ${source}
                                    ${quanityId=='' ? '' : 'quantityId:"' + quanityId + '"'}
                                    ${uomId=='' ? '' : 'defaultMeasurementUnitId:"' + uomId + '"'}
                                }
                            }
                            ) {
                            typeToAttributeType {
                                id
                            }
                          }
                    }`;
                    // console.log(createTypeToAttributeTypeQuery);
                    let typeToAttributeTypeResponse = await tiqGraphQL.makeRequestAsync(createTypeToAttributeTypeQuery);
                    // console.log(typeToAttributeTypeResponse);
                    let typeToAttributeTypeId = typeToAttributeTypeResponse.data.createTypeToAttributeType.typeToAttributeType.id;
                });
            },
            loadEquipmentTypes: async function(){
                let eqTypesQuery = `query q1{
                    equipmentTypes{
                        id
                        displayName
                    }
                    measurementUnits {
                        id
                        displayName
                        relativeName
                        symbol
                        quantity {
                            id
                            displayName
                        }
                    }
                }
                `;
                let aResponse = await tiqGraphQL.makeRequestAsync(eqTypesQuery);
                this.existingEquipmentTypes = aResponse.data.equipmentTypes;
                this.existingUoms = aResponse.data.measurementUnits;
                this.afElementTemplates.forEach(aTemplate =>{
                    let existingType = this.existingEquipmentTypes.find(x=>x.displayName.toLowerCase()==aTemplate.name.toLowerCase());
                    aTemplate.exists = existingType!=null;
                    aTemplate.existingTypeId = existingType==null ? 0 : existingType.id;
                    aTemplate.existingTypeDisplayName = existingType==null ? "" : existingType.displayName;
                });
            },
            loadXmlFromFile: function(ev) {
                const file = ev.target.files[0];
                const reader = new FileReader();
                reader.onload = e => {
                    this.activeAttribute=null;
                    this.activeTemplate=null;
                    this.afElementTemplates=[];
                    let text = e.target.result;
                    let parser = new DOMParser();
                    let xmlDoc = parser.parseFromString(text,"text/xml");
                    let afElementTemplates=[...xmlDoc.getElementsByTagName('AFElementTemplate')];
                    this.afElementTemplates=[];
                    afElementTemplates.forEach(aAfElementTemplate => {
                        let nodes = [...aAfElementTemplate.childNodes];

                        let name = nodes.find(x=>x.tagName=='Name').textContent;

                        let baseTemplate = nodes.find(x=>x.nodeName=='BaseTemplate');
                        if(baseTemplate==null){
                            console.log(`${name}: not based on a type`);
                        } else {
                            console.log(`${name}: based on ${baseTemplate.textContent}`);
                        }

                        let attributeNodes = nodes.filter(x=>x.nodeName=='AFAttributeTemplate');
                        let attributes = [];
                        attributeNodes.forEach(aAttribute =>{
                            let attributeNodes = [...aAttribute.childNodes];
                            attributes.push({
                                name: attributeNodes.find(x=>x.tagName=='Name').textContent,
                                nodes: attributeNodes
                            });
                        });
                        
                        let existingType = this.existingEquipmentTypes.find(x=>x.displayName.toLowerCase()==name.toLowerCase());
                        let exists = existingType!=null;
                        let existingTypeId = existingType==null ? 0 : existingType.id;
                        let existingTypeDisplayName = existingType==null ? "" : existingType.displayName;

                        this.afElementTemplates.push({
                            name: name,
                            baseTemplateName: baseTemplate==null ? null : baseTemplate.textContent,
                            nodes: nodes,
                            attributes: attributes,
                            exists: exists,
                            existingTypeId: existingTypeId,
                            existingTypeDisplayName: existingTypeDisplayName
                        });
                    });
                }
                reader.readAsText(file);
            }
        }
    });


</script>

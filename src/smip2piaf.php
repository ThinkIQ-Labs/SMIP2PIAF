<?php
    
    use Joomla\CMS\HTML\HTMLHelper;
    HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.core.js', array('version' => 'auto', 'relative' => false));
    HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.tiqGraphQL.min.js', array('version' => 'auto', 'relative' => false));
    
    require_once 'thinkiq_context.php';
    $context = new Context();
?>

<script>
    document.title='Import Equipment Types from AVEVA AF';
</script>

<div id="app">

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
                                v-if="aAfElementTemplate.exists" target="_blank"
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
                    <button class="btn btn-primary pull-right mb-2" :disabled="activeTemplate.exists" @click="importType">
                        Import Type
                    </button>
                </h3>
                <div class="card mt-3" style="max-height: 60rem;">
                    <div class="card-body">
                        <div class="card-title">
                            Name: {{activeTemplate.name}}
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
                afElementTemplates:[],
                activeTemplate:null,
                activeAttribute:null,
                existingEquipmentTypes:[]
            }
        },
        mounted: async function(){
            await this.loadEquipmentTypes();
        },
        methods: {
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
                let createEquipmentTypeQuery = `mutation m1{
                    createEquipmentType(
                        input: {
                          equipmentType: { displayName: "${this.activeTemplate.name}", description: "${this.activeTemplate.nodes.find(x=>x.tagName=='Description').textContent}" }
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
                            defaultValue= `"${aAttribute.nodes.find(x=>x.tagName=='Value').textContent}"`;
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
                                }
                            }
                            ) {
                            typeToAttributeType {
                                id
                            }
                          }
                    }`;
                    let typeToAttributeTypeId = (await tiqGraphQL.makeRequestAsync(createTypeToAttributeTypeQuery)).data.createTypeToAttributeType.typeToAttributeType.id;
                });
            },
            loadEquipmentTypes: async function(){
                let eqTypesQuery = `query q1{
                    equipmentTypes{
                        id
                        displayName
                    }
                }
                `;
                this.existingEquipmentTypes = (await tiqGraphQL.makeRequestAsync(eqTypesQuery)).data.equipmentTypes;
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
                        let attributeNodes = nodes.filter(x=>x.nodeName=='AFAttributeTemplate');
                        let attributes = [];
                        attributeNodes.forEach(aAttribute =>{
                            let attributeNodes = [...aAttribute.childNodes];
                            attributes.push({
                                name: attributeNodes.find(x=>x.tagName=='Name').textContent,
                                nodes: attributeNodes
                            });
                        });
                        let name = nodes.find(x=>x.tagName=='Name').textContent;
                        
                        let existingType = this.existingEquipmentTypes.find(x=>x.displayName.toLowerCase()==name.toLowerCase());
                        let exists = existingType!=null;
                        let existingTypeId = existingType==null ? 0 : existingType.id;
                        let existingTypeDisplayName = existingType==null ? "" : existingType.displayName;

                        this.afElementTemplates.push({
                            name: name,
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

<template>

    <div class="row">
        <div class="col-12">
            <h1 class="pb-2 pt-2 text-center">{{ pageTitle }}</h1>
        </div>
    </div>

    <div class="row">

        <div v-if="isImportMode" class="col-4 pl-4">
            <button class="btn btn-light btn-sm" @click="activeTemplate = null; isImportMode = false;">Switch to
                Export</button>
            <div class="card mt-3" style="max-height: 60rem;">
                <div class="card-header">
                    <h4>Import Types from XML<button @click="loadEquipmentTypes"
                            class="btn btn-light btn-sm pull-right"><i class="fa fa-refresh" aria-hidden="true"></i>
                            types</button></h4>
                </div>
                <div class="card-body">
                    <div class="card-title">
                        <label class="text-reader">
                            <input class="btn btn-sm-light" type="file" @change="loadXmlFromFile"
                                style="color: transparent;">
                        </label>

                    </div>
                    <div class="list-group" style="max-height: 54rem; overflow-y:auto;">
                        <button :key="aAfElementTemplate.name" v-for="aAfElementTemplate in afElementTemplates"
                            class="list-group-item list-group-item-action"
                            v-bind:class="{ active: activeTemplate == null ? false : activeTemplate.name == aAfElementTemplate.name }"
                            @click="() => { activeAttribute = null; activeTemplate = aAfElementTemplate; }">
                            {{ aAfElementTemplate.name }}
                            <a :href="`?option=com_modeleditor&view=modeleditor&view_mode=flat&EqptTypeGrid_display_name=${encodeURIComponent(aAfElementTemplate.existingTypeDisplayName)}#EqptTypeGriddiv`"
                                v-if="aAfElementTemplate.exists" target="_blank"
                                :style="activeTemplate == null ? '' : activeTemplate.name == aAfElementTemplate.name ? 'color:darkblue;' : ''"
                                class="pull-right">already exists</a>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!isImportMode" class="col-4  pl-4">
            <button class="btn btn-light btn-sm" @click="activeTemplate = null; isImportMode = true;">Switch to
                Import</button>

            <div class="card mt-3" style="max-height: 60rem;">
                <div class="card-header">
                    <h4>Select Types to Export<button class="btn btn-primary btn-sm pull-right mt-1">Create XML</button>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="list-group" style="max-height: 54rem; overflow-y:auto;">
                        <button :key="aEquipmentType.id" :ref="`type_${aEquipmentType.id}`"
                            v-for="aEquipmentType in existingEquipmentTypes"
                            class="list-group-item list-group-item-action"
                            v-bind:class="{ active: activeTemplate == null ? false : activeTemplate.displayName == aEquipmentType.displayName }"
                            @click="() => { activeAttribute = null; activeTemplate = aEquipmentType; }">
                            {{ aEquipmentType.displayName }}<input type="checkbox" v-model="aEquipmentType.isChecked"
                                class="mr-3 pull-right" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-8 pr-4">
            <div v-if="activeTemplate">
                <h4>
                    Information Model
                    <button v-if="isImportMode" class="btn btn-primary pull-right mb-2"
                        :disabled="activeTemplate.exists || templateNeedsBaseType(activeTemplate)" @click="importType">
                        Import Type
                    </button>
                </h4>
                <div class="card mt-3" style="max-height: 60rem; width: 100%">
                    <div class="card-body">
                        <div class="card-title">
                            Name: {{ isImportMode ? activeTemplate.name : activeTemplate.displayName }}<br />
                            BaseType: {{ isImportMode ? (activeTemplate.baseTemplateName == null ? 'n/a' :
                                    activeTemplate.baseTemplateName) : (activeTemplate.subTypeOf == null ? 'n/a' :
                                        activeTemplate.subTypeOf.displayName)
                            }}
                            <button v-if="!isImportMode && activeTemplate.subTypeOf != null"
                                class="btn btn-light btn-sm ml-4" @click="jumpToType">select</button>
                        </div>
                        <div class="card-text">
                            Description: {{ isImportMode ?
                                    activeTemplate.nodes.find(x => x.tagName == 'Description').textContent :
                                    activeTemplate.description
                            }}<br />
                            <div class="row">
                                <div class="col-4">
                                    <br />
                                    Attributes:<br />
                                    <div v-if="isImportMode" class="list-group"
                                        style="max-height: 49rem; overflow-y:auto;">
                                        <button :key="aAfAttribute.name"
                                            v-for="aAfAttribute in activeTemplate.attributes"
                                            class="list-group-item list-group-item-action"
                                            v-bind:class="{ active: activeAttribute == null ? false : activeAttribute.name == aAfAttribute.name }"
                                            @click="() => { activeAttribute = aAfAttribute; }">
                                            {{ aAfAttribute.name }}
                                        </button>
                                    </div>
                                    <div v-if="!isImportMode" class="list-group"
                                        style="max-height: 49rem; overflow-y:auto;">
                                        <button :key="aAttribute.displayName"
                                            v-for="aAttribute in activeTemplate.typeToAttributeTypes"
                                            class="list-group-item list-group-item-action"
                                            v-bind:class="{ active: activeAttribute == null ? false : activeAttribute.displayName == aAttribute.displayName }"
                                            @click="() => { activeAttribute = aAttribute; }">
                                            {{ aAttribute.displayName }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="isImportMode" class="col-8">
                                    <div v-if="activeAttribute">
                                        <br />
                                        Attribute Settings:<br /><br />
                                        Name: {{ activeAttribute.nodes.find(x => x.tagName == 'Name')?.textContent
                                        }}<br />
                                        Description:
                                        {{ activeAttribute.nodes.find(x => x.tagName == 'Description')?.textContent
                                        }}<br />
                                        Data Type:
                                        {{ activeAttribute.nodes.find(x => x.tagName == 'Type')?.textContent }}<br />
                                        UoM:
                                        {{ activeAttribute.nodes.find(x => x.tagName == 'DefaultUOM')?.textContent
                                        }}<br />
                                        Ref:
                                        {{ activeAttribute.nodes.find(x => x.tagName == 'DataReference')?.textContent
                                        }}<br />
                                    </div>
                                </div>
                                <div v-if="!isImportMode" class="col-8">
                                    <div v-if="activeAttribute">
                                        <br />
                                        Attribute Settings:<br /><br />
                                        Name: {{ activeAttribute.displayName }}<br />
                                        Description: {{ activeAttribute.description }}<br />
                                        Data Type: {{ activeAttribute.dataType }}<br />
                                        UoM: {{ activeAttribute.defaultMeasurementUnit == null ? 'n/a' :
                                                activeAttribute.defaultMeasurementUnit.symbol
                                        }}<br />
                                        Ref: {{ activeAttribute.sourceCategory }}<br />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>

export default {
    name: 'SMIP2PIAF',
    data() {
        return {
            pageTitle: "AVEVA AF Equipment Types Import & Export ",
            offerUpdate: false,
            updateUrl: '',
            updateToVersion: '',
            afElementTemplates: [],
            activeTemplate: null,
            activeAttribute: null,
            existingEquipmentTypes: [],
            existingUoms: [],
            isImportMode: true
        }
    },
    mounted: async function () {
        await this.loadEquipmentTypes();
    },
    methods: {
        makeRequestAsync: async function (query) {
            let settings = { method: 'POST', headers: {} };

            settings.headers['Content-Type'] = 'application/json';
            settings.body = JSON.stringify({ query: query });

            settings.headers.Authorization = `Bearer ` + await this.getTokenAsync();

            // make call to obtain graphql response
            const apiRoute = '____';
            let fetchQueryResponse = await fetch(apiRoute, settings);
            return await fetchQueryResponse.json();
        },
        templateNeedsBaseType(aTemplate) {
            if (aTemplate.baseTemplateName == null) {
                return false;
            } else if (this.existingEquipmentTypes.find(x => x.displayName == aTemplate.baseTemplateName) == null) {
                return true;
            } else {
                return false;
            }
        },
        getTextContentByTagName: function (nodeList, tagName) {
            let returnValue = "";
            let aNode = nodeList.find(x => x.tagName == tagName);
            if (aNode) {
                returnValue = aNode.textContent;
            }
            return returnValue;
        },
        jumpToType: function () {
            this.activeAttribute = null;
            this.$refs[`type_${this.activeTemplate.subTypeOf.id}`][0].scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
            this.activeTemplate = this.existingEquipmentTypes.find(x => x.displayName == this.activeTemplate.subTypeOf.displayName);
        },
        importType: async function () {
            // create type
            let baseTypeId = null;
            if (this.activeTemplate.baseTemplateName != null) {
                baseTypeId = this.existingEquipmentTypes.find(x => x.displayName == this.activeTemplate.baseTemplateName).id;
            }
            let createEquipmentTypeQuery = `mutation m1{
                    createEquipmentType(
                        input: {
                          equipmentType: { 
                                displayName: "${this.activeTemplate.name}", 
                                description: "${this.activeTemplate.nodes.find(x => x.tagName == 'Description').textContent}" 
                                ${baseTypeId == null ? '' : 'subTypeOfId: "' + baseTypeId + '"'}
                            }
                        }
                      ) {
                        equipmentType{
                          id
                        }
                      }
                }`;
            let equipmentTypeId = (await this.makeRequestAsync(createEquipmentTypeQuery)).data.createEquipmentType.equipmentType.id;
            this.activeTemplate.exists = true;
            await this.loadEquipmentTypes();
            // create attributes
            this.activeTemplate.attributes.forEach(async aAttribute => {
                let description = JSON.stringify(this.getTextContentByTagName(aAttribute.nodes, 'Description'));
                let dataType = '';
                let defaultType = '';
                let defaultValue = '';
                let dataReferenceString = this.getTextContentByTagName(aAttribute.nodes, 'DataReference');
                let source = dataReferenceString == "PI Point" ? "DYNAMIC" : "CONFIG";

                switch (aAttribute.nodes.find(x => x.tagName == 'Type').textContent) {
                    case "Boolean":
                        dataType = "BOOL";
                        defaultType = "defaultBoolValue";
                        defaultValue = `${aAttribute.nodes.find(x => x.tagName == 'Value').textContent == 'FALSE' ? 'false' : 'true'}`;
                        break;
                    case "String":
                        dataType = "STRING";
                        defaultType = "defaultStringValue";
                        defaultValue = JSON.stringify(this.getTextContentByTagName(aAttribute.nodes, 'Value'));
                        break;
                    case "Double":
                    case "Single":
                        dataType = "FLOAT";
                        defaultType = "defaultFloatValue";
                        defaultValue = aAttribute.nodes.find(x => x.tagName == 'Value').textContent;
                        break;
                    case "Byte":
                    case "Int16":
                    case "Int32":
                    case "Int64":
                        dataType = "INT";
                        defaultType = "defaultIntValue";
                        defaultValue = `"${aAttribute.nodes.find(x => x.tagName == 'Value').textContent}"`;
                        break;
                    case "AFEnumerationValue":
                        dataType = "STRING";
                        defaultType = "defaultStringValue";
                        defaultValue = JSON.stringify(this.getTextContentByTagName(aAttribute.nodes, 'Value'));
                        break;
                    default:
                        break;
                }

                let afUom = this.getTextContentByTagName(aAttribute.nodes, 'DefaultUOM');
                let aExistingUom = this.existingUoms.find(x => x.symbol == afUom);
                let uomId = aExistingUom != null ? aExistingUom.id : '';
                let quanityId = aExistingUom != null ? aExistingUom.quantity.id : '';

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
                                    ${quanityId == '' ? '' : 'quantityId:"' + quanityId + '"'}
                                    ${uomId == '' ? '' : 'defaultMeasurementUnitId:"' + uomId + '"'}
                                }
                            }
                            ) {
                            typeToAttributeType {
                                id
                            }
                          }
                    }`;
                // console.log(createTypeToAttributeTypeQuery);
                let typeToAttributeTypeResponse = await this.makeRequestAsync(createTypeToAttributeTypeQuery);
                // console.log(typeToAttributeTypeResponse);
                let typeToAttributeTypeId = typeToAttributeTypeResponse.data.createTypeToAttributeType.typeToAttributeType.id;
                console.log(typeToAttributeTypeId);
            });
        },
        loadEquipmentTypes: async function () {
            let eqTypesQuery = `query q1{
                    equipmentTypes {
                        id
                        displayName
                        description
                        subTypeOf {
                            id
                            displayName
                        }
                        typeToAttributeTypes {
                            id
                            displayName
                            description
                            dataType
                            sourceCategory
                            defaultStringValue
                            defaultFloatValue
                            defaultBoolValue
                            defaultIntValue
                            defaultMeasurementUnit {
                                symbol
                            }
                        }
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
            let aResponse = await this.makeRequestAsync(eqTypesQuery);
            let equipmentTypes = aResponse.data.equipmentTypes;
            equipmentTypes.forEach(x => {
                x.isChecked = false;
            });
            this.existingEquipmentTypes = equipmentTypes.sort((a, b) => a.displayName.toLowerCase() > b.displayName.toLowerCase() ? 1 : -1);
            this.existingUoms = aResponse.data.measurementUnits;
            this.afElementTemplates.forEach(aTemplate => {
                let existingType = this.existingEquipmentTypes.find(x => x.displayName.toLowerCase() == aTemplate.name.toLowerCase());
                aTemplate.exists = existingType != null;
                aTemplate.existingTypeId = existingType == null ? 0 : existingType.id;
                aTemplate.existingTypeDisplayName = existingType == null ? "" : existingType.displayName;
            });
        },
        loadXmlFromFile: function (ev) {
            const file = ev.target.files[0];
            const reader = new FileReader();
            reader.onload = e => {
                this.activeAttribute = null;
                this.activeTemplate = null;
                this.afElementTemplates = [];
                let text = e.target.result;
                let parser = new DOMParser();
                let xmlDoc = parser.parseFromString(text, "text/xml");
                let afElementTemplates = [...xmlDoc.getElementsByTagName('AFElementTemplate')];
                this.afElementTemplates = [];
                afElementTemplates.forEach(aAfElementTemplate => {
                    let nodes = [...aAfElementTemplate.childNodes];

                    let name = nodes.find(x => x.tagName == 'Name').textContent;

                    let baseTemplate = nodes.find(x => x.nodeName == 'BaseTemplate');
                    if (baseTemplate == null) {
                        console.log(`${name}: not based on a type`);
                    } else {
                        console.log(`${name}: based on ${baseTemplate.textContent}`);
                    }

                    let attributeNodes = nodes.filter(x => x.nodeName == 'AFAttributeTemplate');
                    let attributes = [];
                    attributeNodes.forEach(aAttribute => {
                        let attributeNodes = [...aAttribute.childNodes];
                        attributes.push({
                            name: attributeNodes.find(x => x.tagName == 'Name').textContent,
                            nodes: attributeNodes
                        });
                    });

                    let existingType = this.existingEquipmentTypes.find(x => x.displayName.toLowerCase() == name.toLowerCase());
                    let exists = existingType != null;
                    let existingTypeId = existingType == null ? 0 : existingType.id;
                    let existingTypeDisplayName = existingType == null ? "" : existingType.displayName;

                    this.afElementTemplates.push({
                        name: name,
                        baseTemplateName: baseTemplate == null ? null : baseTemplate.textContent,
                        nodes: nodes,
                        attributes: attributes,
                        exists: exists,
                        existingTypeId: existingTypeId,
                        existingTypeDisplayName: existingTypeDisplayName
                    });
                });
            }
            reader.readAsText(file);
        },
        getTokenAsync: async function () {

            // required authenticator metadata
            const authenticator = {
                "graphQlEndpoint": "____",
                "clientId": "____",
                "clientSecret": "____",
                "userName": "____",
                "role": "____"
            };

            // settings for our graphql post calls
            const settings = {
                method: 'POST',
                headers: { "Content-Type": "application/json" }
            };

            // first query is to obtain a challenge
            const authRequestQuery = `
    mutation authRequest {
      authenticationRequest(input: {authenticator: "${authenticator.clientId}", role: "${authenticator.role}", userName: "${authenticator.userName}"}) {
        jwtRequest {
          challenge
          message
        }
      }
    }
    `;

            // second query is to obtain a token
            const authValidationQuery = function(challenge) {
                return `
    mutation authValidation {
        authenticationValidation(input: {authenticator: "${authenticator.clientId}", signedChallenge: "${challenge}|${authenticator.clientSecret}"}) {
          jwtClaim
        }
      }
    `;
            }

            settings.body = JSON.stringify({ query: authRequestQuery });

            let fetchAuthRequestQueryResponse = await fetch(authenticator.graphQlEndpoint, settings);
            let authRequestQueryResponse = await fetchAuthRequestQueryResponse.json();

            settings.body = JSON.stringify({ query: authValidationQuery(authRequestQueryResponse.data.authenticationRequest.jwtRequest.challenge) });

            let fetchAuthValidationQueryResponse = await fetch(authenticator.graphQlEndpoint, settings);
            let authValidationQueryResponse = await fetchAuthValidationQueryResponse.json();

            return authValidationQueryResponse.data.authenticationValidation.jwtClaim;

        }
    }
}

</script>
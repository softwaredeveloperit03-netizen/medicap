import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-configuration',
  templateUrl: './configuration.component.html',
  styleUrls: ['./configuration.component.css']
})
export class ConfigurationComponent implements OnInit {
  holidays;
  selectedResult = [];
  isEdit = false;

  // For group multi-select popup
  isGroupModal = false;
  currentGroupIndex: number = -1;
  tempSelectedGroups: string[] = [];
  groupSearchFilter: string = '';
  filteredSubtypes: any[] = [];

  // For stage multi-select popup
  isStageModal = false;
  currentStageIndex: number = -1;
  tempSelectedStages: any[] = [];
  stageSearchFilter: string = '';
  filteredStages: any[] = [];

  // For stage view popup
  isStageViewModal = false;
  currentStageViewIndex: number = -1;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.get_material_subtype();
    this.getSECTIONS();
  }
  
  subtypes: any[] = [];
  
  get_material_subtype(){
    this.service.get('common.php?type=get_material_subtype').subscribe((response: any) => {
      this.subtypes = Array.isArray(response) ? response : [];
      this.filteredSubtypes = Array.isArray(response) ? response : [];
    })
  }
  
  filterGroups() {
    if (!this.groupSearchFilter || this.groupSearchFilter.trim() === '') {
      this.filteredSubtypes = this.subtypes || [];
    } else {
      const searchTerm = this.groupSearchFilter.toLowerCase().trim();
      this.filteredSubtypes = (this.subtypes || []).filter(subtype => 
        subtype.material_subtype && 
        subtype.material_subtype.toLowerCase().includes(searchTerm)
      );
    }
  }
sections
  getSECTIONS(){
    this.service.get('common.php?type=getSECTIONS&department1=Production').subscribe(response => {
      this.sections = response;
    })
  }
  equipments
  get_Equipments(){
    this.service.get('common.php?type=get_Equipments&depart=Production').subscribe(response => {
      this.equipments = response;
    })
  }
  groupList = [];

add(data) {
  let temp = data.value;
  temp['equipmentList'] = [];
    temp['Stages'] = [];          // stages will be loaded after group selection
    temp['selectedGroups'] = [];  // store selected groups for this row
    temp['selectedStages'] = [];  // store selected stages for this row
  this.groupList.push(temp);
  data.resetForm();
}

  // Load stages for selected group(s)
  get_Stages(group: any, index: number) {
    const groupValue = Array.isArray(group) ? group.join(',') : group;
    this.service.get('bmr/process.php?type=getStagemasterLine&dosage_form=' + groupValue)
      .subscribe(response => {
        if (!this.groupList[index]) { return; }
        this.groupList[index]['Stages'] = response || [];
      });
  }

  // Open group multi-select modal for specific line
  openGroupModal(index: number) {
    this.currentGroupIndex = index;
    const existing = this.groupList[index]?.['selectedGroups'] || [];
    this.tempSelectedGroups = [...existing];
    this.groupSearchFilter = '';
    this.filteredSubtypes = this.subtypes || [];
    this.isGroupModal = true;
  }

  // Toggle selection inside modal
  toggleTempGroup(groupName: string, checked: boolean) {
    if (checked) {
      if (!this.tempSelectedGroups.includes(groupName)) {
        this.tempSelectedGroups.push(groupName);
      }
    } else {
      this.tempSelectedGroups = this.tempSelectedGroups.filter(g => g !== groupName);
    }
  }

  // Save selected groups back to row and refresh stages
  saveGroupSelection() {
    if (this.currentGroupIndex >= 0 && this.groupList[this.currentGroupIndex]) {
      this.groupList[this.currentGroupIndex]['selectedGroups'] = [...this.tempSelectedGroups];
      if (this.tempSelectedGroups.length) {
        // Call API to save groups and get stages from process_stages table
        const dosageForm = this.tempSelectedGroups.join(',');
        this.service.post('bmr/process.php?type=saveGroupsAndGetStages', JSON.stringify({
          dosage_form: dosageForm,
          selectedGroups: this.tempSelectedGroups
        })).subscribe((response: any) => {
          if (response && response['status'] === 'success') {
            // Update stages from process_stages table
            this.groupList[this.currentGroupIndex]['Stages'] = response['stages'] || [];
            // Clear selected stages when groups change
            this.groupList[this.currentGroupIndex]['selectedStages'] = [];
            alertify.success('Groups saved and stages loaded successfully');
          } else {
            // Fallback to existing method if new API fails
            this.get_Stages(this.tempSelectedGroups, this.currentGroupIndex);
          }
        }, error => {
          console.error('Error saving groups:', error);
          // Fallback to existing method on error
          this.get_Stages(this.tempSelectedGroups, this.currentGroupIndex);
        });
      } else {
        this.groupList[this.currentGroupIndex]['Stages'] = [];
      }
    }
    this.isGroupModal = false;
  }

isequip=false;
selectedIndex=-1;
equipmentList=[];
equipmentType='';
  AddEqup(index){
    this.selectedIndex=index;
    this.get_Equipments();
    this.isequip=true;
    this.equipmentList=this.groupList[index]['equipmentList'] || [];
    this.equipmentType=this.groupList[index]['Type'];
    this.equipment='';
    this.equipment_lineType='';
    this.selectedEquipment = null;
  }
  selectedEquipment=[];
  onChangeEquipment(index){
    this.selectedEquipment=this.equipments[index-1];
    console.log('this.selectedEquipment :>> ', this.selectedEquipment);
  }
  equipment='';
  equipment_lineType='';
  
  addEquipment(){
    if (!this.selectedEquipment || !this.equipment_lineType) {
      alertify.error('Please select equipment and equipment type');
      return;
    }
    
    let temp={}
    temp['equipment_name']=this.selectedEquipment['equipment_name'];
    temp['equipment_code']=this.selectedEquipment['equipment_code'];
    temp['capacity']=this.selectedEquipment['capacity'];
    temp['from_range']=this.selectedEquipment['from_range'];
    temp['to_range']=this.selectedEquipment['to_range'];
    temp['unit']=this.selectedEquipment['unit'];
    temp['equipment_lineType']=this.equipment_lineType;
    this.equipmentList.push(temp);
    this.groupList[this.selectedIndex]['equipmentList']=this.equipmentList;
    
    // Calculate capacities based on equipment type
    this.calculateCapacities(this.selectedIndex);
    
    // Reset form
    this.equipment='';
    this.equipment_lineType='';
    this.selectedEquipment = null;
  }

  // Calculate min and max capacities from equipment list
  calculateCapacities(index: number) {
    const equipmentList = this.groupList[index]['equipmentList'] || [];
    
    // Filter Manufacturing equipment
    const mfgEquipments = equipmentList.filter(eq => eq.equipment_lineType === 'Manufacturing');
    // Filter Filling equipment
    const fillingEquipments = equipmentList.filter(eq => eq.equipment_lineType === 'Filling');
    
    // Calculate Mfg Line capacities
    if (mfgEquipments.length > 0) {
      const mfgMinValues = mfgEquipments.map(eq => parseFloat(eq.from_range) || 0).filter(v => v > 0);
      const mfgMaxValues = mfgEquipments.map(eq => parseFloat(eq.to_range) || 0).filter(v => v > 0);
      
      this.groupList[index]['MfgLineMinCapacity'] = mfgMinValues.length > 0 ? Math.min(...mfgMinValues).toString() : '';
      this.groupList[index]['MfgLineMaxCapacity'] = mfgMaxValues.length > 0 ? Math.max(...mfgMaxValues).toString() : '';
    } else {
      this.groupList[index]['MfgLineMinCapacity'] = '';
      this.groupList[index]['MfgLineMaxCapacity'] = '';
    }
    
    // Calculate Filling Line capacities
    if (fillingEquipments.length > 0) {
      const fillingMinValues = fillingEquipments.map(eq => parseFloat(eq.from_range) || 0).filter(v => v > 0);
      const fillingMaxValues = fillingEquipments.map(eq => parseFloat(eq.to_range) || 0).filter(v => v > 0);
      
      this.groupList[index]['FillingLineMinCapacity'] = fillingMinValues.length > 0 ? Math.min(...fillingMinValues).toString() : '';
      this.groupList[index]['FillingLineMaxCapacity'] = fillingMaxValues.length > 0 ? Math.max(...fillingMaxValues).toString() : '';
    } else {
      this.groupList[index]['FillingLineMinCapacity'] = '';
      this.groupList[index]['FillingLineMaxCapacity'] = '';
    }
  }

  // Remove equipment and recalculate capacities
  removeEquipment(index: number, eqIndex: number) {
    this.equipmentList.splice(eqIndex, 1);
    this.groupList[this.selectedIndex]['equipmentList'] = this.equipmentList;
    this.calculateCapacities(this.selectedIndex);
  }

  // Get Manufacturing unit from equipment list
  getMfgUnit(config: any): string {
    const list = config?.equipmentList || [];
    const mfg = list.find((eq: any) => eq.equipment_lineType === 'Manufacturing');
    return mfg?.unit || '';
  }

  // Get Filling unit from equipment list
  getFillingUnit(config: any): string {
    const list = config?.equipmentList || [];
    const fill = list.find((eq: any) => eq.equipment_lineType === 'Filling');
    return fill?.unit || '';
  }



  SaveLine(){
    let temp={};
    temp['groupList']=this.groupList;
    console.log('temp :>> ', temp);
      this.service.post('bmr/process.php?type=saveLinemaster', JSON.stringify(temp)).subscribe(response => {
          if (response['status'] === 'success') {
            alertify.success("Saved Successfully");
            this.groupList=[];
          }else{
            alertify.error('Some Error Occured!');
          }
        });
  }

  close() {
    // Navigation is handled by routerLink in template
  }

  // Open stage multi-select modal for specific line
  openStageModal(index: number) {
    this.currentStageIndex = index;
    const existing = this.groupList[index]?.['selectedStages'] || [];
    this.tempSelectedStages = existing.map((stage: any) => ({ ...stage }));
    this.stageSearchFilter = '';
    this.filteredStages = this.groupList[index]['Stages'] || [];
    this.isStageModal = true;
  }

  // Check if stage is selected
  isStageSelected(stage: any): boolean {
    return this.tempSelectedStages.some(s => 
      s.dosage_form === stage.dosage_form && s.stage === stage.stage
    );
  }

  // Toggle stage selection inside modal
  toggleTempStage(stage: any, checked: boolean) {
    if (checked) {
      if (!this.isStageSelected(stage)) {
        this.tempSelectedStages.push({ ...stage });
      }
    } else {
      this.tempSelectedStages = this.tempSelectedStages.filter(s => 
        !(s.dosage_form === stage.dosage_form && s.stage === stage.stage)
      );
    }
  }

  // Filter stages based on search
  filterStages() {
    if (!this.stageSearchFilter || this.stageSearchFilter.trim() === '') {
      this.filteredStages = this.groupList[this.currentStageIndex]?.['Stages'] || [];
    } else {
      const searchTerm = this.stageSearchFilter.toLowerCase().trim();
      const allStages = this.groupList[this.currentStageIndex]?.['Stages'] || [];
      this.filteredStages = allStages.filter((stage: any) => 
        (stage.dosage_form && stage.dosage_form.toLowerCase().includes(searchTerm)) ||
        (stage.stage && stage.stage.toLowerCase().includes(searchTerm))
      );
    }
  }

  // Save selected stages back to row
  saveStageSelection() {
    if (this.currentStageIndex >= 0 && this.groupList[this.currentStageIndex]) {
      this.groupList[this.currentStageIndex]['selectedStages'] = this.tempSelectedStages.map(s => ({ ...s }));
    }
    this.isStageModal = false;
  }

  // Open stage view modal
  openStageViewModal(index: number) {
    this.currentStageViewIndex = index;
    this.isStageViewModal = true;
  }
}


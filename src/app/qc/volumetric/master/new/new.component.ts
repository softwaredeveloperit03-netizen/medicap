import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[ ]
})
export class NewComponent implements OnInit {

  storage_condition;
  isStorage=false;
  storages;
  chemicals=[];
  procedures=[];
  chemists: any[] = [];
  masters;
  reagents: any[] = [];
  results:any;
  safty_data =[];
  Preparation=[];
  equipmentData:any;
  equipment_name:any;
  department_name:any
  reagentname:any;
  manufacturename:any;
  reagents_data=[];
  equipmentSrNo;
  reagentSelecetdValue:any;
  equipmentArr=[];
  weigh_slip: File;
  Expirary=[]
  storage=[]
  Disposal:any;
  Expdays=" "
   options = [
    { label: 'use of', value: 'option1' },
    { label: 'prepare before use', value: 'option2' },
    { label: 'User Input', value: 'userInput' }
  ];
    emp_id: string;
    isDIGI: boolean=false
    materialForm: any;
    isbutton: boolean=true

  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getreagents();
    this.getchemicals();
    this.getEquipments();
    this.getVolumetricMaster();
    }
 

  molecular_wt ;
  StorageConditions;

  getVolumetricMaster() {
    this.service.get('qc/volumetric.php?type=getVolumetricMaster').subscribe(response => {
      this.results = response;
      });
  }
   

  getreagents(){
    this.service.get('qc/chemical.php?type=getReagentsLog').subscribe((response:any)=>{
      this.reagents = Array.isArray(response) ? response : [];
    });
  }
  


  getchemicals(){
    this.service.get('qc/chemical.php?type=getChemicalsLog').subscribe((response: any) => {
      this.chemists = Array.isArray(response) ? response : [];
    });
  }

  getEquipments(){
    this.service.get('master/equipment.php?type=getEquipments' + '&equipment_name=' + this.equipment_name + '&department_name=' + this.department_name).subscribe(response => {
      this.equipmentData= response;
    });
  }

  material_code = '';

  molidetails(index){
    index = index - 1;
    if(index !== -1){
      this.molecular_wt = this.chemists[index].molecular_wt;
      this.material_code = this.chemists[index].material_code || this.chemists[index].chemical_no;
    }
  }






  addChemical(data){
    
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }


    let temp = data.value;
    temp['molecular_wt'] = this.molecular_wt;
    temp['material_code'] = this.material_code;
    this.chemicals[this.chemicals.length]= temp;
    data.reset();
    this.molecular_wt = '';
    this.material_code = '';
  }

  delchemicals(index){
    this.chemicals.splice(index,1);
  }



  addProcedure(data){


    
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }

    let temp =data.value;

    this.procedures[this.procedures.length]= temp;
    data.reset();
  }

  delprocedure(index){
    this.procedures.splice(index,1);
  }

  delregent(index){
    this.reagents_data.splice(index, 1);
  }

  regBatches=[];


  regdetails(index){

    index = index - 1;
    if(index !== -1){
       
      this.regBatches = this.reagents[index];
    }

  }

  mfg_date ='';
  exp_date ='';

  getbatches(index){
    index = index - 1;
    if(index !== -1){
      this.mfg_date = this.regBatches[index].mfg_date;
      this.exp_date = this.regBatches[index].exp_date;
    }
  }


  addReagents(data){

    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }


    let temp = data.value;
    temp['material_code'] = this.regBatches['material_code'] || this.regBatches['indicator_no'];
    
    this.reagents_data.push(temp);
    data.reset();
    this.regBatches =[];
 
  }


  equipment_code ;
  equipmentdetails(index){
    index = index - 1;
    if(index !== -1){
      this.equipment_code= this.equipmentData[index]?.equipment_code;
    }

  }

  
  addEquipments(data){
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    let temp =data.value;
    temp['equipment_code'] = this.equipment_code;
   this.equipmentArr.push(temp);
   data.reset();
   this.equipment_code='';
  }


  delequip(index){
    this.equipmentArr.splice(index,1);
  }

  addSaftyNote(data){

    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }

    let temp =data.value;
   this.safty_data.push(temp);
   data.reset();
  }


  delsafty(index){
    this.safty_data.splice(index,1);
  }

  weigh_slip_req ='';



  onFileChanged(event){
    this.weigh_slip=event.target.files[0];
  }
  

  saveVolumetricMaster(data) {
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
 
    let temp=data.value;

    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    

     uploadData.append('procedures', JSON.stringify(this.procedures));
    uploadData.append('Reagent', JSON.stringify(this.reagents_data));
    uploadData.append('Equipment', JSON.stringify(this.equipmentArr));
    uploadData.append('Safty', JSON.stringify(this.safty_data));
    uploadData.append('chemicals', JSON.stringify(this.chemicals));
    
 
    this.service.postForm('qc/volumatric_sol.php?type=saveVolumetricMaster', uploadData).subscribe(
      (resp) => {
        const text = resp.body || '';
        let response: { status?: string } = {};
        try {
          response = JSON.parse(String(text).trim());
        } catch {
          alertify.error('Unexpected server response. Please try again.');
          return;
        }
        if (response.status === 'success') {
          this.isbutton = true;
          alertify.success('Volumetric Solution Saved Successfully');
          this.router.navigate(['/qc/volumetric/master']);
        } else {
          alertify.error(response.status || 'Save failed.');
        }
      },
      () => alertify.error('Unable to save volumetric solution.')
    );


  }

 

}

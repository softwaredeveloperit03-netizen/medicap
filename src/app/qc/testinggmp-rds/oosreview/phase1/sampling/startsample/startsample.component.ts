import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ReactiveFormsModule,FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';

declare let alertify;

@Component({
  selector: 'app-startsample',
  templateUrl: './startsample.component.html',
  styleUrls: ['./startsample.component.css']
})
export class StartsampleComponent implements OnInit {

  isNew = false;
  results;
  undertest_qty=0;
  selectedSampling:any = [];
  index:any=[];
  units;
  laminars;
  sampling;
  sampleData;
  isStart = false;
  start_date;
  stop_date;
  identication_qty:any;
  actual_indentification:any;
  withdrawal_identication:any;
  actual_composite:any;
  laf_start_date;
  laf_stop_date;
  isStop = false;
  isStopLAF = false;
  infoForm: FormGroup;
  checkListForm: FormGroup;
  samplingData;
spec_tests;
additional_qty = 0;




  checklist=[
    { observation: 'All are the containers properly segregated?', check:''},
    { observation: 'All are the containers properly Labeled (Supplier/Mfg., Approved label, Quarantine Label?', check:''},
    { observation: 'Is the information given on Quarantine label as per GRN?', check:''},
    { observation: 'Are there any damage/leakage/outer seals intact of the Drums/Containers/Bags?', check:''},
    { observation: 'Is sampling area properly cleaned', check:''},
    { observation: 'Is there any extraneous material observed on surface of Polybags', check:''},
    { observation: 'Are all the Drums/Containers sampled one by one and ls are closed properly after sampling?', check:''},
    { observation: 'Whether “Sampled” labels with Container No. are affix on polybags/Bottles containing sample?', check:''},
    { observation: 'Whether “Sample for Analysis” labels with container No. are affix on polybags/Bottles containing sample?', check:''},
    { observation: 'Whether the used sampling tools are kept in Polybags and closed properly for transferring it to laboratory for cleaning?', check:''},
    { observation: 'Are all the safety precautions have been taken during Sampling?', check:''},
    { observation: 'Are all the containers taken for sampling kept back to designated places?', check:''},
    { observation: 'Whether tanker number mentioned in tanker receipt intimation is matched with tanker to be sampled?', check:''},
    { observation: 'Are the entire compartment sealed of tanker?', check:''},
    { observation: 'Is the tanker cleaning record available with tanker?', check:''},
  ];
  composite_qty: any;
  reserve_composite: any;
  withdrawal_composite: any;
  checkPointData: any;
  total_qty1: any;
  reserve_qty1: any;






  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingSamplingForm();
    console.log(this.getPendingSamplingForm)
    this.getUnits();
    this.getCheckPointData();
    this.getSamplingEquipment();
    this.getsampler();

  }
  SamplingEquipment;


  getCheckPointData(){
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Sampling&form=Sampling Form').subscribe(response => {
     this.checkPointData = response;
   });
 }
 getSamplingEquipment(){
    this.service.get('qc/sampling/raw.php?type=getSamplingEquipment').subscribe(response => {
     this.SamplingEquipment = response;
   });
 }



 checkUnit(value,index){
  if(isNaN(value)){
    alertify.error('Please Enter Numeric Value');
    this.selectedSampling['container_details'][index].container_no = '';
  }
  if(this.sample_unit == " "){
    alertify.error('Please Select Sampling Unit');
    this.selectedSampling['container_details'][index].container_no = '';
  }

 }






 equipments;
  getsampler() {
    this.service.get('common.php?type=get_Equipments_sampling&depart=Quality Control&eq_type=Sampling Equipment').subscribe(response => {
      this.equipments = response;
    });
  }
  getPendingSamplingForm() {
    this.service.get('qc/sampling/raw.php?type=getPendingSamplingForm&for=oos').subscribe(response => {
      this.results = response;
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.getSpecifications();  
    console.log(this.selectedSampling['area_details'])
   
    this.isNew = true;
  }


  getSpecifications() {
    this.service.get('qc/specification/raw.php?type=getSpecificationByProduct&material_code=' + this.selectedSampling['material_code']).subscribe(response => {
      this.samplingData = response;
      this.spec_tests = response['tests']
      this.calculation();
    });
  }

  
  reserve_qty;

 composite_sample_data =[]
 retenation_sample_data =[]

 sample_unit = " ";

 which_one = '';
 any_discripancy = 'NO';
 undertest_qty_kg=0;

  calculation() {

    this.composite_sample_data =[]
    this.retenation_sample_data =[]
  
    let sampling_containers = 0;
    this.undertest_qty=0;
    this.undertest_qty_kg=0;
  
    let sampling_criteria = this.samplingData['category'];
    this.composite_qty = Number(this.samplingData['physical_qty']) + Number(this.samplingData['micro_qty']) + Number(this.samplingData['chemical_qty'])
    this.reserve_composite = Number(this.samplingData['control_sample']);
    this.additional_qty = Number(this.samplingData['additional_sample']);

    if (+this.selectedSampling['containers'] > 10) {


      if (sampling_criteria == 'In Active') {
        sampling_containers = Math.round(Math.sqrt(+this.selectedSampling['containers'])) + 1;
      } else {
        sampling_containers = this.selectedSampling['containers'];
      }

    } else {
      sampling_containers = +this.selectedSampling['containers'];
    }
    this.identication_qty = this.samplingData['indentification_qty'];
    this.selectedSampling['sampling_containers'] = sampling_containers;
    this.actual_composite = Number(this.additional_qty) + Number(this.reserve_composite) + Number(this.identication_qty);
    this.actual_indentification = Number(this.identication_qty) * sampling_containers;
    this.withdrawal_composite = Number(this.actual_composite) / Number(sampling_containers);
    this.withdrawal_composite = +parseFloat(+this.withdrawal_composite + '').toFixed(2);
    let containers = [];

    this.selectedSampling['sample_unit'] = this.sample_unit;


    this.composite_sample_data
    for (let i = 0; i < sampling_containers; i++) {
      let temp = {};
      temp['container_no'] = '';
      temp['identication_qty'] = this.identication_qty;
      temp['withdrawal_composite'] = this.withdrawal_composite;
      temp['sample_qty'] = Math.round(+ Number(this.identication_qty)/ Number(sampling_containers));
      temp['reserve_qty'] =  (Number(this.withdrawal_composite) / Number(sampling_containers));
      temp['total_qty'] = (+temp['identication_qty'] + temp['withdrawal_composite']).toFixed(2);
      this.undertest_qty = Number(this.undertest_qty) + Number(temp['total_qty']);
      temp['unit'] = this.selectedSampling['sample_unit'];
      temp['status'] = 'pending';
      temp['remarks'] = '';
      containers[containers.length] = temp;
      this.total_qty1=temp['total_qty'];
      this.reserve_qty1=temp['reserve_qty'];
      console.log(this.total_qty1);

    }

  let density = 1.08;

    if(this.sample_unit == 'gm'){
      this.undertest_qty_kg = this.undertest_qty / 1000;
    }
    else if(this.sample_unit == 'Nos'){
      this.undertest_qty_kg = this.undertest_qty;
    }
    else if(this.sample_unit == 'Kg'){
      this.undertest_qty_kg = this.undertest_qty;
    }
    else if(this.sample_unit == 'ml'){
      let quantity_liters = this.undertest_qty / 1000;
      this.undertest_qty_kg =  quantity_liters * density;
    }
    else if(this.sample_unit == 'Ltr'){
      this.undertest_qty_kg = this.undertest_qty * density;
    }

   



    let temp1 = {};
    temp1['ccontainer_no'] = '';
    temp1['per_contener'] = 0;
    temp1['sample_qty'] =  0;
    temp1['unit'] = this.selectedSampling['sample_unit'];
    temp1['status'] = 'pending';
    temp1['remarks'] = '';

    let temp2 = {};
    temp2['rcontainer_no'] = '';
    temp2['per_contener'] = 0;
    temp2['sample_qty'] =  0;
    temp2['unit'] = this.selectedSampling['sample_unit'];
    temp2['status'] = 'pending';
    temp2['remarks'] = '';


    this.composite_sample_data.push(temp1);
    this.retenation_sample_data.push(temp2);




    this.selectedSampling['container_details'] = containers;

     this.calculatesample();
   } 



   controlsamplereceive;
   retenations;
   compos
   ogsample;
   godLogic = false;

   calculatesample(){
     this.ogsample =0;
     this.retenations =0;
    this.compos =0;
    this.undertest_qty =0;

    let containers = this.selectedSampling['container_details'];


    for (let i = 0; i < containers.length; i++) {
      this.ogsample = this.ogsample + Number(containers[i].total_qty);
    }

    this.compos = this.composite_sample_data[0].sample_qty ;
    this.retenations = this.retenation_sample_data[0].sample_qty ;


    if(this.godLogic == true){
    this.undertest_qty = Number(this.ogsample) + Number(this.retenations);

    let density = 1.08;

    if(this.sample_unit == 'gm'){
      this.undertest_qty_kg = this.undertest_qty / 1000;
    }
    else if(this.sample_unit == 'Nos'){
      this.undertest_qty_kg = this.undertest_qty;
    }
    else if(this.sample_unit == 'Kg'){
      this.undertest_qty_kg = this.undertest_qty;
    }
    else if(this.sample_unit == 'ml'){
      let quantity_liters = this.undertest_qty / 1000;
      this.undertest_qty_kg =  quantity_liters * density;
    }
    else if(this.sample_unit == 'Ltr'){
      this.undertest_qty_kg = this.undertest_qty * density;
    }



    }else{
      this.undertest_qty = Number(this.ogsample) + Number(this.compos) +Number(this.retenations);

      let density = 1.08;

      if(this.sample_unit == 'gm'){
        this.undertest_qty_kg = this.undertest_qty / 1000;
      }
      else if(this.sample_unit == 'Nos'){
        this.undertest_qty_kg = this.undertest_qty;
      }
      else if(this.sample_unit == 'Kg'){
        this.undertest_qty_kg = this.undertest_qty;
      }
      else if(this.sample_unit == 'ml'){
        let quantity_liters = this.undertest_qty / 1000;
        this.undertest_qty_kg =  quantity_liters * density;
      }
      else if(this.sample_unit == 'Ltr'){
        this.undertest_qty_kg = this.undertest_qty * density;
      }
    }

    
   }




















 

  getLaminars() {
    this.service.get('qc/sampling.php?type=getLaminars').subscribe(response => {
      this.laminars = response;
    });
  }


  saveSamplingInfo(data,checklist) {

   

    let temp = data.value;
    console.log(data)
    let flag = 0;
    let containers = this.selectedSampling['container_details'];
    for (let i = 0; i < containers.length; i++) {
      if (containers[i].status == 'pending') {
        flag = 1;
        break;
      }
    }

    if (flag == 1) {
      alertify.error('All containers sample required');
      return;
    }
    temp['undertest_qty']=this.undertest_qty;
    temp['container_details'] = this.selectedSampling['container_details'];
    temp['checklist']=this.checklist;
    temp['composite_sample_data']=this.composite_sample_data;
    temp['retenation_sample_data']=this.retenation_sample_data;
    temp["equipment_code"] = this.equipment_code ;
    temp["checkPointData"]= this.checkPointData;
    temp["undertest_qty_kg"]= this.undertest_qty_kg;
    temp["sampling_index"]= this.sampling_index;
    temp["new_oos_id"]= this.selectedSampling['new_oos_id'];

    this.service.post('qc/sampling/raw.php?type=saveSamplingInfo&for=oos&id=' + this.selectedSampling['s_id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling Information saved successfully');
        this.isNew = false;
        this.getPendingSamplingForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  updateContainer(value,index) {
    let containers = this.selectedSampling['container_details'];
    containers[index].status = 'done';
    this.selectedSampling['container_details'] = containers;





    if(value == 'one'){
      let containers = this.selectedSampling['container_details'];
      containers[index].status = 'done';
      this.selectedSampling['container_details'] = containers;
    }
    if(value == 'two'){
      this.composite_sample_data[index].status = 'done';
    }
    if(value == 'three'){
      this.retenation_sample_data[index].status = 'done';
    }






  }
  
  equipment_code = '' ;
  sampling_index;
  updateSamplingTime(value,sampling_index) {
    this.sampling_index=sampling_index;
    console.log('sampling_index='+this.sampling_index)
    let noOfContainer = this.selectedSampling['sampling_containers'];
    console.log(noOfContainer);
    let specificationNo = this.selectedSampling['specification_no'];
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if (value == 'start') {
      this.start_date = new Date();
      this.isStart = true;
    } else if (value == 'stop') {
      this.stop_date = new Date();
      this.isStop = true;
    } else if (value == 'stoplaf') {
      this.laf_stop_date = new Date();
      this.isStopLAF = true;
    }
    
    
    let sampling_no = this.selectedSampling['sampling_no'];
    this.service.get('qc/sampling.php?type=getSamplingDetails&material_code='+this.selectedSampling['material_code']+'&sampling_no='+sampling_no).subscribe(response => {
      this.sampling = response[0];

      console.log(this.sampling['activity'].start_date);
       this.equipment_code = this.sampling.equipment_code;
      this.identication_qty = this.sampling.indentification_qty;
      this.composite_qty = this.sampling.composite;
      this.reserve_composite = this.sampling.control_sample;
       if(this.reserve_composite == '') this.reserve_composite = 0 ;

      this.actual_indentification = 2*this.sampling.indentification_qty;
      this.actual_composite = 2*this.sampling.composite;
      this.withdrawal_identication = this.actual_indentification;
      this.withdrawal_composite = (this.actual_indentification + this.actual_composite)/noOfContainer.toFixed(2);
      this.selectedSampling['sample_qty'] = this.withdrawal_composite;
      this.selectedSampling['reserve_qty'] =  this.reserve_composite;
      this.calculation();


    });
  }

  lafOtion="";


}

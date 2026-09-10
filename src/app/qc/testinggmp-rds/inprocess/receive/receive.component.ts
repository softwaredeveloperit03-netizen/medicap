import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
declare let alertify;
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-receive',
  templateUrl: './receive.component.html',
  styleUrls: ['./receive.component.css']
})
export class ReceiveComponent implements OnInit {

  isView = false;
  results;
  tab;


emp_id  =''

  selectedResult = [];
  constructor(private service:DataAccessService, private router: Router) { }

  ngOnInit(): void {
     this.getPendingReceiving('stage');
     this.getTestingPersons();
     this.emp_id = localStorage.getItem('emp_id');


  }
  getCurrentTime(action: string, data: any, i: number) {
    var d = new Date(),
        h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
        m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();

    if (action == 'from_time') {
        data.start_time = h + ':' + m;
    } else {
        data.end_times = h + ':' + m;
    }
}

  getPendingReceiving(value){
    this.tab=value;
    // this.service.get('production/technical.php?type=getPendingReceiving').subscribe(response => {
    this.service.get('production/stages.php?type=get_inprocess_data&testing_type='+value).subscribe(response => {
      this.results = response;
    });
  }
  isMoa=false;
  jadugarIndex;
  view(index){
    this.jadugarIndex =index;
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  isAllocation = false;
  viewAllocation(index){
    this.jadugarIndex =index;
    this.selectedResult = this.results[index];
    this.isAllocation = true;
  }
  employees;
  selectedMOA = [];
    calibration_frequency_inhouse;
    getTestingPersons() {
      this.service.get('common.php?type=getTestingPersons').subscribe(response => {
        this.employees = response;
      });
    }
  viewMOA(index) {
    let moa = this.selectedResult['spec_tests'];
    this.selectedMOA = moa[index];
    this.getTesting_methods(this.selectedMOA['test_method_no'])
    
    this.isMoa = true;
    this.isView=false;
  }

  method;
  selectedMethod
  getTesting_methods(test_method_no){
    this.service.get('qc/testing/raw.php?type=getTesting_methods&test_method_no='+test_method_no).subscribe(response => {
      this.method = response;
      this.selectedMethod=this.method[0];
      console.log(this.selectedMethod['eqdates'])
    });
  } 
  start_date
  isStart
  end_time
  isStop=false;
  end_date
  hplc_len=0;
  updateSamplingTime(value) {
    this.hplc_len=0



    var d = new Date(),
    year = d.getFullYear(),
    month = ((d.getMonth() + 1) < 10 ? '0' : '') + (d.getMonth() + 1),
    day = (d.getDate() < 10 ? '0' : '') + d.getDate(),
    h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
    m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();


    if (value == 'start') {

      this.start_date = day + '-' + month + '-' + year + ' ' + h + ':' + m;
      this.isStart = true;

    } else if (value == 'end') {

      this.end_time = h + ':' + m;
      this.end_date = day + '-' + month + '-' + year + ' ' + h + ':' + m;
      this.isStop = true;
    }


    let aa = this.selectedMethod['hplc']?.['Instrument_parameter_list']?.length || 0;
    let bb = this.selectedMethod['hplc']?.['Refractive_indexForm_list']?.length || 0;
    let cc = this.selectedMethod['hplc']?.['Method_ParameterForm_list']?.length || 0;
    let dd = this.selectedMethod['hplc']?.['Retention_time_list']?.length || 0;
    let sum=aa+bb+cc+dd;
    console.log(sum)
    this.hplc_len=sum;

  }

  w1=0;
  w2=0;
  w3=0;
  w4=0;



  
 
  isdata6: boolean = false;
  isdata7: boolean = false;
  isdata8: boolean = false;


 
  dataShow6() {
    this.isdata6 = !this.isdata6;
  }

  dataShow7 () {
    this.isdata7 = !this.isdata7;
  }
  dataShow8 () {
    this.isdata8 = !this.isdata8;
  }
  weighing_table=[];

  observation=''
  submitTest(data) {
    console.log(data);
    if(!data.valid){
      alertify.error('All Field are required');
      return;
    }
    let temp = data.value;
    temp['start_time'] = this.start_date;
    temp['end_time'] = this.end_date;
    temp['observation'] = this.observation;
    temp['chemi_data']=this.selectedMethod['chems_dats'];
    temp['dilutions']=this.selectedMethod['dilutions'];
    temp['eqdates']=this.selectedMethod['eqdates'];
    temp['Genral_Instruction']=this.selectedMethod['Genral_Instruction'];
    temp['purpose']=this.selectedMethod['purpose'];
    temp['Scope']=this.selectedMethod['Scope'];
    temp['Associative_Document']=this.selectedMethod['Associative_Document'];
    temp['Refrenced_Document']=this.selectedMethod['Refrenced_Document'];
    temp['defination']=this.selectedMethod['defination'];
    temp['testinginstruction']=this.selectedMethod['testinginstruction'];
    temp['Safety']=this.selectedMethod['Safety'];
    temp['procedure']=this.selectedMethod['procedure'];
    temp['balance']=this.selectedMethod['balance1'];
    temp['weighing_table']=this.weighing_table;
    temp['glasswares']=this.selectedMethod['glasswares'];
    temp['volumetric_solutions']=this.selectedMethod['volumetric_solutions'];
    temp['hplc']=this.selectedMethod['hplc'];
    temp['calculations']=this.selectedMethod['calculations'];
    temp['Documentations']=this.selectedMethod['Documentations'];
    temp['Trendings']=this.selectedMethod['Trendings'];
    temp['testing_test_id']=this.selectedMOA['testing_test_id'];
    temp['test_method_no']= this.selectedMOA['test_method_no'];
    temp['testing_no']= this.selectedResult['testing_no']
    temp['material_code']= this.selectedResult['product_code']
    temp['material_name']= this.selectedResult['product_name']
    temp['bmr_qcsample_tests_id']= this.selectedResult['bmr_qcsample_tests_id']
 
    
    this.service.post('qc/testing/raw.php?type=saveTestingForm1&id='+this.selectedMOA['ttt_id']+'&test_master_id='+this.selectedMOA['test_master_id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        // this.getPendingTestingForms();
        
        this.isMoa = false; 
        // this.isInit = false;
        this.isStart = false;
        this.result = 0;
        this.observation = '';

        this.router.navigate(['/qc/testing-rds/raw/awaiting']);
        alertify.success('test successfully send for approval');

        setTimeout(() => {
          this.view(this.jadugarIndex);
        }, 1000);

      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }
  result = 0;
  updateTests(index, action, value) {
    let test = this.selectedResult['spec_tests'];
    if (action === 'outside') {
      test[index].isoutside = value;
    } else if (action === 'person') {
      test[index].person = value;
    }else if (action === 'lab') {
      test[index].lab_name = value;
    }
    this.selectedResult['spec_tests'] = test;
  }
  testing_type;
  updateQcTest(status,id) {

 let temp={};
 temp['id']=id
    
    this.service.post('qc/testing/raw.php?type=update_statusQCtest&status='+status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Testing Person allocated successfully');
    
        this.isAllocation = false;
        // this.getTestings();
      } else {
        alertify.error('An error occured, ' + response['error']);
      }
    });
  }
  allocateTestingPerson() {

    // let isValidate = true ;
 
    // if(!isValidate) { alertify.error('Please allocate all material'); ; return false ; }
    // console.log(this.testing_type);
    // this.selectedResult.testing_type_s =  this.testing_type;
    
    this.service.post('qc/testing/raw.php?type=allocateTestingPerson_finish&testing_type='+this.testing_type+'&specification_no='+ this.selectedResult['specification_no']+'&spec_test_id='+ this.selectedResult['spec_test_id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Testing Person allocated successfully');
    
        this.isAllocation = false;
        this.getPendingReceiving('stage');
      } else {
        alertify.error('An error occured, ' + response['error']);
      }
    });
  }

  check_stat(){
    console.log("jadugar");
    

    if(this.selectedMOA['limit_type']=='Range'){
     
      if(this.result  >= Number(this.selectedMOA['lower_limit'])  && this.result  <=  Number(this.selectedMOA['upper_limit']) ){
        this.observation='complies';
        console.log('PASS.');
      }else{
        this.observation='non-complies';
        console.log('Fail.');
      }

    }
    else if(this.selectedMOA['limit_type']=='Not LessThan'){

      if(this.result  >= Number(this.selectedMOA['upper_limit']) ){
        this.observation='complies';
        console.log('PASS' + this.selectedMOA['upper_limit']);
      }else{
        this.observation='non-complies';
        console.log('Fail' +this.selectedMOA['upper_limit']);
      }

    }
    else if(this.selectedMOA['limit_type']=='Not MoreThan'){

      if(this.result  <=  Number(this.selectedMOA['lower_limit']) ){
        this.observation='complies';
        console.log('PASS' +this.selectedMOA['lower_limit']);
      }else{
        this.observation='non-complies';
        console.log('Fail'+ this.selectedMOA['lower_limit']);
      }

    }
    else if(this.selectedMOA['limit_type']=='Description' || this.selectedMOA['limit_type']=='Compliances'){
    
        this.observation=this.observation;
    }
  }
}
 